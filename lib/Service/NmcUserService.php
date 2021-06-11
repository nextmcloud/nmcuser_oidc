<?php
namespace OCA\NextMagentaCloud\Service;

use Exception;

use OCP\IUserManager;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccount;
use OCP\Security\ISecureRandom;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;

// classes from user_oidc app
use OCA\UserOIDC\Db\UserMapper;
use OCA\UserOIDC\Db\User;
use OCA\UserOIDC\Db\ProviderMapper;
use OCA\UserOIDC\Db\Provider;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

use OCA\NextMagentaCloud\Service\NotFoundException;
use OCA\NextMagentaCloud\Service\UserExistException;


use RuntimeException;

class NmcUserService {

    /** @var IUserManager */
	private $userManager;

    /** @var IAccountManager */
	private $accountManager;

    /** @var UserMapper */
	private $oidcUserMapper;

    /** @var ProviderMapper */
	private $oidcUProviderMapper;

    /** @var IProvider */
	protected $tokenProvider;

    /** @var ISecureRandom */
	private $random;

    public function __construct(IUserManager $userManager,
                            IAccountManager $accountManager,
                            UserMapper $oidcUserMapper,
                            ProviderMapper $oidcProviderMapper,
                            IProvider $tokenProvider,
                            ISecureRandom $random){
        $this->userManager = $userManager;
        $this->accountManager = $accountManager;
        $this->oidcUserMapper = $oidcUserMapper;
        $this->oidcProviderMapper = $oidcProviderMapper;
        $this->tokenProvider = $tokenProvider;
        $this->random = $random;
    }

    /**
     * Find OpenId connect provider id case-insensitive by name.
     */
    public function findProviderByIdentifier(string $provider) {
        $providers = $this->oidcProviderMapper->getProviders();
        foreach ($providers as $p) {
            if ((strcasecmp($p->getIdentifier(), $provider) == 0) ||
                (strcmp($p->id, $provider) == 0 )) {
                return $p->id;
            }
        }

        throw new NotFoundException("No oidc provider " . $provider);
    }

    /**
     * Imitate zhe userID computation from oidc app
     * id4me is not used/supported yet.
     */
    protected function computeUserId(string $providerId, string $username, bool $id4me = false) {
		// old way with hashed names only:
        //if ($id4me) {
		//	return hash('sha256', $providerId . '_1_' . $username);
		//} else {
		//	return hash('sha256', $providerId . '_0_' . $username);
		//}
        if (strlen($username) > 64) {
			return hash('sha256', $username);
		} else {
            return $username;
        }
    }

    /**
     * Find openid user entries based on username in id system or
     * by the generic hash id used by NextCloud user_oidc
     * with priority to the username in OpenID system.
     *
     * @return user object from manager
     */
    protected function findUser(string $provider, string $username) {
        $providerId = $this->findProviderByIdentifier($provider);
        $oidcUserId = $this->computeUserId($providerId, $username);
        $user = $this->userManager->get($oidcUserId);
        if ($user === null) {
            $user = $this->userManager->get($username);
        }
        if ($user === null) {
            throw new NotFoundException("No user " . $username . ", id=" . $oidcUserId);
        }

        return $user;
    }

    /**
     * Check for OpenId user existence
    */
    protected function userExists(string $provider, string $username) {
        try {
            $user = $this->findUser($provider, $username);
            return true;
        } catch (NotFoundException $eNotFound) {
            return false;
        }    
    }

    /**
     * Get openid user data based on username in id system or
     * by the generic hash id used by NextCloud user_oidc
     * with priority to the username in OpenID system.
     */
    public function find(string $provider, string $username) {
        try {
            $user = $this->findUser($provider, $username);
            $userAccount = $this->accountManager->getAccount($user);
            return [
                'id'          => $user->getUID(),
                'displayname' => $user->getDisplayName(),
                'email'       => $user->getEmailAddress(),
                'altemail'    => $userAccount->getProperty(IAccountManager::PROPERTY_ADDRESS)->getValue(), // tmp location only
                'quota'       => $user->getQuota(),
                'enabled'     => $user->isEnabled(),
            ];
        } catch(DoesNotExistException | MultipleObjectsReturnedException $eNotFound) {
            throw new NotFoundException($eNotFound->getMessage());
        } 
    }

