<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationEndpoint\AuthorizationRequest;

use DateTimeImmutable;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;
use OAuth2Framework\Tests\TestBundle\Entity\ResourceServer;
use OAuth2Framework\Tests\TestBundle\Entity\UserAccount;

/**
 * @internal
 */
final class AuthorizationRequestTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function basicCalls(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create(),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $userAccount = UserAccount::create(
            UserAccountId::create('USER_ACCOUNT_ID'),
            'username',
            [],
            new DateTimeImmutable('now -2 hours'),
            null,
            []
        );

        $resourceServer = ResourceServer::create(ResourceServerId::create('RESOURCE_SERVER'));
        $params = [
            'prompt' => 'consent login select_account',
            'ui_locales' => 'fr en',
            'scope' => 'scope1 scope2',
            'redirect_uri' => 'https://localhost',
        ];
        $authorizationRequest = AuthorizationRequest::create($client, $params);

        $authorizationRequest->setUserAccount($userAccount);
        $authorizationRequest->setResponseParameter('foo', 'bar');
        $authorizationRequest->setResponseHeader('X-FOO', 'bar');
        $authorizationRequest->setResourceServer($resourceServer);

        static::assertSame($params, $authorizationRequest->getQueryParams());
        static::assertFalse($authorizationRequest->hasQueryParam('client_id'));
        static::assertTrue($authorizationRequest->hasQueryParam('prompt'));
        static::assertSame('consent login select_account', $authorizationRequest->getQueryParam('prompt'));
        static::assertInstanceOf(Client::class, $authorizationRequest->getClient());
        static::assertSame('https://localhost', $authorizationRequest->getRedirectUri());
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
    }
}
