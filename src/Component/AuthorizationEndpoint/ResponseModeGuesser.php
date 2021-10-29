<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint;

use Assert\Assertion;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;

class ResponseModeGuesser
{
    public function __construct(
        private ResponseModeManager $responseModeManager,
        private bool $responseModeParameterInAuthorizationRequestAllowed
    ) {
    }

    public function get(AuthorizationRequest $authorization, ResponseType $responseType): ResponseMode
    {
        if ($this->responseModeParameterInAuthorizationRequestAllowed && $authorization->hasQueryParam(
            'response_mode'
        ) === true) {
            $responseModeName = $authorization->getQueryParam('response_mode');
        } else {
            $responseModeName = $responseType->getResponseMode();
        }
        Assertion::true(
            $this->responseModeManager->has($responseModeName),
            sprintf(
                'The response mode "%s" is not supported. Please use one of the following values: %s.',
                $responseModeName,
                implode(', ', $this->responseModeManager->list())
            )
        );

        return $this->responseModeManager->get($responseModeName);
    }
}