    /**
     * This method only delivers ids/usernames of OpenID connect users 
     */
    public function findAll(string $provider, ?int $limit = null, ?int $offset = null) {
        //$providerId = $this->findProviderByIdentifier($provider);
        //$users = $this->oidcUserMapper->find("", $limit, $offset);
        
        $users = $this->userManager->search("", $limit, $offset);
        return $users;
    }

    /**
     * Encapsulation  
     */
    protected function createDbUser(string $providerId, string $username) {
		// old way with hashed names only:
        // return $this->oidcUserMapper->getOrCreate($providerId, $username);
        $userId = $this->computeUserId($providerId, $username);
        $user = new User();
		$user->setUserId($userId);
        return $this->oidcUserMapper->insert($user);
    }

    /**
     * Create a compliant user for 
     */
    public function create(string $provider,
                        string $username,
                        string $displayname,
                        $email = null,
                        $altemail = null,
                        string $quota = "3 GB",
                        bool $enabled = true) {
        $providerId = $this->findProviderByIdentifier($provider);
        if ($this->userExists($providerId, $username)) {
            throw new UserExistException("OpenID user " . $username . "," . $oidcUserId . " already exists!");
        }
        
        $oidcUser = $this->createDbUser($providerId, $username);
        $oidcUser->setDisplayName($displayname);
        $this->oidcUserMapper->update($oidcUser);
        $user = $this->userManager->get($oidcUser->getUserId());

        if ($altemail !== null) {
            $userAccount = $this->accountManager->getAccount($user);
            $userAccount->setProperty(IAccountManager::PROPERTY_ADDRESS, $altemail, 
                                      IAccountManager::SCOPE_PRIVATE, IAccountManager::VERIFIED);
            $this->accountManager->updateAccount($userAccount);
        }

        if ($email !== null) {
            $user->setEMailAddress($email);
        }

        $user->setQuota($quota);
        $user->setEnabled($enabled);

        return [
          'id' => $oidcUser->getUserId()
        ];
    }

    public function update(string $provider,
                        string $username,
                        $displayname = null, 
                        $email = null, 
                        $altemail = null,
                        $quota = null,
                        bool $enabled = true) {
        $user = $this->findUser($provider, $username);
        $oidcUser = $this->oidcUserMapper->getUser($user->getUID());
        $userAccount = $this->accountManager->getAccount($user);
        
        if ($altemail !== null) {
            $userAccount->setProperty(IAccountManager::PROPERTY_ADDRESS, $altemail, 
                                    IAccountManager::SCOPE_PRIVATE, IAccountManager::VERIFIED);
            $this->accountManager->updateAccount($userAccount);
        }

        if ($email !== null) {
            $user->setEMailAddress($email);
        }
        if ($quota !== null) {
            $user->setQuota($quota);
        }
        $user->setEnabled($enabled);
                            
        if ($displayname !== null) {
            $oidcUser->setDisplayName($displayname);
            $oidcUser->setDisplayName($displayname);
            $this->oidcUserMapper->update($oidcUser);
        }

        return [
            'id'          => $user->getUID(),
            'displayname' => $user->getDisplayName(),
            'email'       => $user->getEmailAddress(),
            'altemail'    => $userAccount->getProperty(IAccountManager::PROPERTY_ADDRESS)->getValue(), // tmp location only
            'quota'       => $user->getQuota(),
            'enabled'     => $user->isEnabled(),
        ];  
    }

    public function delete(string $provider, string $username) {
        try {
            $user = $this->findUser($provider, $username);
            $oidcUser = $this->oidcUserMapper->getUser($user->getUID());

            // TODO: add this to user_oidc mapper as delete method
            $user->delete();
            $this->oidcUserMapper->delete($oidcUser);
            // TODO: delete openid entry in app
        } catch(DoesNotExistException | MultipleObjectsReturnedException $eNotFound) {
            throw new NotFoundException($eNotFound->getMessage());
        }

        return "";
    }

    /**
     * Generate app token
     *
     * @param string $providername
     * @param string $username
     * @return string
     * @throws RuntimeException
     */
    public function token(string $provider, string $username) {
        $user = $this->findUser($provider, $username);

        $token = $this->random->generate(72, ISecureRandom::CHAR_UPPER.ISecureRandom::CHAR_LOWER.ISecureRandom::CHAR_DIGITS);
		$this->tokenProvider->generateToken(
			$token,
			$user->getUID(),
			$user->getUID(),
			null,
			'cli',
			IToken::PERMANENT_TOKEN,
			IToken::DO_NOT_REMEMBER
		);

        return [
            'token' => $token,
        ];
    }

}