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

namespace OAuth2Framework\Component\OpenIdConnect\IdTokenGrant;

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeResponseType;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\ImplicitGrant\TokenResponseType;

final class CodeIdTokenTokenResponseType implements ResponseType
{
    /**
     * @var AuthorizationCodeResponseType
     */
    private $codeResponseType;

    /**
     * @var IdTokenResponseType
     */
    private $idTokenResponseType;

    /**
     * @var TokenResponseType
     */
    private $tokenResponseType;

    /**
     * CodeIdTokenTokenResponseType constructor.
     */
    public function __construct(AuthorizationCodeResponseType $codeResponseType, IdTokenResponseType $idTokenResponseType, TokenResponseType $tokenResponseType)
    {
        $this->codeResponseType = $codeResponseType;
        $this->idTokenResponseType = $idTokenResponseType;
        $this->tokenResponseType = $tokenResponseType;
    }

    public function associatedGrantTypes(): array
    {
        return \array_merge(
            $this->codeResponseType->associatedGrantTypes(),
            $this->idTokenResponseType->associatedGrantTypes(),
            $this->tokenResponseType->associatedGrantTypes()
        );
    }

    public function name(): string
    {
        return 'code id_token token';
    }

    public function getResponseMode(): string
    {
        return self::RESPONSE_TYPE_MODE_FRAGMENT;
    }

    public function preProcess(AuthorizationRequest $authorization): AuthorizationRequest
    {
        $authorization = $this->codeResponseType->preProcess($authorization);
        $authorization = $this->tokenResponseType->preProcess($authorization);
        $authorization = $this->idTokenResponseType->preProcess($authorization);

        return $authorization;
    }

    public function process(AuthorizationRequest $authorization): AuthorizationRequest
    {
        $authorization = $this->codeResponseType->process($authorization);
        $authorization = $this->tokenResponseType->process($authorization);
        $authorization = $this->idTokenResponseType->process($authorization);

        return $authorization;
    }
}
