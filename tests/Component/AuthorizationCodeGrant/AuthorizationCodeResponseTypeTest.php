<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationCodeGrant;

use DateTimeImmutable;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeResponseType;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\Plain;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\S256;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 */
final class AuthorizationCodeResponseTypeTest extends TestCase
{
    use ProphecyTrait;

    private ?AuthorizationCodeResponseType $grantType = null;

    private ?PKCEMethodManager $pkceMethodManager = null;

    /**
     * @test
     */
    public function genericInformation(): void
    {
        static::assertSame(['authorization_code'], $this->getResponseType()->associatedGrantTypes());
        static::assertSame('code', $this->getResponseType()->name());
        static::assertSame('query', $this->getResponseType()->getResponseMode());
    }

    /**
     * @test
     */
    public function theRequestHaveMissingParameters(): void
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

        $userAccount = $this->prophesize(UserAccount::class);
        $userAccount->getPublicId()
            ->willReturn(new UserAccountId('USER_ACCOUNT_ID'))
        ;
        $userAccount->getUserAccountId()
            ->willReturn(new UserAccountId('USER_ACCOUNT_ID'))
        ;

        $tokenType = $this->prophesize(TokenType::class);
        $tokenType->getAdditionalInformation()
            ->willReturn([
                'token_type' => 'FOO',
            ])
        ;

        $authorization = new AuthorizationRequest(
            $client->reveal(),
            [
                'code_challenge' => 'ABCDEFGH',
                'code_challenge_method' => 'S256',
                'redirect_uri' => 'http://localhost:8000/',
            ]
        );
        $authorization->setUserAccount($userAccount->reveal(), true);
        $this->getResponseType()
            ->preProcess($authorization)
        ;
        $this->getResponseType()
            ->process($authorization, $tokenType->reveal())
        ;
        static::assertTrue($authorization->hasResponseParameter('code'));
    }

    private function getResponseType(): AuthorizationCodeResponseType
    {
        if ($this->grantType === null) {
            $authorizationCodeRepository = $this->prophesize(AuthorizationCodeRepository::class);
            $authorizationCodeRepository->create(
                Argument::type(ClientId::class),
                Argument::type(UserAccountId::class),
                Argument::type('array'),
                Argument::type('string'),
                Argument::type(DateTimeImmutable::class),
                Argument::type(DataBag::class),
                Argument::type(DataBag::class),
                Argument::any()
            )->will(
                function (array $args) {
                    return new AuthorizationCode(
                        new AuthorizationCodeId(bin2hex(random_bytes(32))),
                        $args[0],
                        $args[1],
                        $args[2],
                        $args[3],
                        $args[4],
                        $args[5],
                        $args[6],
                        $args[7]
                    );
                }
            );
            $authorizationCodeRepository->save(Argument::type(AuthorizationCode::class))->will(function (array $args) {
            });

            $this->grantType = new AuthorizationCodeResponseType(
                $authorizationCodeRepository->reveal(),
                30,
                $this->getPkceMethodManager(),
                false
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
}
