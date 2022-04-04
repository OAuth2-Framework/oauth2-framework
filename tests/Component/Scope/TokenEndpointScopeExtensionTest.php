<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Scope;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal
 */
final class TokenEndpointScopeExtensionTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function theRequestHasNoScope(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $request = $this->buildRequest('GET', []);
        $grantTypeData = GrantTypeData::create($client);
        $next = static function (ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData {
            return $grantTypeData;
        };

        $result = $this->getTokenEndpointScopeExtension()
            ->beforeAccessTokenIssuance($request, $grantTypeData, $this->getAuthorizationCodeGrantType(), $next)
        ;
        static::assertSame($grantTypeData, $result);
    }

    /**
     * @test
     */
    public function theRequestedScopeIsNotSupported(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $request = $this->buildRequest('GET', [
            'scope' => 'café',
        ]);
        $grantTypeData = GrantTypeData::create($client);
        $next = static function (ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData {
            return $grantTypeData;
        };

        try {
            $this->getTokenEndpointScopeExtension()
                ->beforeAccessTokenIssuance($request, $grantTypeData, $this->getAuthorizationCodeGrantType(), $next)
            ;
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_scope',
                'error_description' => 'An unsupported scope was requested. Available scope is/are: openid, scope1, scope2.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theRequestedScopeIsValid(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $request = $this->buildRequest('GET', [
            'scope' => 'scope2 scope1',
        ]);
        $grantTypeData = GrantTypeData::create($client);
        $next = static function (ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData {
            return $grantTypeData;
        };

        $result = $this->getTokenEndpointScopeExtension()
            ->beforeAccessTokenIssuance($request, $grantTypeData, $this->getAuthorizationCodeGrantType(), $next)
        ;
        static::assertTrue($result->getParameter()->has('scope'));
        static::assertSame('scope2 scope1', $result->getParameter()->get('scope'));
    }

    /**
     * @test
     */
    public function after(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $accessToken = new AccessToken(
            AccessTokenId::create('ACCESS_TOKEN_ID'),
            $client
                ->getClientId(),
            $client
                ->getPublicId(),
            new DateTimeImmutable('now +1 hour'),
            DataBag::create([]),
            DataBag::create([]),
            null
        );

        $next = static function (Client $client, ResourceOwner $resourceOwner, AccessToken $accessToken): array {
            return $accessToken->getResponseData();
        };

        $result = $this->getTokenEndpointScopeExtension()
            ->afterAccessTokenIssuance($client, $client, $accessToken, $next)
        ;
        static::assertCount(2, $result);
    }
}
