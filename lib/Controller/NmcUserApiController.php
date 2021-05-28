<?php
namespace OCA\NextMagentaCloud\User\Controller;

use Closure;
use OCA\NextMagentaCloud\User\Service\NmcUserService;
use OCA\NextMagentaCloud\User\Service\NotFoundException;
use OCP\IRequest;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\ApiController;


class NmcUserApiController extends ApiController {

    private $service;

    public function __construct($appName, 
                                IRequest $request,
                                NmcUserService $service){
        parent::__construct($appName, $request);
        $this->service = $service;
    }

    /**
     * Utility function for uniform http status handling in case of
     * $id is not found
     */
    protected function handleNotFound (Closure $callback) {
        try {
            return new DataResponse($callback());
        } catch(NotFoundException $e) {
            $message = ['message' => $e->getMessage()];
            return new DataResponse($message, Http::STATUS_NOT_FOUND);
        }
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @AdminRequired
     *
     * @param string $providername
     * @param string $username
     */
    public function index($providername, $username) {
        return new DataResponse($this->service->findAll($providername, $username));
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @AdminRequired
     *
     * @param string $providername
     * @param string $username
     */
    public function show($providername, $username) {
        return $this->handleNotFound(function () use ($providername, $username) {
            return $this->service->find($providername, $username);
        });
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @AdminRequired
     *
     * @param string $providername
     * @param string $username
     * @param string $displayname
     * @param string $email
     * @param int $quota
     * @param bool $enabled
     */
    public function create(string $providername,
                        string $username,
                        string $displayname,
                        string $email, 
                        int $quota,
                        bool $enabled = true) {
        return $this->service->create($providername, $username, $displayname, $email, $quota, $enabled);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @AdminRequired
     *
     * @param string $providername
     * @param string $username
     * @param string $displayName
     * @param string $email
     * @param int $quota
     * @param bool $enabled
     */
    public function update(string $providername,
                        string $username,
                        string $displayName, 
                        string $email, 
                        int $quota,
                        bool $enabled = true) {
        return $this->handleNotFound(function () use ($providername, $username, $displayName, $email, $quota, $enabled) {
            return $this->service->update($providername, $username, $displayName, $email, $quota, $enabled);
        });
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @AdminRequired
     *
     * @param int $providername
     * @param string $username
     */
    public function destroy($providername, $username) {
        return $this->handleNotFound(function () use ($providername, $username) {
            return $this->service->delete($providername, $username);
        });
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @AdminRequired
     *
     * @param string $providername
     * @param string $username
     */
    public function token($providername, $username) {
        return $this->handleNotFound(function () use ($providername, $username) {
            return $this->service->token($providername, $username);
        });
    }

}
    /*
    string $providername,
    string $username,
    string $displayName, 
    string $email, 
    int $quota,
    bool $enabled = true
    */
