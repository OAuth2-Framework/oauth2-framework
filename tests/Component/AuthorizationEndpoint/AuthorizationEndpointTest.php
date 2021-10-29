<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationEndpoint;

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
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 */
final class AuthorizationEndpointTest extends TestCase
{
    use ProphecyTrait;

    private ?TokenTypeManager $tokenTypeManager = null;

    private ?ResponseTypeManager $responseTypeManager = null;

    private ?ResponseModeManager $responseModeManager = null;

    private ?ExtensionManager $extensionManager = null;

    private ?ObjectProphecy $loginHandler = null;

    private ?ObjectProphecy $consentHandler = null;

    private ?ObjectProphecy $selectAccountHandler = null;

    private ?UserAuthenticationCheckerManager $userAuthenticationCheckerManager = null;

    /**
     * @test
     */
    public function aClientAsksForConsentLoginAndAccountSelectionButResourceOwnerRefusedTheDelegation(): void
    {
        $params = [
            'prompt' => 'consent login select_account',
            'ui_locales' => 'fr en',
            'scope' => 'scope1 scope2',
            'redirect_uri' => 'https://localhost',
        ];
        $client = $this->prophesize(Client::class);
        $client->has('default_max_age')
            ->willReturn(false)
        ;
        $userAccount = $this->prophesize(UserAccount::class);
        $resourceServer = $this->prophesize(ResourceServer::class);
        $authorizationRequest = new AuthorizationRequest($client->reveal(), $params);

        $authorizationRequest->setUserAccount($userAccount->reveal());
        $authorizationRequest->setResponseParameter('foo', 'bar');
        $authorizationRequest->setResponseHeader('X-FOO', 'bar');
        $authorizationRequest->setResourceServer($resourceServer->reveal());

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $request = $this->prophesize(ServerRequestInterface::class);

        $response = $this->getAuthorizationEndpoint($authorizationRequest)
            ->process($request->reveal(), $handler->reveal())
        ;

        static::assertSame(303, $response->getStatusCode());
        static::assertSame(
            ['https://foo.bar/authorization/___ID___/select_account'],
            $response->getHeader('location')
        );

        $authorizationRequest->setAttribute('account_has_been_selected', true);

        $response = $this->getAuthorizationEndpoint($authorizationRequest)
            ->process($request->reveal(), $handler->reveal())
        ;

        static::assertSame(303, $response->getStatusCode());
        static::assertSame(['https://foo.bar/authorization/___ID___/login'], $response->getHeader('location'));

        $authorizationRequest->setAttribute('user_has_been_authenticated', true);

        $response = $this->getAuthorizationEndpoint($authorizationRequest)
            ->process($request->reveal(), $handler->reveal())
        ;

        static::assertSame(303, $response->getStatusCode());
        static::assertSame(['https://foo.bar/authorization/___ID___/consent'], $response->getHeader('location'));

        $authorizationRequest->deny();

        try {
            $this->getAuthorizationEndpoint($authorizationRequest)
                ->process($request->reveal(), $handler->reveal())
            ;
        } catch (OAuth2AuthorizationException $exception) {
            static::assertSame('access_denied', $exception->getMessage());
            static::assertSame('The resource owner denied access to your client.', $exception->getErrorDescription());
        }
    }

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

    public function getTokenTypeManager(): TokenTypeManager
    {
        if ($this->tokenTypeManager === null) {
            $tokenType = $this->prophesize(TokenType::class);
            $tokenType->name()
                ->willReturn('bearer')
            ;

            $this->tokenTypeManager = new TokenTypeManager();
            $this->tokenTypeManager->add($tokenType->reveal());
        }

        return $this->tokenTypeManager;
    }

    public function getResponseTypeManager(): ResponseTypeManager
    {
        if ($this->responseTypeManager === null) {
            $responseType = $this->prophesize(ResponseType::class);
            $responseType->name()
                ->willReturn('foo')
            ;
            $responseType->getResponseMode()
                ->willReturn('query')
            ;
            $responseTypeManager = new ResponseTypeManager();
            $responseTypeManager->add($responseType->reveal());

            $this->responseTypeManager = new ResponseTypeManager();
            $this->responseTypeManager->add($responseType->reveal());
        }

        return $this->responseTypeManager;
    }

