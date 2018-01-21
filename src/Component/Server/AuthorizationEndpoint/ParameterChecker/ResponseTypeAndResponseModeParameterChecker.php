<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\AuthorizationEndpoint\ParameterChecker;

use OAuth2Framework\Component\Server\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\ResponseType;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\ResponseTypeManager;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;

final class ResponseTypeAndResponseModeParameterChecker implements ParameterChecker
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
            if (!$authorization->hasQueryParam('response_type')) {
                throw new \InvalidArgumentException('The parameter "response_type" is mandatory.');
            }
            $responseType = $authorization->getQueryParam('response_type');
            $responseTypes = $this->getResponseTypes($authorization->getQueryParam('response_type'));
            $authorization = $authorization->withResponseTypes($responseTypes);

            if (true === $authorization->hasQueryParam('response_mode') && $this->isResponseModeParameterInAuthorizationRequestAllowed()) {
                $responseMode = $authorization->getQueryParam('response_mode');
            } else {
                $responseMode = $this->findResponseMode($responseTypes, $responseType);
            }
            if (!$this->responseModeManager->has($responseMode)) {
                throw new \InvalidArgumentException(sprintf('The response mode "%s" is supported. Please use one of the following values: %s.', $responseMode, implode(', ', $this->responseModeManager->list())));
            }
            $authorization = $authorization->withResponseMode($this->responseModeManager->get($responseMode));
            if (!$authorization->getClient()->isResponseTypeAllowed($responseType)) {
                throw new \InvalidArgumentException(sprintf('The response type "%s" is unauthorized for this client.', $responseType)); // Should try to find the response mode before exception
            }

            return $next($authorization);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), $authorization, $e);
        }
    }

    /**
     * @param string $responseType
     *
     * @throws \InvalidArgumentException
     *
     * @return ResponseType[]
     */
    private function getResponseTypes(string $responseType): array
    {
        if (!$this->responseTypeManager->isSupported($responseType)) {
            throw new \InvalidArgumentException(sprintf('Response type "%s" is not supported by this server', $responseType));
        }
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
                throw new \InvalidArgumentException(sprintf('Unsupported response type combination "%s".', $responseType));
        }
    }
}
