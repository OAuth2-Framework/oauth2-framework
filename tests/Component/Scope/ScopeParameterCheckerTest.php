<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Scope;

use InvalidArgumentException;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;

/**
 * @internal
 */
final class ScopeParameterCheckerTest extends OAuth2TestCase
{
    /**
     * @inheritdoc}
     */
    protected function setUp(): void
    {
        if (! class_exists(AuthorizationRequest::class)) {
            static::markTestSkipped('The component "oauth2-framework/authorization-endpoint" is not installed.');
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestWithScopeParameterIsChecked(): void
    {
        $client = Client::create(ClientId::create('CLIENT_ID'), DataBag::create(), UserAccountId::create('john.1'));
        $authorization = AuthorizationRequest::create($client, [
            'scope' => 'scope1',
        ]);

        $this->getScopeParameterChecker()
            ->check($authorization)
        ;

        static::assertSame([
            'scope' => 'scope1',
        ], $authorization->getResponseParameters());
    }

    /**
     * @test
     */
    public function anAuthorizationRequestWithAnUnsupportedScopeParameterIsChecked(): void
    {
        $client = Client::create(ClientId::create('CLIENT_ID'), DataBag::create(), UserAccountId::create('john.1'));
        $authorization = AuthorizationRequest::create($client, [
            'scope' => 'invalid_scope',
        ]);

        try {
            $this->getScopeParameterChecker()
                ->check($authorization)
            ;
            static::fail('Expected exception nt thrown.');
        } catch (InvalidArgumentException $e) {
            static::assertSame(
                'An unsupported scope was requested. Available scopes are openid, scope1, scope2.',
                $e->getMessage()
            );
        }
    }
}