    public function getResponseModeManager(): ResponseModeManager
    {
        if ($this->responseModeManager === null) {
            $this->responseModeManager = new ResponseModeManager();
            $this->responseModeManager->add(new QueryResponseMode());
            $this->responseModeManager->add(new FragmentResponseMode());
        }

        return $this->responseModeManager;
    }

    private function getExtensionManager(): ExtensionManager
    {
        if ($this->extensionManager === null) {
            $this->extensionManager = new ExtensionManager();
        }

        return $this->extensionManager;
    }

    private function getAuthorizationRequestStorage(
        AuthorizationRequest $authorizationRequest
    ): AuthorizationRequestStorage {
        $authorizationRequestStorage = $this->prophesize(AuthorizationRequestStorage::class);
        $authorizationRequestStorage->getId(Argument::type(ServerRequestInterface::class))->willReturn('___ID___');
        $authorizationRequestStorage->get(Argument::containingString('___ID___'))->willReturn($authorizationRequest);
        $authorizationRequestStorage->has(Argument::containingString('___ID___'))->willReturn(true);
        $authorizationRequestStorage->remove(Argument::containingString('___ID___'))->will(function () {});
        $authorizationRequestStorage->set(
            Argument::containingString('___ID___'),
            Argument::type(AuthorizationRequest::class)
        )->will(function () {});

        return $authorizationRequestStorage->reveal();
    }

    private function getLoginHandler(): LoginHandler
    {
        if ($this->loginHandler === null) {
            $response = $this->prophesize(ResponseInterface::class);
            $response->getStatusCode()
                ->willReturn(303)
            ;
            $response->getHeader('location')
                ->willReturn(['https://foo.bar/authorization/___ID___/login'])
            ;

            $this->loginHandler = $this->prophesize(LoginHandler::class);
            $this->loginHandler->handle(Argument::type(ServerRequestInterface::class), '___ID___')->willReturn(
                $response->reveal()
            );
        }

        return $this->loginHandler->reveal();
    }

    private function getConsentHandler(): ConsentHandler
    {
        if ($this->consentHandler === null) {
            $response = $this->prophesize(ResponseInterface::class);
            $response->getStatusCode()
                ->willReturn(303)
            ;
            $response->getHeader('location')
                ->willReturn(['https://foo.bar/authorization/___ID___/consent'])
            ;

            $this->consentHandler = $this->prophesize(ConsentHandler::class);
            $this->consentHandler->handle(Argument::type(ServerRequestInterface::class), '___ID___')->willReturn(
                $response->reveal()
            );
        }

        return $this->consentHandler->reveal();
    }

    private function getSelectAccountHandler(): SelectAccountHandler
    {
        if ($this->selectAccountHandler === null) {
            $response = $this->prophesize(ResponseInterface::class);
            $response->getStatusCode()
                ->willReturn(303)
            ;
            $response->getHeader('location')
                ->willReturn(['https://foo.bar/authorization/___ID___/select_account'])
            ;

            $this->selectAccountHandler = $this->prophesize(SelectAccountHandler::class);
            $this->selectAccountHandler->handle(Argument::type(ServerRequestInterface::class), '___ID___')->willReturn(
                $response->reveal()
            );
        }

        return $this->selectAccountHandler->reveal();
    }

    private function getUserAuthenticationCheckerManager(): UserAuthenticationCheckerManager
    {
        if ($this->userAuthenticationCheckerManager === null) {
            $this->userAuthenticationCheckerManager = new UserAuthenticationCheckerManager();
            $this->userAuthenticationCheckerManager->add(new MaxAgeParameterAuthenticationChecker());
        }

        return $this->userAuthenticationCheckerManager;
    }
}
