<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\AccessToken;

use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHint;

final class AccessTokenIntrospectionTypeHint implements TokenTypeHint
{
    public function __construct(
        private AccessTokenRepository $accessTokenRepository
    ) {
    }

    public function hint(): string
    {
        return 'access_token';
    }

    public function find(string $token): ?AccessToken
    {
        $id = new AccessTokenId($token);

        return $this->accessTokenRepository->find($id);
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
            'client_id' => $token->getClientId(),
            'resource_owner' => $token->getResourceOwnerId(),
            'expires_in' => $token->getExpiresIn(),
        ];
        if (! $token->getParameter()->has('scope')) {
            $values['scope'] = $token->getParameter()->get('scope');
        }

        return $values + $token->getParameter()
            ->all()
        ;
    }
}
