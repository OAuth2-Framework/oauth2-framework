<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationEndpoint\ParameterChecker;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;

/**
 * @internal
 */
final class ParameterCheckerManagerTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButTheDisplayParameterIsNotValid(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('john.1')
        );
        $authorization = AuthorizationRequest::create($client, [
            'display' => 'foo',
        ]);

        try {
            $this->getParameterCheckerManager()
                ->check($authorization)
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertSame('invalid_request', $e->getMessage());
            static::assertSame(
                'Invalid parameter "display". Allowed values are page, popup, touch, wap',
                $e->getErrorDescription()
            );
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButThePromptParameterIsNotValid(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('john.1')
        );
        $authorization = AuthorizationRequest::create($client, [
            'prompt' => 'foo',
        ]);

        try {
            $this->getParameterCheckerManager()
                ->check($authorization)
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertSame('invalid_request', $e->getMessage());
            static::assertSame(
                'Invalid parameter "prompt". Allowed values are none, login, consent, select_account',
                $e->getErrorDescription()
            );
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButThePromptParameterNoneMustBeUsedAlone(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('john.1')
        );
        $authorization = AuthorizationRequest::create($client, [
            'prompt' => 'none login',
        ]);

        try {
            $this->getParameterCheckerManager()
                ->check($authorization)
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertSame('invalid_request', $e->getMessage());
            static::assertSame(
                'Invalid parameter "prompt". Prompt value "none" must be used alone.',
                $e->getErrorDescription()
            );
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButNoRedirectUriIsSet(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('john.1')
        );
        $authorization = AuthorizationRequest::create($client, []);

        try {
            $this->getParameterCheckerManager()
                ->check($authorization)
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertSame('invalid_request', $e->getMessage());
            static::assertSame('The parameter "redirect_uri" is missing.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButNoResponseTypeIsSet(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'redirect_uris' => ['https://www.foo.bar/callback'],
                'response_types' => ['code'],
            ]),
            UserAccountId::create('john.1')
        );

        $authorization = AuthorizationRequest::create($client, [
            'redirect_uri' => 'https://www.foo.bar/callback',
        ]);

        try {
            $this->getParameterCheckerManager()
                ->check($authorization)
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertSame('invalid_request', $e->getMessage());
            static::assertSame('The parameter "response_type" is mandatory.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButTheResponseTypeIsNotSupportedByThisServer(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'redirect_uris' => ['https://www.foo.bar/callback'],
                'response_types' => ['code'],
            ]),
            UserAccountId::create('john.1')
        );

        $authorization = AuthorizationRequest::create($client, [
            'redirect_uri' => 'https://www.foo.bar/callback',
            'response_type' => 'bar',
        ]);

        try {
            $this->getParameterCheckerManager()
                ->check($authorization)
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertSame('invalid_request', $e->getMessage());
            static::assertSame('The response type "bar" is not supported by this server', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButTheResponseTypeIsNotAllowedForTheClient(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'redirect_uris',
                ['https://www.foo.bar/callback'],
                'response_types' => [],
            ]),
            UserAccountId::create('john.1')
        );

        $authorization = AuthorizationRequest::create($client, [
            'redirect_uri' => 'https://www.foo.bar/callback',
            'response_type' => 'code',
        ]);

        try {
            $this->getParameterCheckerManager()
                ->check($authorization)
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertSame('invalid_request', $e->getMessage());
            static::assertSame('The response type "code" is not allowed for this client.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedAndIsValid(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'redirect_uris' => ['https://www.foo.bar/callback'],
                'response_types' => ['code'],
            ]),
            UserAccountId::create('john.1')
        );

        $authorization = AuthorizationRequest::create($client, [
            'redirect_uri' => 'https://www.foo.bar/callback',
            'response_type' => 'code',
            'state' => '0123456789',
            'prompt' => 'login consent',
            'display' => 'wap',
            'response_mode' => 'fragment',
        ]);

        $this->getParameterCheckerManager()
            ->check($authorization)
        ;

        static::assertSame(['login', 'consent'], $authorization->getPrompt());
        static::assertFalse($authorization->hasPrompt('none'));
    }
}
