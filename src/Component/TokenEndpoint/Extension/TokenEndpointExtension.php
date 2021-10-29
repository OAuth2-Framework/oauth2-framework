<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\TokenEndpoint\Extension;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

interface TokenEndpointExtension
{
    public function beforeAccessTokenIssuance(
        ServerRequestInterface $request,
        GrantTypeData $grantTypeData,
        GrantType $grantType,
        callable $next
    ): GrantTypeData;

    public function afterAccessTokenIssuance(
        Client $client,
        ResourceOwner $resourceOwner,
        AccessToken $accessToken,
        callable $next
    ): array;
}
