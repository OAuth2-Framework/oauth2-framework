<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Scope;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Scope\Policy\NoScopePolicy;
use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;
use OAuth2Framework\Component\Scope\Scope;
use OAuth2Framework\Component\Scope\ScopeRepository;
use OAuth2Framework\Component\Scope\TokenEndpointScopeExtension;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Component\TokenEndpoint\TokenEndpoint;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @internal
 */
final class TokenEndpointScopeExtensionTest extends TestCase
{
    use ProphecyTrait;

    private ?TokenEndpointScopeExtension $extension = null;

    /**
     * @inheritdoc}
     */
    protected function setUp(): void
    {
        if (! class_exists(TokenEndpoint::class)) {
            static::markTestSkipped('The component "oauth2-framework/token-endpoint" is not installed.');
        }
    }

    /**
     * @test
     */
    public function theRequestHasNoScope(): void
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()
            ->willReturn(false)
        ;
        $client->getPublicId()
            ->willReturn(new ClientId('CLIENT_ID'))
        ;
        $client->getClientId()
            ->willReturn(new ClientId('CLIENT_ID'))
        ;
        $client->getOwnerId()
            ->willReturn(new UserAccountId('USER_ACCOUNT_ID'))
        ;
        $client->has('scope_policy')
            ->willReturn(false)
        ;
        $client->has('scope')
            ->willReturn(false)
        ;

        $request = $this->buildRequest([]);
        $grantTypeData = new GrantTypeData($client->reveal());
        $grantType = $this->prophesize(GrantType::class);
        $next = function (
            ServerRequestInterface $request,
            GrantTypeData $grantTypeData,
            GrantType $grantType
        ): GrantTypeData {
            return $grantTypeData;
        };

        $result = $this->getExtension()
            ->beforeAccessTokenIssuance($request->reveal(), $grantTypeData, $grantType->reveal(), $next)
        ;
        static::assertSame($grantTypeData, $result);
    }

    /**
     * @test
     */
    public function theRequestedScopeIsNotSupported(): void
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()
            ->willReturn(false)
        ;
        $client->getPublicId()
            ->willReturn(new ClientId('CLIENT_ID'))
        ;
        $client->getClientId()
            ->willReturn(new ClientId('CLIENT_ID'))
        ;
        $client->getOwnerId()
            ->willReturn(new UserAccountId('USER_ACCOUNT_ID'))
        ;
        $client->has('scope_policy')
            ->willReturn(false)
        ;
        $client->has('scope')
            ->willReturn(false)
        ;

        $request = $this->buildRequest([
            'scope' => 'café',
        ]);
        $grantTypeData = new GrantTypeData($client->reveal());
        $grantType = $this->prophesize(GrantType::class);
        $next = function (
            ServerRequestInterface $request,
            GrantTypeData $grantTypeData,
            GrantType $grantType
        ): GrantTypeData {
            return $grantTypeData;
        };

        try {
            $this->getExtension()
                ->beforeAccessTokenIssuance($request->reveal(), $grantTypeData, $grantType->reveal(), $next)
            ;
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_scope',
                'error_description' => 'An unsupported scope was requested. Available scope is/are: scope1, scope2.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theRequestedScopeIsValid(): void
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()
            ->willReturn(false)
        ;
        $client->getPublicId()
            ->willReturn(new ClientId('CLIENT_ID'))
        ;
        $client->getClientId()
            ->willReturn(new ClientId('CLIENT_ID'))
        ;
        $client->getOwnerId()
            ->willReturn(new UserAccountId('USER_ACCOUNT_ID'))
        ;
        $client->has('scope_policy')
            ->willReturn(false)
        ;
        $client->has('scope')
            ->willReturn(false)
        ;

        $request = $this->buildRequest([
            'scope' => 'scope2 scope1',
        ]);
        $grantTypeData = new GrantTypeData($client->reveal());
        $grantType = $this->prophesize(GrantType::class);
        $next = function (
            ServerRequestInterface $request,
            GrantTypeData $grantTypeData,
            GrantType $grantType
        ): GrantTypeData {
            return $grantTypeData;
        };

        $result = $this->getExtension()
            ->beforeAccessTokenIssuance($request->reveal(), $grantTypeData, $grantType->reveal(), $next)
        ;
        static::assertTrue($result->getParameter()->has('scope'));
        static::assertSame('scope2 scope1', $result->getParameter()->get('scope'));
    }

    /**
     * @test
     */
    public function after(): void
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()
            ->willReturn(false)
        ;
        $client->getPublicId()
            ->willReturn(new ClientId('CLIENT_ID'))
        ;
        $client->getClientId()
            ->willReturn(new ClientId('CLIENT_ID'))
        ;
        $client->getOwnerId()
            ->willReturn(new UserAccountId('USER_ACCOUNT_ID'))
        ;
        $client->has('scope_policy')
            ->willReturn(false)
        ;
        $client->has('scope')
            ->willReturn(false)
        ;

        $accessToken = new AccessToken(
            new AccessTokenId('ACCESS_TOKEN_ID'),
            $client->reveal()
                ->getPublicId(),
            $client->reveal()
                ->getPublicId(),
            new DateTimeImmutable('now +1 hour'),
            new DataBag([]),
            new DataBag([]),
            null
        );

        $next = function (Client $client, ResourceOwner $resourceOwner, AccessToken $accessToken): array {
            return $accessToken->getResponseData();
        };

        $result = $this->getExtension()
            ->afterAccessTokenIssuance($client->reveal(), $client->reveal(), $accessToken, $next)
        ;
        static::assertCount(2, $result);
    }

    private function getExtension(): TokenEndpointScopeExtension
    {
        if ($this->extension === null) {
            $scope1 = $this->prophesize(Scope::class);
            $scope1->getName()
                ->willReturn('scope1')
            ;
            $scope1->__toString()
                ->willReturn('scope1')
            ;
            $scope2 = $this->prophesize(Scope::class);
            $scope2->getName()
                ->willReturn('scope2')
            ;
            $scope2->__toString()
                ->willReturn('scope2')
            ;
            $scopeRepository = $this->prophesize(ScopeRepository::class);
            $scopeRepository->all()
                ->willReturn([$scope1->reveal(), $scope2->reveal()])
            ;

            $scopePolicyManager = new ScopePolicyManager();
            $scopePolicyManager->add(new NoScopePolicy(), true);

            $this->extension = new TokenEndpointScopeExtension($scopeRepository->reveal(), $scopePolicyManager);
        }

        return $this->extension;
    }

    private function buildRequest(array $data): ObjectProphecy
    {
        $body = $this->prophesize(StreamInterface::class);
        $body->getContents()
            ->willReturn(http_build_query($data))
        ;
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->hasHeader('Content-Type')
            ->willReturn(true)
        ;
        $request->getHeader('Content-Type')
            ->willReturn(['application/x-www-form-urlencoded'])
        ;
        $request->getBody()
            ->willReturn($body->reveal())
        ;
        $request->getParsedBody()
            ->willReturn([])
        ;

        return $request;
    }
}
