<?php

namespace App\Controller;

use App\Exception\ApiException;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

trait ApiHelper
{
    protected ValidatorInterface $validator;

    protected ?Request $request;

    /**
     * Retrieves POST data from request.
     */
    protected function getPost($key, $default = null)
    {
        return $this->request->request->get($key, $default);
    }

    protected function getPostCollection($key): ArrayCollection
    {
        return new ArrayCollection($this->request->request->get($key, []));
    }

    /**
     * Validates a form model or entity and throws an error.
     */
    protected function validateOrDie($formModelOrEntity, $groups = null)
    {
        $errors = $this->validator->validate($formModelOrEntity, null, $groups);
        if ($errors->count()) {
            throw new ApiException(ApiException::VALIDATION_ERROR, $errors);
        }
    }

    protected function successResponse($context = null)
    {
        $respArr = [
            'success' => true,
            'data' => [],
        ];

        if ($context) {
            $respArr['data'] = $context;
        }

        return $respArr;
    }

    protected function errorResponse(string $message, $context = null)
    {
        $respArr = [
            'success' => false,
            'error' => $message,
            'context' => [],
        ];

        if ($context) {
            $respArr['context'] = $context;
        }

        return $respArr;
    }
}
