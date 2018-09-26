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

final class CodeIdTokenResponseType implements ResponseType
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
     * CodeIdTokenResponseType constructor.
     */
    public function __construct(AuthorizationCodeResponseType $codeResponseType, IdTokenResponseType $idTokenResponseType)
    {
        $this->codeResponseType = $codeResponseType;
        $this->idTokenResponseType = $idTokenResponseType;
    }

    public function associatedGrantTypes(): array
    {
        return \array_merge(
            $this->codeResponseType->associatedGrantTypes(),
            $this->idTokenResponseType->associatedGrantTypes()
        );
    }

    public function name(): string
    {
        return 'code id_token';
    }

    public function getResponseMode(): string
    {
        return self::RESPONSE_TYPE_MODE_FRAGMENT;
    }

    public function preProcess(AuthorizationRequest $authorization): void
    {
        $this->codeResponseType->preProcess($authorization);
        $this->idTokenResponseType->preProcess($authorization);
    }

    public function process(AuthorizationRequest $authorization): void
    {
        $this->codeResponseType->process($authorization);
        $this->idTokenResponseType->process($authorization);
    }
}
