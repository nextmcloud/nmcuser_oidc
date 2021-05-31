<?php
namespace OCA\NextMagentaCloud\User\Service;

use Exception;

use OCP\IUserManager;
use OCP\IUser;
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
     * Otherwise, assume that the given parameter is already the id 
     */
    public function findProviderByIdentifier(string $providerNameOrId) {
        $providers = $this->oidcProviderMapper->getProviders();
        foreach ($providers as $provider) {
            if (strcasecmp($provider->identifier, $providerNameOrId) == 0) {
                return $provider->id;
            }
        }

        return $providerNameOrId;
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

    public function find(string $providername, string $username) {
        try {
            $providerId = $this->findProviderByIdentifier($providername);
            $oidcUserId = $this->computeUserId($providerId, $username);
            $user = $this->userManager->get($oidcUserId);
            return [
                'id'          => $user->getUID(),
                'displayname' => $user->getDisplayName(),
                'email'       => $user->getEmailAddress(),
                'quota'       => $user->getQuota(),
                'enabled'     => $user->isEnabled(),
            ];
        } catch(DoesNotExistException | MultipleObjectsReturnedException $eNotFound) {
            throw new NotFoundException($eNotFound->getMessage());
        } catch(Exception $e) {
            throw ($e);
        }
    }

    public function findAll(string $providername, string $username) {
        // TODO: implement multiple match (should this happen at all?)
        return [ $this->find($providername, $username) ];
    }

    public function create(string $providername,
                        string $username,
                        string $displayname, 
                        string $email, 
                        int $quota,
                        bool $enabled = true) {
        $providerId = $this->findProviderByIdentifier($providername);
        $oidcUserId = $this->computeUserId($providerId, $username);
        if ($this->oidcUserMapper->userExists($oidcUserId)) {
            throw new UserExistException("OpenID user " . $username . "," . $oidcUserId . " already exists!");
        }
        
        $oidcUser = $this->oidcUserMapper->getOrCreate($providerId, $username);
        $oidcUser->setDisplayName($displayname);
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
                        int $quota,
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
     * @param string $providerid
     * @param string $username
     * @return string
     * @throws RuntimeException
     */
    public function token(string $providerid, string $username) {
        $oidcUserId = $this->computeUserId($providerid, $username);
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