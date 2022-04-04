<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\AccessToken;

use function count;
use function is_array;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHint;

final class AccessTokenIntrospectionTypeHint implements TokenTypeHint
{
    public function __construct(
        private readonly AccessTokenRepository $accessTokenRepository
    ) {
    }

    public static function create(AccessTokenRepository $accessTokenRepository): static
    {
        return new self($accessTokenRepository);
    }

    public function hint(): string
    {
        return 'access_token';
    }

    public function find(string $token, ?ResourceServerId $resourceServerId): ?AccessToken
    {
        $id = AccessTokenId::create($token);
        $accessToken = $this->accessTokenRepository->find($id);

        if ($accessToken === null || $accessToken->getResourceServerId() === null) {
            return $accessToken;
        }

        if ($resourceServerId === null) {
            return null;
        }

        if ($accessToken->getResourceServerId()->getValue() === $resourceServerId->getValue()) {
            return $accessToken;
        }

        return null;
    }

    public function introspect(mixed $token): array
    {
        if (! $token instanceof AccessToken || $token->isRevoked() === true) {
            return [
                'active' => false,
            ];
        }

        $values = [
            'active' => ! $token->hasExpired(),
            'client_id' => (string) $token->getClientId(),
            'resource_owner' => (string) $token->getResourceOwnerId(),
            'expires_in' => $token->getExpiresIn(),
        ];
        if (! $token->getParameter()->has('scope')) {
            $values['scope'] = $token->getParameter()->get('scope');
        }

        return array_filter(
            array_merge($values, $token->getParameter()->all()),
            static function (mixed $value): bool {
                if (is_array($value) && count($value) === 0) {
                    return true;
                }

                return $value !== null;
            }
        );
    }
}
