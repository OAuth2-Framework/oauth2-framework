<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationCodeGrant;

use DateTimeImmutable;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeGrantType;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\Plain;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\S256;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @internal
 */
final class AuthorizationCodeGrantTypeTest extends TestCase
{
    use ProphecyTrait;

    private ?AuthorizationCodeGrantType $grantType = null;

    private ?PKCEMethodManager $pkceMethodManager = null;

    /**
     * @test
     */
    public function genericInformation(): void
    {
        static::assertSame(['code'], $this->getGrantType()->associatedResponseTypes());
        static::assertSame('authorization_code', $this->getGrantType()->name());
    }

    /**
     * @test
     */
    public function theRequestHaveMissingParameters(): void
    {
        $request = $this->buildRequest([]);

        try {
            $this->getGrantType()
                ->checkRequest($request->reveal())
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_request',
                'error_description' => 'Missing grant type parameter(s): code, redirect_uri.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theRequestHaveAllRequiredParameters(): void
    {
        $request = $this->buildRequest([
            'code' => 'AUTHORIZATION_CODE_ID',
            'redirect_uri' => 'http://localhost:8000/',
        ]);

        $this->getGrantType()
            ->checkRequest($request->reveal())
        ;
        static::assertTrue(true);
    }

    /**
     * @test
     */
    public function theTokenResponseIsCorrectlyPrepared(): void
    {
        $client = $this->prophesize(Client::class);
        $request = $this->buildRequest([
            'code' => 'AUTHORIZATION_CODE_ID',
            'redirect_uri' => 'http://localhost:8000/',
        ]);
        $grantTypeData = new GrantTypeData($client->reveal());

        $this->getGrantType()
            ->prepareResponse($request->reveal(), $grantTypeData)
        ;
        static::assertSame($grantTypeData, $grantTypeData);
    }

    /**
     * @test
     */
    public function theGrantTypeCannotGrantTheClientAsTheCodeVerifierIsMissing(): void
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()
            ->willReturn(false)
        ;
        $client->getPublicId()
            ->willReturn(new ClientId('CLIENT_ID'))
        ;

        $request = $this->buildRequest([
            'code' => 'AUTHORIZATION_CODE_ID',
            'redirect_uri' => 'http://localhost:8000/',
        ]);
        $request->getAttribute('client')
            ->willReturn($client)
        ;
        $grantTypeData = new GrantTypeData($client->reveal());

        try {
            $this->getGrantType()
                ->grant($request->reveal(), $grantTypeData)
            ;
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_grant',
                'error_description' => 'The parameter "code_verifier" is missing or invalid.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theGrantTypeCanGrantTheClient(): void
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()
            ->willReturn(false)
        ;
        $client->getPublicId()
            ->willReturn(new ClientId('CLIENT_ID'))
        ;

        $request = $this->buildRequest([
            'code' => 'AUTHORIZATION_CODE_ID',
            'redirect_uri' => 'http://localhost:8000/',
            'code_verifier' => 'ABCDEFGH',
        ]);
        $request->getAttribute('client')
            ->willReturn($client)
        ;
        $grantTypeData = new GrantTypeData($client->reveal());

        $this->getGrantType()
            ->grant($request->reveal(), $grantTypeData)
        ;
        static::assertSame('USER_ACCOUNT_ID', $grantTypeData->getResourceOwnerId()->getValue());
        static::assertSame('CLIENT_ID', $grantTypeData->getClient()->getPublicId()->getValue());
    }

    private function getGrantType(): AuthorizationCodeGrantType
    {
        if ($this->grantType === null) {
            $authorizationCode = new AuthorizationCode(
                new AuthorizationCodeId('AUTHORIZATION_CODE_ID'),
                new ClientId('CLIENT_ID'),
                new UserAccountId('USER_ACCOUNT_ID'),
                [
                    'code_challenge' => 'ABCDEFGH',
                    'code_challenge_method' => 'plain',
                ],
                'http://localhost:8000/',
                new DateTimeImmutable('now +1 day'),
                new DataBag([
                    'scope' => 'scope1 scope2',
                ]),
                new DataBag([]),
                new ResourceServerId('RESOURCE_SERVER_ID')
            );
            $authorizationCodeRepository = $this->prophesize(AuthorizationCodeRepository::class);
            $authorizationCodeRepository->find(new AuthorizationCodeId('AUTHORIZATION_CODE_ID'))
                ->willReturn($authorizationCode)
            ;
            $authorizationCodeRepository->save(Argument::type(AuthorizationCode::class))->will(function (array $args) {
            });

            $this->grantType = new AuthorizationCodeGrantType(
                $authorizationCodeRepository->reveal(),
                $this->getPkceMethodManager()
            );
        }

        return $this->grantType;
    }

    private function getPkceMethodManager(): PKCEMethodManager
    {
        if ($this->pkceMethodManager === null) {
            $this->pkceMethodManager = new PKCEMethodManager();
            $this->pkceMethodManager->add(new Plain());
            $this->pkceMethodManager->add(new S256());
        }

        return $this->pkceMethodManager;
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
