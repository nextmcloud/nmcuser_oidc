<?php
namespace OCA\NextMagentaCloud\User\Controller;

use Closure;

use OCP\IRequest;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\ApiController;

use OCA\NmcUserOidc\Service\NmcUserService;
use OCA\NmcUserOidc\Service\NotFoundException;

class NmcUserApiController extends ApiController {

    private $service;

    public function __construct($appName, 
                                IRequest $request,
                                NmcUserService $service){
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
    public function index() {
        return new DataResponse($this->service->findAll($this->userId));
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
                        bool enabled = true) {
        return $this->service->create($title, $content);
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
                        bool enabled = true) {
        return $this->handleNotFound(function () use ($id, $title, $content) {
            return $this->service->update($id, $title, $content;
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