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

namespace OAuth2Framework\Component\OpenIdConnect\IdTokenGrant;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\ImplicitGrant\TokenResponseType;

final class IdTokenTokenResponseType implements ResponseType
{
    private IdTokenResponseType $idTokenResponseType;

    private TokenResponseType $tokenResponseType;

    /**
     * IdTokenTokenResponseType constructor.
     */
    public function __construct(IdTokenResponseType $idTokenResponseType, TokenResponseType $tokenResponseType)
    {
        $this->idTokenResponseType = $idTokenResponseType;
        $this->tokenResponseType = $tokenResponseType;
    }

    public function associatedGrantTypes(): array
    {
        return array_merge(
            $this->idTokenResponseType->associatedGrantTypes(),
            $this->tokenResponseType->associatedGrantTypes()
        );
    }

    public function name(): string
    {
        return 'id_token token';
    }

    public function getResponseMode(): string
    {
        return self::RESPONSE_TYPE_MODE_FRAGMENT;
    }

    public function preProcess(AuthorizationRequest $authorization): void
    {
        $this->tokenResponseType->preProcess($authorization);
        $this->idTokenResponseType->preProcess($authorization);
    }

    public function process(AuthorizationRequest $authorization, TokenType $tokenType): void
    {
        $this->tokenResponseType->process($authorization, $tokenType);
        $this->idTokenResponseType->process($authorization, $tokenType);
    }
}
