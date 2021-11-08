<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\IdTokenGrant;

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeResponseType;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\Core\TokenType\TokenType;

final class CodeIdTokenResponseType implements ResponseType
{
    public function __construct(
        private AuthorizationCodeResponseType $codeResponseType,
        private IdTokenResponseType $idTokenResponseType
    ) {
    }

    public static function create(
        AuthorizationCodeResponseType $codeResponseType,
        IdTokenResponseType $idTokenResponseType
    ): self {
        return new self($codeResponseType, $idTokenResponseType);
    }

    public function associatedGrantTypes(): array
    {
        return array_merge(
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

    public function process(AuthorizationRequest $authorization, TokenType $tokenType): void
    {
        $this->codeResponseType->process($authorization, $tokenType);
        $this->idTokenResponseType->process($authorization, $tokenType);
    }
}
