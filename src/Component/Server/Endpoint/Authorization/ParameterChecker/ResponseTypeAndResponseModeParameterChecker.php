<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Endpoint\Authorization\ParameterChecker;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use OAuth2Framework\Component\Server\ResponseMode\ResponseModeManager;
use OAuth2Framework\Component\Server\ResponseType\ResponseTypeInterface;
use OAuth2Framework\Component\Server\ResponseType\ResponseTypeManager;

final class ResponseTypeAndResponseModeParameterChecker implements ParameterCheckerInterface
{
    /**
     * @var ResponseModeManager
     */
    private $responseModeManager;

    /**
     * @var bool
     */
    private $responseModeParameterInAuthorizationRequestAllowed;

    /**
     * @var ResponseTypeManager
     */
    private $responseTypeManager;

    /**
     * ResponseTypeAndResponseModeParameterChecker constructor.
     *
     * @param ResponseTypeManager $responseTypeManager
     * @param ResponseModeManager $responseModeManager
     * @param bool                $responseModeParameterInAuthorizationRequestAllowed
     */
    public function __construct(ResponseTypeManager $responseTypeManager, ResponseModeManager $responseModeManager, bool $responseModeParameterInAuthorizationRequestAllowed)
    {
        $this->responseTypeManager = $responseTypeManager;
        $this->responseModeManager = $responseModeManager;
        $this->responseModeParameterInAuthorizationRequestAllowed = $responseModeParameterInAuthorizationRequestAllowed;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Authorization $authorization, callable $next): Authorization
    {
        try {
            /*
             * @see http://tools.ietf.org/html/rfc6749#section-3.1.1
             */
            Assertion::true($authorization->hasQueryParam('response_type'), 'The parameter \'response_type\' is mandatory.');
            $responseType = $authorization->getQueryParam('response_type');
            $responseTypes = $this->getResponseTypes($authorization->getQueryParam('response_type'));
            $authorization = $authorization->withResponseTypes($responseTypes);

            if (true === $authorization->hasQueryParam('response_mode') && $this->isResponseModeParameterInAuthorizationRequestAllowed()) {
                $responseMode = $authorization->getQueryParam('response_mode');
            } else {
                $responseMode = $this->findResponseMode($responseTypes, $responseType);
            }
            Assertion::true($this->responseModeManager->has($responseMode), sprintf('The response mode \'%s\' is supported. Please use one of the following values: %s.', $responseMode, implode(', ', $this->responseModeManager->list())));
            $authorization = $authorization->withResponseMode($this->responseModeManager->get($responseMode));
            Assertion::true($authorization->getClient()->isResponseTypeAllowed($responseType), sprintf('The response type \'%s\' is unauthorized for this client.', $responseType)); // Should try to find the response mode before exception

            return $next($authorization);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => $e->getMessage(), 'authorization' => $authorization]);
        }
    }

    /**
     * @param string $responseType
     *
     * @throws \InvalidArgumentException
     *
     * @return ResponseTypeInterface[]
     */
    private function getResponseTypes(string $responseType): array
    {
        Assertion::true($this->responseTypeManager->isSupported($responseType), sprintf('Response type \'%s\' is not supported by this server', $responseType));
        $types = $this->responseTypeManager->find($responseType);

        return $types;
    }

    /**
     * @return bool
     */
    public function isResponseModeParameterInAuthorizationRequestAllowed(): bool
    {
        return $this->responseModeParameterInAuthorizationRequestAllowed;
    }

    /**
     * @param array  $types
     * @param string $responseType
     *
     * @return string
     */
    public function findResponseMode(array $types, string $responseType): string
    {
        if (1 === count($types)) {
            // There is only one type (OAuth2 request)
            return $types[0]->getResponseMode();
        }

        //There are multiple response types
        switch ($responseType) {
            case 'code token':
            case 'code id_token':
            case 'id_token token':
            case 'code id_token token':
                return 'fragment';
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported response type combination \'%s\'.', $responseType));
        }
    }
}
