<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationCodeGrant;

use DateTimeImmutable;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeResponseType;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;
use OAuth2Framework\Tests\TestBundle\Entity\UserAccount;
use OAuth2Framework\Tests\TestBundle\Repository\AuthorizationCodeRepository;

/**
 * @internal
 */
final class AuthorizationCodeResponseTypeTest extends OAuth2TestCase
{
    private ?AuthorizationCodeResponseType $authorizationCodeGrantType = null;

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
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'token_endpoint_auth_method' => 'client_secret_jwt',
            ]),
            null
        );

        $userAccount = UserAccount::create(
            UserAccountId::create('USER_ACCOUNT_ID'),
            'username',
            [],
            new DateTimeImmutable('now -2 hours'),
            null,
            []
        );

        $tokenType = BearerToken::create('REALM');

        $authorization = AuthorizationRequest::create(
            $client,
            [
                'code_challenge' => 'ABCDEFGH',
                'code_challenge_method' => 'S256',
                'redirect_uri' => 'http://localhost:8000/',
            ]
        );
        $authorization->setUserAccount($userAccount);
        $this->getResponseType()
            ->preProcess($authorization)
        ;
        $this->getResponseType()
            ->process($authorization, $tokenType)
        ;
        static::assertTrue($authorization->hasResponseParameter('code'));
    }

    private function getResponseType(): AuthorizationCodeResponseType
    {
        if ($this->authorizationCodeGrantType === null) {
            $authorizationCodeRepository = new AuthorizationCodeRepository();
            $this->authorizationCodeGrantType = new AuthorizationCodeResponseType(
                $authorizationCodeRepository,
                30,
                $this->getPkceMethodManager(),
                false
            );
        }

        return $this->authorizationCodeGrantType;
    }
}
