<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\AuthorizationEndpoint;

use Assert\Assertion;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use function Safe\sprintf;

class ResponseModeGuesser
{
    /**
     * @var ResponseModeManager
     */
    private $responseModeManager;

    /**
     * @var bool
     */
    private $responseModeParameterInAuthorizationRequestAllowed;

    public function __construct(ResponseModeManager $responseModeManager, bool $responseModeParameterInAuthorizationRequestAllowed)
    {
        $this->responseModeManager = $responseModeManager;
        $this->responseModeParameterInAuthorizationRequestAllowed = $responseModeParameterInAuthorizationRequestAllowed;
    }

    public function get(AuthorizationRequest $authorization, ResponseType $responseType): ResponseMode
    {
        if ($this->responseModeParameterInAuthorizationRequestAllowed && true === $authorization->hasQueryParam('response_mode')) {
            $responseModeName = $authorization->getQueryParam('response_mode');
        } else {
            $responseModeName = $responseType->getResponseMode();
        }
        Assertion::true($this->responseModeManager->has($responseModeName), sprintf('The response mode "%s" is not supported. Please use one of the following values: %s.', $responseModeName, implode(', ', $this->responseModeManager->list())));

        return $this->responseModeManager->get($responseModeName);
    }
}
