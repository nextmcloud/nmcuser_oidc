<?php
namespace OCA\NextMagentaCloud\User\Service;

use Exception;

use OCP\IUserManager;
use OCP\IUser;

// classes from user_oidc app
use OCA\UserOIDC\Db\UserMapper;
use OCA\UserOIDC\Db\User;
use OCA\UserOIDC\Db\ProviderMapper;
use OCA\UserOIDC\Db\Provider;

class NmcUserService {

    /** @var IUserManager */
	private $userManager;

    /** @var UserMapper */
	private $oidcUserMapper;

    /** @var ProviderMapper */
	private $oidcUProviderMapper;

    public function __construct(IUserManager $userManager,
                            UserMapper $oidcUserMapper,
                            ProviderMapper $oidcProviderMapper){
        $this->userManager = $userManager;
        $this->oidcUserMapper = $oidcUserMapper;
        $this->oidcProviderMapper = $oidcProviderMapper;
    }

    /**
     * Find OpenId connect provider id case-insensitive by name.
     * Otherwise, assume that the given parameter is already the id 
     */
    public findProviderByIdentifier(string $providerNameOrId) {
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
    protected computeUserId(string $providerId, string $username, bool $id4me = false) {
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
                'displayName' => $user->getDisplayName(),
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
                        string $displayName, 
                        string $email, 
                        int $quota,
                        bool $enabled = true) {
        $providerId = $this->findProviderByIdentifier($providername);
        $oidcUserId = $this->computeUserId($providerId, $username);
        if ($this->oidcUserMapper->userExists($oidcUserId) {
            throw new UserExistException("OpenID user " . $username . "," . $oidcUserId . " already exists!")
        }
        
        $oidcUser = $this->oidcUserMapper->getOrCreate($providerId, $username);
        $oidcUser->setDisplayName($displayName);
        $user = $this->userManager->get($oidcUser->getUserId());
        $user->setEMailAddress($email);
        $user->setQuota($quota);
        $user->setEnabled($enabled);
    }

    public function update(string $providername,
                        string $username,
                        string $displayName, 
                        string $email, 
                        int $quota,
                        bool enabled = true) {
        $providerId = $this->findProviderByIdentifier($providername);
        $oidcUserId = $this->computeUserId($providerId, $username);
        if (!$this->oidcUserMapper->userExists($oidcUserId) {
            throw new UserExistException("OpenID user " . $username . "," . $oidcUserId . " does not exist!")
        }
                            
        $oidcUser = $this->oidcUserMapper->getOrCreate($providerId, $username);
        $oidcUser->setDisplayName($displayName);
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

    public function token(string $userId) {
    }

}