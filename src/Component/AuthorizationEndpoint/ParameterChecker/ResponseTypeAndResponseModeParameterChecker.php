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

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseTypeManager;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;

class ResponseTypeAndResponseModeParameterChecker implements ParameterChecker
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
    public function check(Authorization $authorization): Authorization
    {
        try {
            /*
             * @see http://tools.ietf.org/html/rfc6749#section-3.1.1
             */
            if (!$authorization->hasQueryParam('response_type')) {
                throw new \InvalidArgumentException('The parameter "response_type" is mandatory.');
            }
            $responseTypeName = $authorization->getQueryParam('response_type');
            $responseType = $this->getResponseType($responseTypeName);
            if (!$authorization->getClient()->isResponseTypeAllowed($responseTypeName)) {
                throw new \InvalidArgumentException(sprintf('The response type "%s" is not allowed for this client.', $responseTypeName)); // Should try to find the response mode before exception
            }
            $authorization = $authorization->withResponseType($responseType);

            if (true === $authorization->hasQueryParam('response_mode') && $this->isResponseModeParameterInAuthorizationRequestAllowed()) {
                $responseMode = $authorization->getQueryParam('response_mode');
            } else {
                $responseMode = $responseType->getResponseMode();
            }
            if (!$this->responseModeManager->has($responseMode)) {
                throw new \InvalidArgumentException(sprintf('The response mode "%s" is not supported. Please use one of the following values: %s.', $responseMode, implode(', ', $this->responseModeManager->list())));
            }
            $authorization = $authorization->withResponseMode($this->responseModeManager->get($responseMode));

            return $authorization;
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2AuthorizationException(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), $authorization, $e);
        }
    }

    /**
     * @param string $responseType
     *
     * @throws \InvalidArgumentException
     *
     * @return ResponseType
     */
    private function getResponseType(string $responseType): ResponseType
    {
        if (!$this->responseTypeManager->has($responseType)) {
            throw new \InvalidArgumentException(sprintf('The response type "%s" is not supported by this server', $responseType));
        }

        return $this->responseTypeManager->get($responseType);
    }

    /**
     * @return bool
     */
    public function isResponseModeParameterInAuthorizationRequestAllowed(): bool
    {
        return $this->responseModeParameterInAuthorizationRequestAllowed;
    }
}
