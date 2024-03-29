<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\RefreshTokenGrant;

use DateTimeImmutable;
use function in_array;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\TokenEndpoint\Extension\TokenEndpointExtension;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

final class RefreshTokenEndpointExtension implements TokenEndpointExtension
{
    public function __construct(
        private int $lifetime,
        private RefreshTokenRepository $refreshTokenRepository
    ) {
    }

    public function beforeAccessTokenIssuance(
        ServerRequestInterface $request,
        GrantTypeData $grantTypeData,
        GrantType $grantType,
        callable $next
    ): GrantTypeData {
        return $next($request, $grantTypeData, $grantType);
    }

    public function afterAccessTokenIssuance(
        Client $client,
        ResourceOwner $resourceOwner,
        AccessToken $accessToken,
        callable $next
    ): array {
        $result = $next($client, $resourceOwner, $accessToken);
        $scope = $accessToken->getParameter()
            ->has('scope') ? explode(' ', $accessToken->getParameter()->get('scope')) : [];
        if (in_array('offline_access', $scope, true)) {
            $expiresAt = new DateTimeImmutable(sprintf('now +%u seconds', $this->lifetime));
            $refreshToken = $this->refreshTokenRepository->create(
                $accessToken->getClientId(),
                $accessToken->getResourceOwnerId(),
                $expiresAt,
                $accessToken->getParameter(),
                $accessToken->getMetadata(),
                null
            );
            $refreshToken->addAccessToken($accessToken->getId());
            $this->refreshTokenRepository->save($refreshToken);
            $result['refresh_token'] = $refreshToken->getId()->getValue();
        }

        return $result;
    }
}
