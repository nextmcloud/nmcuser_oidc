<?php
namespace OCA\NextMagentaCloud\Controller;

use Closure;
use OCA\NextMagentaCloud\Service\NmcUserService;
use OCA\NextMagentaCloud\Service\NotFoundException;
use OCA\NextMagentaCloud\Service\UserExistException;
use OCP\IRequest;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\ApiController;


class NmcUserApiController extends ApiController {

	private $service;

	public function __construct($appName,
								IRequest $request,
								NmcUserService $service) {
		parent::__construct($appName, $request);
		$this->service = $service;
	}

	/**
	 * Utility function for uniform http status handling in case of
	 * $id is not found
	 */
	protected function handleNotFound(Closure $callback) {
		try {
			return new DataResponse($callback());
		} catch (NotFoundException $e) {
			$message = ['message' => $e->getMessage()];
			return new DataResponse($message, Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Utility function for uniform http status handling in case of
	 * $id is not found
	 */
	protected function handleAlreadyExists(Closure $callback) {
		try {
			return new DataResponse($callback(), Http::STATUS_CREATED);
		} catch (UserExistException $e) {
			$message = ['message' => $e->getMessage()];
			return new DataResponse($message, Http::STATUS_CONFLICT);
		}
	}


	/**
	 * @CORS
	 * @NoCSRFRequired
	 * @AdminRequired
	 *
	 * @param string $providername
	 */
	public function index($providername) {
		return new DataResponse($this->service->findAll($providername));
	}

	/**
	 * @CORS
	 * @NoCSRFRequired
	 * @AdminRequired
	 *
	 * @param string $providername
	 * @param string $id
	 */
	public function show($providername, $id) {
		return $this->handleNotFound(function () use ($providername, $id) {
			return $this->service->find($providername, $id);
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
	 * @param string $quota
	 * @param bool $enabled
	 */
	public function create(string $providername,
						   string $username,
						   string $displayname,
						   string $email,
						   string $quota,
						   bool $enabled = true) {
		return $this->handleAlreadyExists(function () use ($providername, $username, $displayname, $email, $quota, $enabled) {
			return $this->service->create($providername, $username, $displayname, $email, $quota, $enabled);
		});
	}

	/**
	 * @CORS
	 * @NoCSRFRequired
	 * @AdminRequired
	 *
	 * @param string $providername
	 * @param string $id
	 * @param string $displayname
	 * @param string $email
	 * @param string $quota
	 * @param bool $enabled
	 */
	public function update(string $providername,
						   string $id,
						   string $displayname,
						   string $email,
						   string $quota,
						   bool $enabled = true) {
		return $this->handleNotFound(function () use ($providername, $id, $displayname, $email, $quota, $enabled) {
			return $this->service->update($providername, $id, $displayname, $email, $quota, $enabled);
		});
	}

	/**
	 * @CORS
	 * @NoCSRFRequired
	 * @AdminRequired
	 *
	 * @param string $providername
	 * @param string $id
	 */
	public function destroy($providername, $id) {
		return $this->handleNotFound(function () use ($providername, $id) {
			return $this->service->delete($providername, $id);
		});
	}

	/**
	 * @CORS
	 * @NoCSRFRequired
	 * @AdminRequired
	 *
	 * @param string $providername
	 * @param string $id
	 */
	public function token($providername, $id) {
		return $this->handleNotFound(function () use ($providername, $id) {
			return $this->service->token($providername, $id);
		});
	}
}
