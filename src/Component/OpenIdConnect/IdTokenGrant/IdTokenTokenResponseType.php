<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\IdTokenGrant;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\ImplicitGrant\TokenResponseType;

final class IdTokenTokenResponseType implements ResponseType
{
    public function __construct(
        private IdTokenResponseType $idTokenResponseType,
        private TokenResponseType $tokenResponseType
    ) {
    }

    public function associatedGrantTypes(): array
    {
        return array_merge(
            $this->idTokenResponseType->associatedGrantTypes(),
            $this->tokenResponseType->associatedGrantTypes()
        );
    }

    public static function create(
        IdTokenResponseType $idTokenResponseType,
        TokenResponseType $tokenResponseType
    ): self {
        return new self($idTokenResponseType, $tokenResponseType);
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
