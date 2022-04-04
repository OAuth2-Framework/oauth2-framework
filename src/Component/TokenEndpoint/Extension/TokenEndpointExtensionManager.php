<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\TokenEndpoint\Extension;

use function array_key_exists;
use function call_user_func;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

class TokenEndpointExtensionManager
{
    /**
     * @var TokenEndpointExtension[]
     */
    private array $extensions = [];

    public function add(TokenEndpointExtension $tokenEndpointExtension): static
    {
        $this->extensions[] = $tokenEndpointExtension;

        return $this;
    }

    public function handleBeforeAccessTokenIssuance(
        ServerRequestInterface $request,
        GrantTypeData $grantTypeData,
        GrantType $grantType
    ): GrantTypeData {
        return call_user_func($this->getCallableBeforeAccessTokenIssuance(0), $request, $grantTypeData, $grantType);
    }

    public function handleAfterAccessTokenIssuance(
        Client $client,
        ResourceOwner $resourceOwner,
        AccessToken $accessToken
    ): array {
        return call_user_func($this->getCallableAfterAccessTokenIssuance(0), $client, $resourceOwner, $accessToken);
    }

    private function getCallableBeforeAccessTokenIssuance(int $index): callable
    {
        if (! isset($this->extensions[$index])) {
            return function (
                ServerRequestInterface $request,
                GrantTypeData $grantTypeData,
                GrantType $grantType
            ): GrantTypeData {
                return $grantTypeData;
            };
        }
        $extension = $this->extensions[$index];

        return function (ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType) use (
            $extension,
            $index
        ): GrantTypeData {
            return $extension->beforeAccessTokenIssuance(
                $request,
                $grantTypeData,
                $grantType,
                $this->getCallableBeforeAccessTokenIssuance($index + 1)
            );
        };
    }

    private function getCallableAfterAccessTokenIssuance(int $index): callable
    {
        if (! array_key_exists($index, $this->extensions)) {
            return function (Client $client, ResourceOwner $resourceOwner, AccessToken $accessToken): array {
                return $accessToken->getResponseData();
            };
        }
        $extension = $this->extensions[$index];

        return function (Client $client, ResourceOwner $resourceOwner, AccessToken $accessToken) use (
            $extension,
            $index
        ): array {
            return $extension->afterAccessTokenIssuance(
                $client,
                $resourceOwner,
                $accessToken,
                $this->getCallableAfterAccessTokenIssuance($index + 1)
            );
        };
    }
}
