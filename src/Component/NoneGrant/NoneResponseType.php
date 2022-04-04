<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\NoneGrant;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\Core\TokenType\TokenType;

/**
 * This response type has been introduced by OpenID Connect. It stores the authorization to allow the access token
 * issuance later on. It returns nothing and only stores the authorization.
 *
 * At this time, this response type is not complete, because it always redirects the client. But if no redirect URI is
 * specified, no redirection should occur as per OpenID Connect specification.
 *
 * @see http://openid.net/specs/oauth-v2-multiple-response-types-1_0.html#none
 */
final class NoneResponseType implements ResponseType
{
    public function __construct(
        private readonly AuthorizationStorage $authorizationStorage
    ) {
    }

    public static function create(AuthorizationStorage $authorizationStorage): static
    {
        return new self($authorizationStorage);
    }

    public function associatedGrantTypes(): array
    {
        return [];
    }

    public function name(): string
    {
        return 'none';
    }

    public function getResponseMode(): string
    {
        return self::RESPONSE_TYPE_MODE_QUERY;
    }

    public function preProcess(AuthorizationRequest $authorization): void
    {
        // Nothing to do
    }

    public function process(AuthorizationRequest $authorization, TokenType $tokenType): void
    {
        $this->authorizationStorage->save($authorization);
    }
}
