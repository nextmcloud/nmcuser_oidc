<?php
namespace OCA\NextMagentaCloud\Service;

use Exception;

use OCP\IUserManager;
use OCP\Security\ISecureRandom;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;

// classes from user_oidc app
use OCA\UserOIDC\Db\UserMapper;
use OCA\UserOIDC\Db\User;
use OCA\UserOIDC\Db\ProviderMapper;
use OCA\UserOIDC\Db\Provider;
use RuntimeException;

class NmcUserService {

    /** @var IUserManager */
	private $userManager;

    /** @var UserMapper */
	private $oidcUserMapper;

    /** @var ProviderMapper */
	private $oidcUProviderMapper;

    /** @var IProvider */
	protected $tokenProvider;

    /** @var ISecureRandom */
	private $random;

    public function __construct(IUserManager $userManager,
                            UserMapper $oidcUserMapper,
                            ProviderMapper $oidcProviderMapper,
                            IProvider $tokenProvider,
                            ISecureRandom $random){
        $this->userManager = $userManager;
        $this->oidcUserMapper = $oidcUserMapper;
        $this->oidcProviderMapper = $oidcProviderMapper;
        $this->tokenProvider = $tokenProvider;
        $this->random = $random;
    }

    /**
     * Find OpenId connect provider id case-insensitive by name.
     */
    public function findProviderByIdentifier(string $providerNameOrId) {
        $providers = $this->oidcProviderMapper->getProviders();
        foreach ($providers as $provider) {
            if (strcasecmp($provider->getIdentifier(), $providerNameOrId) == 0) {
                return $provider->id;
            }
        }

        throw new NotFoundException("No oidc provider " . $providerNameOrId);
    }

    /**
     * Imitate zhe userID computation from oidc app
     * id4me is not used/supported yet.
     */
    protected function computeUserId(string $providerId, string $username, bool $id4me = false) {
		if ($id4me) {
			return hash('sha256', $providerId . '_1_' . $username);
		} else {
			return hash('sha256', $providerId . '_0_' . $username);
		}
    }

    /**
     * Find openid user entries based on username in id system or
     * by the generic hash id used by NextCloud user_oidc
     * with priority to the username in OpenID system.
     */
    public function find(string $providername, string $username) {
        try {
            $providerId = $this->findProviderByIdentifier($providername);
            $oidcUserId = $this->computeUserId($providerId, $username);
            $user = $this->userManager->get($oidcUserId);
            if ($user === null) {
                $user = $this->userManager->get($username);
            }
            if ($user === null) {
                throw new NotFoundException("No user " . $username . "id=" . $oidcUserId);
            }
            return [
                'id'          => $user->getUID(),
                'displayname' => $user->getDisplayName(),
                'email'       => $user->getEmailAddress(),
                'quota'       => $user->getQuota(),
                'enabled'     => $user->isEnabled(),
            ];    
        } catch(DoesNotExistException | MultipleObjectsReturnedException $eNotFound) {
            throw new NotFoundException($eNotFound->getMessage());
        } 
    }

    public function findAll(string $providername) {
        // TODO: implement multiple match (should this happen at all?)
        return [ ];
    }

    public function create(string $providername,
                        string $username,
                        string $displayname,
                        string $email,
                        string $quota,
                        bool $enabled = true) {
        $providerId = $this->findProviderByIdentifier($providername);
        $oidcUserId = $this->computeUserId($providerId, $username);
        if ($this->oidcUserMapper->userExists($oidcUserId)) {
            throw new UserExistException("OpenID user " . $username . "," . $oidcUserId . " already exists!");
        }
        
        $oidcUser = $this->oidcUserMapper->getOrCreate($providerId, $username);
        $oidcUser->setDisplayName($displayname);
        $this->oidcUserMapper->update($oidcUser);
        $user = $this->userManager->get($oidcUser->getUserId());
        $user->setEMailAddress($email);
        $user->setQuota($quota);
        $user->setEnabled($enabled);

        return [
          'id' => $oidcUserId
        ];
    }

    public function update(string $providername,
                        string $username,
                        string $displayname, 
                        string $email, 
                        string $quota,
                        bool $enabled = true) {
        $providerId = $this->findProviderByIdentifier($providername);
        $oidcUserId = $this->computeUserId($providerId, $username);
        if (!$this->oidcUserMapper->userExists($oidcUserId)) {
            throw new UserExistException("OpenID user " . $username . "," . $oidcUserId . " does not exist!");
        }
                            
        $oidcUser = $this->oidcUserMapper->getOrCreate($providerId, $username);
        $oidcUser->setDisplayName($displayname);
        $user = $this->userManager->get($oidcUser->getUserId());
        $user->setEMailAddress($email);
        $user->setQuota($quota);
        $user->setEnabled($enabled);
    }

    public function delete(string $providername, string $username) {
        try {
            $providerId = $this->findProviderByIdentifier($providername);
            $oidcUserId = $this->computeUserId($providerId, $username);
            $user = $this->userManager->get($oidcUserId);
            $user->delete();
            // TODO: delete openid entry in app
        } catch(DoesNotExistException | MultipleObjectsReturnedException $eNotFound) {
            throw new NotFoundException($eNotFound->getMessage());
        } catch(Exception $e) {
            throw ($e);
        }
    }

    /**
     * Generate app token
     *
     * @param string $providername
     * @param string $username
     * @return string
     * @throws RuntimeException
     */
    public function token(string $providername, string $username) {
		$providerId = $this->findProviderByIdentifier($providername);
		$oidcUserId = $this->computeUserId($providerId, $username);
        $user = $this->userManager->get($oidcUserId);

        $token = $this->random->generate(72, ISecureRandom::CHAR_UPPER.ISecureRandom::CHAR_LOWER.ISecureRandom::CHAR_DIGITS);
		$this->tokenProvider->generateToken(
			$token,
			$user->getUID(),
			$user->getDisplayName(),
			'',
			'cli',
			IToken::PERMANENT_TOKEN,
			IToken::DO_NOT_REMEMBER
		);

        return [
            'token' => $token,
        ];
    }

}