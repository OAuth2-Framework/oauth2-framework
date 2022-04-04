<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\RefreshTokenGrant;

use function count;
use function is_array;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHint;

final class RefreshTokenIntrospectionTypeHint implements TokenTypeHint
{
    public function __construct(
        private RefreshTokenRepository $refreshTokenRepository
    ) {
    }

    public static function create(RefreshTokenRepository $refreshTokenRepository): static
    {
        return new self($refreshTokenRepository);
    }

    public function hint(): string
    {
        return 'refresh_token';
    }

    public function find(string $token, ?ResourceServerId $resourceServerId): ?RefreshToken
    {
        $id = RefreshTokenId::create($token);
        $refreshToken = $this->refreshTokenRepository->find($id);

        if ($refreshToken === null || $refreshToken->getResourceServerId() === null) {
            return $refreshToken;
        }

        if ($resourceServerId === null) {
            return null;
        }

        if ($refreshToken->getResourceServerId()->getValue() === $resourceServerId->getValue()) {
            return $refreshToken;
        }

        return null;
    }

    public function introspect(mixed $token): array
    {
        if (! $token instanceof RefreshToken || $token->isRevoked() === true) {
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
