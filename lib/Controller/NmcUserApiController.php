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

    private $userId;

    public function __construct($appName, 
                                IRequest $request,
                                NmcUserService $service,
                                string $userId){
        parent::__construct($appName, $request);
        $this->service = $service;
        $this->userId = $userId;
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
     */
    public function index($id) {
        return new DataResponse($this->service->findAll($id, $this->userId));
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @AdminRequired
     *
     * @param int $id
     */
    public function show($id) {
        return $this->handleNotFound(function () use ($id) {
            return $this->service->find($id, $this->userId);
        });
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @AdminRequired
     *
     * @param string $title
     * @param string $content
     */
    public function create(string $providername,
                        string $username,
                        string $displayName, 
                        string $email, 
                        int $quota,
                        bool $enabled = true) {
        return $this->service->create($providername, $username, $displayName, $email, $quota, $enabled);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @AdminRequired
     *
     * @param int $id
     * @param string $title
     * @param string $content
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
     * @param int $id
     */
    public function destroy($id) {
        return $this->handleNotFound(function () use ($id) {
            return $this->service->delete($id);
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
