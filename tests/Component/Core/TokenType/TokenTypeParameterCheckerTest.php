<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Core\TokenType;

use InvalidArgumentException;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\TokenType\TokenTypeGuesser;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;

/**
 * @internal
 */
final class TokenTypeParameterCheckerTest extends OAuth2TestCase
{
    private ?TokenTypeGuesser $tokenTypeGuesser = null;

    protected function setUp(): void
    {
        if (! class_exists(AuthorizationRequest::class)) {
            static::markTestSkipped('Authorization Endpoint not available');
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestWithNoTokenTypeParameterIsChecked(): void
    {
        $authorization = AuthorizationRequest::create(
            Client::create(ClientId::create('CLIENT_ID'), DataBag::create(), UserAccountId::create('john.1')),
            []
        );
        $tokenType = $this->getTokenTypeGuesser(true)
            ->find($authorization)
        ;

        static::assertInstanceOf(BearerToken::class, $tokenType);
    }

    /**
     * @test
     */
    public function anAuthorizationRequestWithTokenTypeParameterIsCheckedAndTheTokenTypeIsKnown(): void
    {
        $authorization = AuthorizationRequest::create(
            Client::create(ClientId::create('CLIENT_ID'), DataBag::create(), UserAccountId::create('john.1')),
            [
                'token_type' => 'Bearer',
            ]
        );
        $tokenType = $this->getTokenTypeGuesser(true)
            ->find($authorization)
        ;

        static::assertInstanceOf(BearerToken::class, $tokenType);
    }

    /**
     * @test
     */
    public function anAuthorizationRequestWithTokenTypeParameterIsCheckedButTheTokenTypeIsUnknown(): void
    {
        $authorization = AuthorizationRequest::create(
            Client::create(ClientId::create('CLIENT_ID'), DataBag::create(), UserAccountId::create('john.1')),
            [
                'token_type' => 'UnknownTokenType',
            ]
        );

        try {
            $this->getTokenTypeGuesser(true)
                ->find($authorization)
            ;
            static::fail('Expected exception nt thrown.');
        } catch (InvalidArgumentException $e) {
            static::assertSame('Unsupported token type "UnknownTokenType".', $e->getMessage());
        }
    }

    private function getTokenTypeGuesser(bool $tokenTypeParameterAllowed): TokenTypeGuesser
    {
        if ($this->tokenTypeGuesser === null) {
            $this->tokenTypeGuesser = TokenTypeGuesser::create(
                $this->getTokenTypeManager(),
                $tokenTypeParameterAllowed
            );
        }

        return $this->tokenTypeGuesser;
    }
}
