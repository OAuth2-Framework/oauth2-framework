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

namespace OAuth2Framework\Component\AuthorizationEndpoint\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationEndpoint;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequestStorage;
use OAuth2Framework\Component\AuthorizationEndpoint\ConsentHandler;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\AuthorizationEndpoint\Extension\ExtensionManager;
use OAuth2Framework\Component\AuthorizationEndpoint\Hook\ConsentPrompt;
use OAuth2Framework\Component\AuthorizationEndpoint\Hook\LoginPrompt;
use OAuth2Framework\Component\AuthorizationEndpoint\Hook\NonePrompt;
use OAuth2Framework\Component\AuthorizationEndpoint\Hook\SelectAccountPrompt;
use OAuth2Framework\Component\AuthorizationEndpoint\LoginHandler;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\DisplayParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterCheckerManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\PromptParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\RedirectUriParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ResponseTypeParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\StateParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FragmentResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\QueryResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseModeGuesser;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseTypeManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseTypeGuesser;
use OAuth2Framework\Component\AuthorizationEndpoint\SelectAccountHandler;
use OAuth2Framework\Component\AuthorizationEndpoint\User\MaxAgeParameterAuthenticationChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAuthenticationCheckerManager;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServer;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\Core\TokenType\TokenTypeGuesser;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @group AuthorizationEndpoint
 * @group ResponseTypeManager
 *
 * @internal
 * @coversNothing
 */
final class AuthorizationEndpointTest extends TestCase
{
    /**
     * @var null|TokenTypeManager
     */
    private $tokenTypeManager;

    /**
     * @var null|ResponseTypeManager
     */
    private $responseTypeManager;

    /**
     * @var null|ResponseModeManager
     */
    private $responseModeManager;

    /**
     * @var null|ParameterCheckerManager
     */
    private $parameterCheckerManager;

    /**
     * @var null|ExtensionManager
     */
    private $extensionManager;

    /**
     * @var null|LoginHandler
     */
    private $loginHandler;

    /**
     * @var null|ConsentHandler
     */
    private $consentHandler;

    /**
     * @var null|SelectAccountHandler
     */
    private $selectAccountHandler;

    /**
     * @var null|UserAuthenticationCheckerManager
     */
    private $userAuthenticationCheckerManager;

    /**
     * @test
     */
    public function aClientAsksForConsentLoginAndAccountSelectionButResourceOwnerRefusedTheDelegation()
    {
        $params = [
            'prompt' => 'consent login select_account',
            'ui_locales' => 'fr en',
            'scope' => 'scope1 scope2',
            'redirect_uri' => 'https://localhost',
        ];
        $client = $this->prophesize(Client::class);
        $client->has('default_max_age')->willReturn(false);
        $userAccount = $this->prophesize(UserAccount::class);
        $resourceServer = $this->prophesize(ResourceServer::class);
        $authorizationRequest = new AuthorizationRequest($client->reveal(), $params);

        $authorizationRequest->setUserAccount($userAccount->reveal());
        $authorizationRequest->setResponseParameter('foo', 'bar');
        $authorizationRequest->setResponseHeader('X-FOO', 'bar');
        $authorizationRequest->setResourceServer($resourceServer->reveal());

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $request = $this->prophesize(ServerRequestInterface::class);

        $response = $this->getAuthorizationEndpoint($authorizationRequest)->process($request->reveal(), $handler->reveal());

        static::assertEquals(303, $response->getStatusCode());
        static::assertEquals(['https://foo.bar/authorization/___ID___/select_account'], $response->getHeader('location'));

        $authorizationRequest->setAttribute('account_has_been_selected', true);

        $response = $this->getAuthorizationEndpoint($authorizationRequest)->process($request->reveal(), $handler->reveal());

        static::assertEquals(303, $response->getStatusCode());
        static::assertEquals(['https://foo.bar/authorization/___ID___/login'], $response->getHeader('location'));

        $authorizationRequest->setAttribute('user_has_been_authenticated', true);

        $response = $this->getAuthorizationEndpoint($authorizationRequest)->process($request->reveal(), $handler->reveal());

        static::assertEquals(303, $response->getStatusCode());
        static::assertEquals(['https://foo.bar/authorization/___ID___/consent'], $response->getHeader('location'));

        $authorizationRequest->deny();

        try {
            $this->getAuthorizationEndpoint($authorizationRequest)->process($request->reveal(), $handler->reveal());
        } catch (OAuth2AuthorizationException $exception) {
            static::assertEquals('access_denied', $exception->getMessage());
            static::assertEquals('The resource owner denied access to your client.', $exception->getErrorDescription());
        }
    }

