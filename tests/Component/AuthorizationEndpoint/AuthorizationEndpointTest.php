<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationEndpoint;

use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationEndpoint;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\AuthorizationEndpoint\Hook\ConsentPrompt;
use OAuth2Framework\Component\AuthorizationEndpoint\Hook\LoginPrompt;
use OAuth2Framework\Component\AuthorizationEndpoint\Hook\NonePrompt;
use OAuth2Framework\Component\AuthorizationEndpoint\Hook\SelectAccountPrompt;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseModeGuesser;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseTypeGuesser;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Middleware\TerminalRequestHandler;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\TokenType\TokenTypeGuesser;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;
use OAuth2Framework\Tests\TestBundle\Entity\ResourceServer;
use OAuth2Framework\Tests\TestBundle\Entity\UserAccount;

/**
 * @internal
 */
final class AuthorizationEndpointTest extends OAuth2TestCase
{
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
        $client = Client::create(ClientId::create('CLIENT_ID'), DataBag::create(), UserAccountId::create('john.1'));
        $userAccount = UserAccount::create(UserAccountId::create('john.1'), 'john.1', [], null, null, []);
        $resourceServer = ResourceServer::create(ResourceServerId::create('RESOURCE_SERVER_ID'));
        $authorizationRequest = AuthorizationRequest::create($client, $params)
            ->setUserAccount($userAccount)
            ->setResponseParameter('foo', 'bar')
            ->setResponseHeader('X-FOO', 'bar')
            ->setResourceServer($resourceServer)
        ;
        $this->getAuthorizationRequestStorage()
            ->set('RANDOM_ID', $authorizationRequest)
        ;

        $request = $this->buildRequest()
            ->withAttribute('authorization_request_id', 'RANDOM_ID')
        ;

        $response = $this->getAuthorizationEndpoint()
            ->process($request, new TerminalRequestHandler(new Psr17Factory()))
        ;

        static::assertSame(303, $response->getStatusCode());
        static::assertSame(
            ['https://foo.bar/authorization/___ID___/select_account'],
            $response->getHeader('location')
        );

        $authorizationRequest->setAttribute('account_has_been_selected', true);

        $response = $this->getAuthorizationEndpoint()
            ->process($request, new TerminalRequestHandler(new Psr17Factory()))
        ;

        static::assertSame(303, $response->getStatusCode());
        static::assertSame(['https://foo.bar/authorization/___ID___/login'], $response->getHeader('location'));

        $authorizationRequest->setAttribute('user_has_been_authenticated', true);

        $response = $this->getAuthorizationEndpoint()
            ->process($request, new TerminalRequestHandler(new Psr17Factory()))
        ;

        static::assertSame(303, $response->getStatusCode());
        static::assertSame(['https://foo.bar/authorization/___ID___/consent'], $response->getHeader('location'));

        $authorizationRequest->deny();

        try {
            $this->getAuthorizationEndpoint()
                ->process($request, new TerminalRequestHandler(new Psr17Factory()))
            ;
        } catch (OAuth2AuthorizationException $exception) {
            static::assertSame('access_denied', $exception->getMessage());
            static::assertSame('The resource owner denied access to your client.', $exception->getErrorDescription());
        }
    }

    public function getAuthorizationEndpoint(): AuthorizationEndpoint
    {
        $endpoint = new AuthorizationEndpoint(
            new Psr17Factory(),
            new TokenTypeGuesser($this->getTokenTypeManager(), true),
            new ResponseTypeGuesser($this->getResponseTypeManager()),
            new ResponseModeGuesser($this->getResponseModeManager(), true),
            null,
            $this->getExtensionManager(),
            $this->getAuthorizationRequestStorage(),
            $this->getLoginHandler(),
            $this->getConsentHandler()
        );
        $endpoint->addHook(new NonePrompt(null));
        $endpoint->addHook(new SelectAccountPrompt($this->getSelectAccountHandler()));
        $endpoint->addHook(new LoginPrompt($this->getUserAuthenticationCheckerManager(), $this->getLoginHandler()));
        $endpoint->addHook(new ConsentPrompt($this->getConsentHandler()));

        return $endpoint;
    }
}
