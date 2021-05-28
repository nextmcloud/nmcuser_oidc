<?php

namespace OCA\NextMagentaCloud\User\Controller;

use Closure;
use OCA\NextMagentaCloud\User\Service\NotFoundException;
use OCA\NextMagentaCloud\User\Service\DoesNotExistException;
use OCA\NextMagentaCloud\User\Service\MultipleObjectsReturnedException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;


trait ApiControllerTrait {

    protected function handleNotFound (Closure $callback) {
        try {
            return new DataResponse($callback());
        } catch(NotFoundException $e) {
            $message = ['message' => $e->getMessage()];
            return new DataResponse($message, Http::STATUS_NOT_FOUND);
        }
    }

    protected function handleException ($e) {
        if ($e instanceof DoesNotExistException ||
            $e instanceof MultipleObjectsReturnedException) {
            throw new NotFoundException($e->getMessage());
        } else {
            throw $e;
        }
    }


}