    /**
     * @return AuthorizationEndpoint
     */
    public function getAuthorizationEndpoint(AuthorizationRequest $authorizationRequest): AuthorizationEndpoint
    {
        $endpoint = new AuthorizationEndpoint(
            new Psr17Factory(),
            new TokenTypeGuesser($this->getTokenTypeManager(), true),
            new ResponseTypeGuesser($this->getResponseTypeManager()),
            new ResponseModeGuesser($this->getResponseModeManager(), true),
            null,
            $this->getExtensionManager(),
            $this->getAuthorizationRequestStorage($authorizationRequest),
            $this->getLoginHandler(),
            $this->getConsentHandler()
        );
        $endpoint->addHook(new NonePrompt(null));
        $endpoint->addHook(new SelectAccountPrompt($this->getSelectAccountHandler()));
        $endpoint->addHook(new LoginPrompt($this->getUserAuthenticationCheckerManager(), $this->getLoginHandler()));
        $endpoint->addHook(new ConsentPrompt($this->getConsentHandler()));

        return $endpoint;
    }

    /**
     * @return TokenTypeManager
     */
    public function getTokenTypeManager(): TokenTypeManager
    {
        if (null === $this->tokenTypeManager) {
            $tokenType = $this->prophesize(TokenType::class);
            $tokenType->name()->willReturn('bearer');

            $this->tokenTypeManager = new TokenTypeManager();
            $this->tokenTypeManager->add($tokenType->reveal());
        }

        return $this->tokenTypeManager;
    }

    /**
     * @return ResponseTypeManager
     */
    public function getResponseTypeManager(): ResponseTypeManager
    {
        if (null === $this->responseTypeManager) {
            $responseType = $this->prophesize(ResponseType::class);
            $responseType->name()->willReturn('foo');
            $responseType->getResponseMode()->willReturn('query');
            $responseTypeManager = new ResponseTypeManager();
            $responseTypeManager->add($responseType->reveal());

            $this->responseTypeManager = new ResponseTypeManager();
            $this->responseTypeManager->add($responseType->reveal());
        }

        return $this->responseTypeManager;
    }

    /**
     * @return ResponseModeManager
     */
    public function getResponseModeManager(): ResponseModeManager
    {
        if (null === $this->responseModeManager) {
            $this->responseModeManager = new ResponseModeManager();
            $this->responseModeManager->add(new QueryResponseMode());
            $this->responseModeManager->add(new FragmentResponseMode());
        }

        return $this->responseModeManager;
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

    private function getExtensionManager(): ExtensionManager
    {
        if (null === $this->extensionManager) {
            $this->extensionManager = new ExtensionManager();
        }

        return $this->extensionManager;
    }

    private function getAuthorizationRequestStorage(AuthorizationRequest $authorizationRequest): AuthorizationRequestStorage
    {
        $authorizationRequestStorage = $this->prophesize(AuthorizationRequestStorage::class);
        $authorizationRequestStorage->getId(Argument::type(ServerRequestInterface::class))->willReturn('___ID___');
        $authorizationRequestStorage->get(Argument::containingString('___ID___'))->willReturn($authorizationRequest);
        $authorizationRequestStorage->has(Argument::containingString('___ID___'))->willReturn(true);
        $authorizationRequestStorage->set(Argument::containingString('___ID___'), Argument::type(AuthorizationRequest::class))->will(function () {});

        return $authorizationRequestStorage->reveal();
    }

    private function getLoginHandler(): LoginHandler
    {
        if (null === $this->loginHandler) {
            $response = $this->prophesize(ResponseInterface::class);
            $response->getStatusCode()->willReturn(303);
            $response->getHeader('location')->willReturn(['https://foo.bar/authorization/___ID___/login']);

            $this->loginHandler = $this->prophesize(LoginHandler::class);
            $this->loginHandler->handle(Argument::type(ServerRequestInterface::class), '___ID___')->willReturn($response->reveal());
        }

        return $this->loginHandler->reveal();
    }

    private function getConsentHandler(): ConsentHandler
    {
        if (null === $this->consentHandler) {
            $response = $this->prophesize(ResponseInterface::class);
            $response->getStatusCode()->willReturn(303);
            $response->getHeader('location')->willReturn(['https://foo.bar/authorization/___ID___/consent']);

            $this->consentHandler = $this->prophesize(ConsentHandler::class);
            $this->consentHandler->handle(Argument::type(ServerRequestInterface::class), '___ID___')->willReturn($response->reveal());
        }

        return $this->consentHandler->reveal();
    }

    private function getSelectAccountHandler(): SelectAccountHandler
    {
        if (null === $this->selectAccountHandler) {
            $response = $this->prophesize(ResponseInterface::class);
            $response->getStatusCode()->willReturn(303);
            $response->getHeader('location')->willReturn(['https://foo.bar/authorization/___ID___/select_account']);

            $this->selectAccountHandler = $this->prophesize(SelectAccountHandler::class);
            $this->selectAccountHandler->handle(Argument::type(ServerRequestInterface::class), '___ID___')->willReturn($response->reveal());
        }

        return $this->selectAccountHandler->reveal();
    }

    private function getUserAuthenticationCheckerManager(): UserAuthenticationCheckerManager
    {
        if (null === $this->userAuthenticationCheckerManager) {
            $this->userAuthenticationCheckerManager = new UserAuthenticationCheckerManager();
            $this->userAuthenticationCheckerManager->add(new MaxAgeParameterAuthenticationChecker());
        }

        return $this->userAuthenticationCheckerManager;
    }
}
