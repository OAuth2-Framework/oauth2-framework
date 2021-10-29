<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientAuthentication;

use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethod;
use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\ClientAuthentication\ClientSecretBasic;
use OAuth2Framework\Component\ClientAuthentication\ClientSecretPost;
use OAuth2Framework\Component\ClientAuthentication\None;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @internal
 */
final class AuthenticationMethodManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function genericCalls(): void
    {
        $manager = new AuthenticationMethodManager();
        $manager->add(new None());
        $manager->add(new ClientSecretBasic('Realm'));
        static::assertTrue($manager->has('none'));
        static::assertSame(['none', 'client_secret_basic'], $manager->list());
        static::assertInstanceOf(AuthenticationMethod::class, $manager->get('none'));
        static::assertCount(2, $manager->all());
        static::assertSame(['Basic realm="Realm",charset="UTF-8"'], $manager->getSchemesParameters());
    }

    /**
     * @test
     */
    public function theClientCannotUseSeveralAuthenticationMethods(): void
    {
        $manager = new AuthenticationMethodManager();
        $manager->add(new ClientSecretBasic('My Service'));
        $manager->add(new ClientSecretPost());
        $request = $this->buildRequest([
            'client_id' => 'CLIENT_ID',
            'client_secret' => 'CLIENT_SECRET',
        ]);
        $request->getHeader('Authorization')
            ->willReturn(['Basic ' . base64_encode('CLIENT_ID:CLIENT_SECRET')])
        ;

        try {
            $manager->findClientIdAndCredentials($request->reveal(), $method, $credentials);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_request',
                'error_description' => 'Only one authentication method may be used to authenticate the client.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theClientCanUseSeveralAuthenticationMethodsWhenOneIsNone(): void
    {
        $manager = new AuthenticationMethodManager();
        $manager->add(new None());
        $manager->add(new ClientSecretPost());
        $request = $this->buildRequest([
            'client_id' => 'CLIENT_ID',
            'client_secret' => 'CLIENT_SECRET',
        ]);

        $clientId = $manager->findClientIdAndCredentials($request->reveal(), $method, $credentials);
        static::assertInstanceOf(ClientSecretPost::class, $method);
        static::assertInstanceOf(ClientId::class, $clientId);
        static::assertSame('CLIENT_SECRET', $credentials);
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
