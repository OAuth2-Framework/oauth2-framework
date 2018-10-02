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

namespace OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseTypeManager;

final class ResponseTypeAndResponseModeParameterChecker implements ParameterChecker
{
    private $responseModeManager;

    private $responseModeParameterInAuthorizationRequestAllowed;

    private $responseTypeManager;

    public function __construct(ResponseTypeManager $responseTypeManager, ResponseModeManager $responseModeManager, bool $responseModeParameterInAuthorizationRequestAllowed)
    {
        $this->responseTypeManager = $responseTypeManager;
        $this->responseModeManager = $responseModeManager;
        $this->responseModeParameterInAuthorizationRequestAllowed = $responseModeParameterInAuthorizationRequestAllowed;
    }

    public function check(AuthorizationRequest $authorization): void
    {
        /*
         * @see http://tools.ietf.org/html/rfc6749#section-3.1.1
         */
        if (!$authorization->hasQueryParam('response_type')) {
            throw new \InvalidArgumentException('The parameter "response_type" is mandatory.');
        }
        $responseTypeName = $authorization->getQueryParam('response_type');
        $responseType = $this->getResponseType($responseTypeName);
        if (!$authorization->getClient()->isResponseTypeAllowed($responseTypeName)) {
            throw new \InvalidArgumentException(\Safe\sprintf('The response type "%s" is not allowed for this client.', $responseTypeName)); // Should try to find the response mode before exception
        }
        $authorization->setResponseType($responseType);

        if (true === $authorization->hasQueryParam('response_mode') && $this->isResponseModeParameterInAuthorizationRequestAllowed()) {
            $responseModeName = $authorization->getQueryParam('response_mode');
        } else {
            $responseModeName = $responseType->getResponseMode();
        }
        if (!$this->responseModeManager->has($responseModeName)) {
            throw new \InvalidArgumentException(\Safe\sprintf('The response mode "%s" is not supported. Please use one of the following values: %s.', $responseModeName, \implode(', ', $this->responseModeManager->list())));
        }
        $responseMode = $this->responseModeManager->get($responseModeName);
        $authorization->setResponseMode($responseMode);
    }

    private function getResponseType(string $responseType): ResponseType
    {
        if (!$this->responseTypeManager->has($responseType)) {
            throw new \InvalidArgumentException(\Safe\sprintf('The response type "%s" is not supported by this server', $responseType));
        }

        return $this->responseTypeManager->get($responseType);
    }

    public function isResponseModeParameterInAuthorizationRequestAllowed(): bool
    {
        return $this->responseModeParameterInAuthorizationRequestAllowed;
    }
}
