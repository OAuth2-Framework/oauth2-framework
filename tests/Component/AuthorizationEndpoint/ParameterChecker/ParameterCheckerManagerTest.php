<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Tests\Component\AuthorizationEndpoint\Tests\ParameterChecker;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\DisplayParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterCheckerManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\PromptParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\RedirectUriParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ResponseTypeParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\StateParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FragmentResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\QueryResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseTypeManager;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @group ParameterCheckerManager
 *
 * @internal
 */
final class ParameterCheckerManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var null|ParameterCheckerManager
     */
    private $parameterCheckerManager;

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButTheDisplayParameterIsNotValid()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $authorization = new AuthorizationRequest($client->reveal(), [
            'display' => 'foo',
        ]);

        try {
            $this->getParameterCheckerManager()->check($authorization);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('Invalid parameter "display". Allowed values are page, popup, touch, wap', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButThePromptParameterIsNotValid()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $authorization = new AuthorizationRequest($client->reveal(), [
            'prompt' => 'foo',
        ]);

        try {
            $this->getParameterCheckerManager()->check($authorization);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('Invalid parameter "prompt". Allowed values are none, login, consent, select_account', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButThePromptParameterNoneMustBeUsedAlone()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $authorization = new AuthorizationRequest($client->reveal(), [
            'prompt' => 'none login',
        ]);

        try {
            $this->getParameterCheckerManager()->check($authorization);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('Invalid parameter "prompt". Prompt value "none" must be used alone.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButNoRedirectUriIsSet()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $authorization = new AuthorizationRequest($client->reveal(), []);

        try {
            $this->getParameterCheckerManager()->check($authorization);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('The parameter "redirect_uri" is missing.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButNoResponseTypeIsSet()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->has('redirect_uris')->willReturn(true);
        $client->get('redirect_uris')->willReturn(['https://www.foo.bar/callback']);
        $authorization = new AuthorizationRequest($client->reveal(), [
            'redirect_uri' => 'https://www.foo.bar/callback',
        ]);

        try {
            $this->getParameterCheckerManager()->check($authorization);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('The parameter "response_type" is mandatory.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButTheResponseTypeIsNotSupportedByThisServer()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->has('redirect_uris')->willReturn(true);
        $client->get('redirect_uris')->willReturn(['https://www.foo.bar/callback']);
        $client->isResponseTypeAllowed('foo')->willReturn(true);
        $authorization = new AuthorizationRequest($client->reveal(), [
            'redirect_uri' => 'https://www.foo.bar/callback',
            'response_type' => 'bar',
        ]);

        try {
            $this->getParameterCheckerManager()->check($authorization);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('The response type "bar" is not supported by this server', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButTheResponseTypeIsNotAllowedForTheClient()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->has('redirect_uris')->willReturn(true);
        $client->get('redirect_uris')->willReturn(['https://www.foo.bar/callback']);
        $client->isResponseTypeAllowed('foo')->willReturn(false);
        $authorization = new AuthorizationRequest($client->reveal(), [
            'redirect_uri' => 'https://www.foo.bar/callback',
            'response_type' => 'foo',
        ]);

        try {
            $this->getParameterCheckerManager()->check($authorization);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('The response type "foo" is not allowed for this client.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedAndIsValid()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->has('redirect_uris')->willReturn(true);
        $client->get('redirect_uris')->willReturn(['https://www.foo.bar/callback']);
        $client->isResponseTypeAllowed('foo')->willReturn(true);
        $authorization = new AuthorizationRequest($client->reveal(), [
            'redirect_uri' => 'https://www.foo.bar/callback',
            'response_type' => 'foo',
            'state' => '0123456789',
            'prompt' => 'login consent',
            'display' => 'wap',
            'response_mode' => 'fragment',
        ]);

        $this->getParameterCheckerManager()->check($authorization);

        static::assertEquals(['login', 'consent'], $authorization->getPrompt());
        static::assertFalse($authorization->hasPrompt('none'));
    }

    private function getParameterCheckerManager(): ParameterCheckerManager
    {
        if (null === $this->parameterCheckerManager) {
            $responseType = $this->prophesize(ResponseType::class);
            $responseType->name()->willReturn('foo');
            $responseType->getResponseMode()->willReturn('query');
            $responseTypeManager = new ResponseTypeManager();
            $responseTypeManager->add($responseType->reveal());

            $responseModeManager = new ResponseModeManager();
            $responseModeManager->add(new QueryResponseMode());
            $responseModeManager->add(new FragmentResponseMode());

            $this->parameterCheckerManager = new ParameterCheckerManager();
            $this->parameterCheckerManager->add(new DisplayParameterChecker());
            $this->parameterCheckerManager->add(new PromptParameterChecker());
            $this->parameterCheckerManager->add(new RedirectUriParameterChecker());
            $this->parameterCheckerManager->add(new ResponseTypeParameterChecker($responseTypeManager));
            $this->parameterCheckerManager->add(new StateParameterChecker());
        }

        return $this->parameterCheckerManager;
    }
}
