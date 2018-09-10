<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\AuthorizationEndpoint\Tests\ParameterChecker;

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\DisplayParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterCheckerManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\PromptParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\RedirectUriParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ResponseTypeAndResponseModeParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\StateParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FragmentResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\QueryResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseTypeManager;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;

/**
 * @group ParameterCheckerManager
 */
final class ParameterCheckerManagerTest extends TestCase
{
    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButTheDisplayParameterIsNotValid()
    {
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([]),
            null
        );
        $authorization = new Authorization($client, [
            'display' => 'foo',
        ]);

        try {
            $this->getParameterCheckerManager()->process($authorization);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('Invalid parameter "display". Allowed values are page, popup, touch, wap', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButThePromptParameterIsNotValid()
    {
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([]),
            null
        );
        $authorization = new Authorization($client, [
            'prompt' => 'foo',
        ]);

        try {
            $this->getParameterCheckerManager()->process($authorization);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('Invalid parameter "prompt". Allowed values are none, login, consent, select_account', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButThePromptParameterNoneMustBeUsedAlone()
    {
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([]),
            null
        );
        $authorization = new Authorization($client, [
            'prompt' => 'none login',
        ]);

        try {
            $this->getParameterCheckerManager()->process($authorization);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('Invalid parameter "prompt". Prompt value "none" must be used alone.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButNoRedirectUriIsSet()
    {
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([]),
            null
        );
        $authorization = new Authorization($client, []);

        try {
            $this->getParameterCheckerManager()->process($authorization);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('The parameter "redirect_uri" is mandatory.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButNoResponseTypeIsSet()
    {
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([]),
            null
        );
        $authorization = new Authorization($client, [
            'redirect_uri' => 'https://www.foo.bar/callback',
        ]);

        try {
            $this->getParameterCheckerManager()->process($authorization);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('The parameter "response_type" is mandatory.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButTheResponseModeIsNotSupported()
    {
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([
                'response_types' => ['foo'],
            ]),
            null
        );
        $authorization = new Authorization($client, [
            'redirect_uri' => 'https://www.foo.bar/callback',
            'response_type' => 'foo',
            'response_mode' => 'foo',
        ]);

        try {
            $this->getParameterCheckerManager()->process($authorization);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('The response mode "foo" is not supported. Please use one of the following values: query, fragment.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButTheResponseTypeIsNotSupportedByThisServer()
    {
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([]),
            null
        );
        $authorization = new Authorization($client, [
            'redirect_uri' => 'https://www.foo.bar/callback',
            'response_type' => 'bar',
        ]);

        try {
            $this->getParameterCheckerManager()->process($authorization);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('The response type "bar" is not supported by this server', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedButTheResponseTypeIsNotAllowedForTheClient()
    {
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([]),
            null
        );
        $authorization = new Authorization($client, [
            'redirect_uri' => 'https://www.foo.bar/callback',
            'response_type' => 'foo',
        ]);

        try {
            $this->getParameterCheckerManager()->process($authorization);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('The response type "foo" is not allowed for this client.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestIsReceivedAndIsValid()
    {
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([
                'response_types' => ['foo'],
            ]),
            null
        );
        $authorization = new Authorization($client, [
            'redirect_uri' => 'https://www.foo.bar/callback',
            'response_type' => 'foo',
            'state' => '0123456789',
            'prompt' => 'login consent',
            'display' => 'wap',
            'response_mode' => 'fragment',
        ]);

        $authorization = $this->getParameterCheckerManager()->process($authorization);

        static::assertInstanceOf(FragmentResponseMode::class, $authorization->getResponseMode());
        static::assertInstanceOf(ResponseType::class, $authorization->getResponseType());
        static::assertEquals(['login', 'consent'], $authorization->getPrompt());
        static::assertFalse($authorization->hasPrompt('none'));
    }

    /**
     * @var null|ParameterCheckerManager
     */
    private $extensionManager = null;

    private function getParameterCheckerManager(): ParameterCheckerManager
    {
        if (null === $this->extensionManager) {
            $responseType = $this->prophesize(ResponseType::class);
            $responseType->name()->willReturn('foo');
            $responseType->getResponseMode()->willReturn('query');
            $responseTypeManager = new ResponseTypeManager();
            $responseTypeManager->add($responseType->reveal());

            $responseModeManager = new ResponseModeManager();
            $responseModeManager->add(new QueryResponseMode());
            $responseModeManager->add(new FragmentResponseMode());

            $this->extensionManager = new ParameterCheckerManager();
            $this->extensionManager->add(new DisplayParameterChecker());
            $this->extensionManager->add(new PromptParameterChecker());
            $this->extensionManager->add(new RedirectUriParameterChecker());
            $this->extensionManager->add(new ResponseTypeAndResponseModeParameterChecker($responseTypeManager, $responseModeManager, true));
            $this->extensionManager->add(new StateParameterChecker());
        }

        return $this->extensionManager;
    }
}
