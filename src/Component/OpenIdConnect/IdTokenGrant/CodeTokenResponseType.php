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

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeResponseType;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\ImplicitGrant\TokenResponseType;

final class CodeTokenResponseType implements ResponseType
{
    private AuthorizationCodeResponseType $codeResponseType;

    private TokenResponseType $tokenResponseType;

    public function __construct(AuthorizationCodeResponseType $codeResponseType, TokenResponseType $tokenResponseType)
    {
        $this->codeResponseType = $codeResponseType;
        $this->tokenResponseType = $tokenResponseType;
    }

    public function associatedGrantTypes(): array
    {
        return array_merge(
            $this->codeResponseType->associatedGrantTypes(),
            $this->tokenResponseType->associatedGrantTypes()
        );
    }

    public function name(): string
    {
        return 'code token';
    }

    public function getResponseMode(): string
    {
        return self::RESPONSE_TYPE_MODE_FRAGMENT;
    }

    public function preProcess(AuthorizationRequest $authorization): void
    {
        $this->codeResponseType->preProcess($authorization);
        $this->tokenResponseType->preProcess($authorization);
    }

    public function process(AuthorizationRequest $authorization, TokenType $tokenType): void
    {
        $this->codeResponseType->process($authorization, $tokenType);
        $this->tokenResponseType->process($authorization, $tokenType);
    }
}
