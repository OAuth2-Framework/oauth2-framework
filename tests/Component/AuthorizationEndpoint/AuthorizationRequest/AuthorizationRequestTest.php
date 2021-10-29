<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationEndpoint\AuthorizationRequest;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServer;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 */
final class AuthorizationRequestTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function basicCalls(): void
    {
        $client = $this->prophesize(Client::class);
        $userAccount = $this->prophesize(UserAccount::class);
        $resourceServer = $this->prophesize(ResourceServer::class);
        $params = [
            'prompt' => 'consent login select_account',
            'ui_locales' => 'fr en',
            'scope' => 'scope1 scope2',
            'redirect_uri' => 'https://localhost',
        ];
        $authorizationRequest = new AuthorizationRequest($client->reveal(), $params);

        $authorizationRequest->setUserAccount($userAccount->reveal());
        $authorizationRequest->setResponseParameter('foo', 'bar');
        $authorizationRequest->setResponseHeader('X-FOO', 'bar');
        $authorizationRequest->setResourceServer($resourceServer->reveal());

        static::assertSame($params, $authorizationRequest->getQueryParams());
        static::assertFalse($authorizationRequest->hasQueryParam('client_id'));
        static::assertTrue($authorizationRequest->hasQueryParam('prompt'));
        static::assertSame('consent login select_account', $authorizationRequest->getQueryParam('prompt'));
        static::assertInstanceOf(Client::class, $authorizationRequest->getClient());
        static::assertSame('https://localhost', $authorizationRequest->getRedirectUri());
        static::assertInstanceOf(UserAccount::class, $authorizationRequest->getUserAccount());
        static::assertSame([
            'foo' => 'bar',
        ], $authorizationRequest->getResponseParameters());
        static::assertFalse($authorizationRequest->hasResponseParameter('bar'));
        static::assertTrue($authorizationRequest->hasResponseParameter('foo'));
        static::assertSame('bar', $authorizationRequest->getResponseParameter('foo'));
        static::assertSame([
            'X-FOO' => 'bar',
        ], $authorizationRequest->getResponseHeaders());
        static::assertFalse($authorizationRequest->hasPrompt('none'));
        static::assertTrue($authorizationRequest->hasPrompt('login'));
        static::assertSame(['consent', 'login', 'select_account'], $authorizationRequest->getPrompt());
        static::assertTrue($authorizationRequest->hasUiLocales());
        static::assertSame(['fr', 'en'], $authorizationRequest->getUiLocales());
        $authorizationRequest->allow();
        static::assertTrue($authorizationRequest->isAuthorized());
        $authorizationRequest->deny();
        static::assertFalse($authorizationRequest->isAuthorized());
        static::assertInstanceOf(ResourceServer::class, $authorizationRequest->getResourceServer());
        static::assertTrue($authorizationRequest->hasScope());
        static::assertSame('scope1 scope2', $authorizationRequest->getScope());
        static::assertInstanceOf(DataBag::class, $authorizationRequest->getMetadata());
    }
}
