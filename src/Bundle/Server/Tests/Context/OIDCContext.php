<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\Tests\Context;

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use Assert\Assertion;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Jose\JWTCreatorInterface;
use Jose\Loader;
use Jose\Object\JWSInterface;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\IdToken\IdTokenId;
use OAuth2Framework\Component\Server\Model\IdToken\IdTokenLoader;
use OAuth2Framework\Bundle\Server\Model\ClientRepository;

final class OIDCContext implements Context
{
    use KernelDictionary;

    /**
     * @var ResponseTypeContext
     */
    private $responseTypeContext;

    /**
     * @var MinkContext
     */
    private $minkContext;

    /**
     * @BeforeScenario
     *
     * @param BeforeScenarioScope $scope
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        $this->minkContext = $environment->getContext(MinkContext::class);
        $this->responseTypeContext = $environment->getContext(ResponseTypeContext::class);
    }

    /**
     * @When a client send a Userinfo request without access token
     */
    public function aClientSendAUserinfoRequestWithoutAccessToken()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/userinfo',
            [],
            [],
            []
        );
    }

    /**
     * @When a client sends a valid Userinfo request
     */
    public function aClientSendsAValidUserinfoRequest()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/userinfo',
            [],
            [],
            [
                'HTTP_Authorization' => 'Bearer VALID_ACCESS_TOKEN_FOR_USERINFO',
            ]
        );
    }

    /**
     * @Then the response contains an Id Token with the following claims for the client :clientId
     */
    public function theResponseContainsAnIdTokenWithTheFollowingClaimsForTheClient($clientId, PyStringNode $expectedClaims)
    {
        $client = $this->getContainer()->get(ClientRepository::class)->find(ClientId::create($clientId));
        Assertion::isInstanceOf($client, Client::class);
        $claims = json_decode($expectedClaims->getRaw(), true);
        $response = $this->minkContext->getSession()->getPage()->getContent();
        $loader = new Loader();
        $jwt = $loader->load($response);
        Assertion::isInstanceOf($jwt, JWSInterface::class);
        Assertion::true(empty(array_diff($claims, $jwt->getClaims())));
    }

    /**
     * @When a client sends a Userinfo request but the access token has no openid scope
     */
    public function aClientSendsAUserinfoRequestButTheAccessTokenHasNoOpenidScope()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/userinfo',
            [],
            [],
            [
                'HTTP_Authorization' => 'Bearer INVALID_ACCESS_TOKEN_FOR_USERINFO',
            ]
        );
    }

    /**
     * @When a client sends a Userinfo request but the access token has not been issued through the authorization endpoint
     */
    public function aClientSendsAUserinfoRequestButTheAccessTokenHasNotBeenIssuedThroughTheAuthorizationEndpoint()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/userinfo',
            [],
            [],
            [
                'HTTP_Authorization' => 'Bearer ACCESS_TOKEN_ISSUED_THROUGH_TOKEN_ENDPOINT',
            ]
        );
    }

    /**
     * @When A client sends a request to get the keys used by this authorization server
     */
    public function aClientSendsARequestToGetTheKeysUsedByThisAuthorizationServer()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/keys/public.jwkset',
            [],
            [],
            []
        );
    }

    /**
     * @When A client sends a request to get the Session Management iFrame
     */
    public function aClientSendsARequestToGetTheSessionManagementIframe()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/session/manager/iframe',
            [],
            [],
            []
        );
    }

    /**
     * @Given a client send a request to the metadata endpoint
     */
    public function aClientSendARequestToTheMetadataEndpoint()
    {
        throw new PendingException();
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/.well-known/openid-configuration',
            [],
            [],
            []
        );
    }

    /**
     * @Given A client sends an authorization request with max_age parameter but the user has to authenticate again
     */
    public function aClientSendsAnAuthorizationRequestWithMaxAgeParameterButTheUserHasToAuthenticateAgain()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->followRedirects(false);
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/authorize',
            [
                'client_id'     => 'client1',
                'redirect_uri'  => 'https://example.com/redirection/callback',
                'response_type' => 'code',
                'state'         => '0123456789',
                'max_age'       => '60',
            ],
            [],
            []
        );
    }

    /**
     * @Given A client sends an authorization request with max_age parameter
     */
    public function aClientSendsAnAuthorizationRequestWithMaxAgeParameter()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/authorize',
            [
                'client_id'     => 'client1',
                'redirect_uri'  => 'https://example.com/redirection/callback',
                'response_type' => 'code',
                'state'         => '0123456789',
                'max_age'       => '3600',
            ],
            [],
            []
        );
    }

    /**
     * @Given A client sends an authorization request with "prompt=none" parameter but the user has to authenticate again
     */
    public function aClientSendsAnAuthorizationRequestWithPromptNoneParameterButTheUserHasToAuthenticateAgain()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->followRedirects(false);
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/authorize',
            [
                'client_id'     => 'client1',
                'redirect_uri'  => 'https://example.com/redirection/callback',
                'response_type' => 'code',
                'state'         => '0123456789',
                'prompt'        => 'none',
            ],
            [],
            []
        );
    }

    /**
     * @Given A client sends an authorization request with "prompt=none consent" parameter
     */
    public function aClientSendsAnAuthorizationRequestWithPromptNoneConsentParameter()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->followRedirects(false);
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/authorize',
            [
                'client_id'     => 'client1',
                'redirect_uri'  => 'https://example.com/redirection/callback',
                'response_type' => 'code',
                'state'         => '0123456789',
                'prompt'        => 'none consent',
            ],
            [],
            []
        );
    }

    /**
     * @Given A client sends an authorization request with "prompt=login" parameter
     */
    public function aClientSendsAnAuthorizationRequestWithPromptLoginParameter()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->followRedirects(false);
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/authorize',
            [
                'client_id'     => 'client1',
                'redirect_uri'  => 'https://example.com/redirection/callback',
                'response_type' => 'code',
                'state'         => '0123456789',
                'prompt'        => 'login',
            ],
            [],
            []
        );
    }

    /**
     * @Given A client sends an authorization request that was already accepted and saved by the resource owner
     */
    public function aClientSendsAnAuthorizationRequestThatWasAlreadyAcceptedAndSavedByTheResourceOwner()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->followRedirects(false);
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/authorize',
            [
                'client_id'     => 'client1',
                'redirect_uri'  => 'https://example.com/redirection/callback',
                'response_type' => 'code',
                'state'         => '0123456789',
                'scope'         => 'openid profile phone address email',
            ],
            [],
            []
        );

    }

    /**
     * @Given A client sends an authorization request that was already accepted and saved by the resource owner with "prompt=consent"
     */
    public function aClientSendsAnAuthorizationRequestThatWasAlreadyAcceptedAndSavedByTheResourceOwnerWithPromptConsent()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/authorize',
            [
                'client_id'     => 'client1',
                'redirect_uri'  => 'https://example.com/redirection/callback',
                'response_type' => 'code',
                'state'         => '0123456789',
                'prompt'        => 'consent',
                'scope'         => 'openid profile phone address email',
            ],
            [],
            []
        );
    }

    /**
     * @Given A client sends an authorization request with ui_locales parameter and at least one locale is supported
     */
    public function aClientSendsAnAuthorizationRequestWithUiLocalesParameterAndAtLeastOneLocaleIsSupported()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/authorize',
            [
                'client_id'     => 'client1',
                'redirect_uri'  => 'https://example.com/redirection/callback',
                'response_type' => 'code',
                'state'         => '0123456789',
                'ui_locales'    => 'fr en',
            ],
            [],
            []
        );
    }

    /**
     * @Then the consent screen should be translated
     */
    public function theConsentScreenShouldBeTranslated()
    {
        $content = $this->minkContext->getSession()->getPage()->getContent();

        Assertion::contains($content, 'a besoin de votre autorisation pour accéder à vos resources.');
    }

    /**
     * @Given A client sends an authorization request with ui_locales parameter and none of them is supported
     */
    public function aClientSendsAnAuthorizationRequestWithUiLocalesParameterAndNoneOfThemIsSupported()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/authorize',
            [
                'client_id'     => 'client1',
                'redirect_uri'  => 'https://example.com/redirection/callback',
                'response_type' => 'code',
                'state'         => '0123456789',
                'ui_locales'    => 'ru de',
            ],
            [],
            []
        );
    }

    /**
     * @Then the consent screen should not be translated
     */
    public function theConsentScreenShouldNotBeTranslated()
    {
        $content = $this->minkContext->getSession()->getPage()->getContent();

        Assertion::contains($content, 'needs your authorization to get access on your resources.');
    }

    /**
     * @When A client sends a valid authorization request with a valid id_token_hint parameter
     */
    public function aClientSendsAValidAuthorizationRequestWithAValidIdTokenHintParameter()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/authorize',
            [
                'client_id'     => 'client1',
                'redirect_uri'  => 'https://example.com/redirection/callback',
                'response_type' => 'code',
                'state'         => '0123456789',
                'id_token_hint' => $this->generateValidIdToken(),
            ],
            [],
            []
        );
    }

    /**
     * @When A client sends a valid authorization request with a valid id_token_hint parameter but the current user does not correspond
     */
    public function aClientSendsAValidAuthorizationRequestWithAValidIdTokenHintParameterButTheCurrentUserDoesNotCorrespond()
    {
        $isFollowingRedirects = $this->minkContext->getSession()->getDriver()->getClient()->isFollowingRedirects();
        $this->minkContext->getSession()->getDriver()->getClient()->followRedirects(false);
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/authorize',
            [
                'client_id'     => 'client1',
                'redirect_uri'  => 'https://example.com/redirection/callback',
                'response_type' => 'code',
                'state'         => '0123456789',
                'id_token_hint' => $this->generateValidIdToken(),
            ],
            [],
            []
        );
        $this->minkContext->getSession()->getDriver()->getClient()->followRedirects($isFollowingRedirects);
    }

    /**
     * @When A client sends a valid authorization request with an invalid id_token_hint parameter
     */
    public function aClientSendsAValidAuthorizationRequestWithAnInvalidIdTokenHintParameter()
    {
        $isFollowingRedirects = $this->minkContext->getSession()->getDriver()->getClient()->isFollowingRedirects();
        $this->minkContext->getSession()->getDriver()->getClient()->followRedirects(false);
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/authorize',
            [
                'client_id'     => 'client1',
                'redirect_uri'  => 'https://example.com/redirection/callback',
                'response_type' => 'code',
                'state'         => '0123456789',
                'id_token_hint' => 'BAD_VALUE !!!!!!!!!!!!!!!!!!!!!!!!!!',
            ],
            [],
            []
        );
        $this->minkContext->getSession()->getDriver()->getClient()->followRedirects($isFollowingRedirects);
    }

    /**
     * @When A client sends a valid authorization request with a valid id_token_hint parameter but signed with an unsupported algorithm
     */
    public function aClientSendsAValidAuthorizationRequestWithAValidIdTokenHintParameterButSignedWithAnUnsupportedAlgorithm()
    {
        $isFollowingRedirects = $this->minkContext->getSession()->getDriver()->getClient()->isFollowingRedirects();
        $this->minkContext->getSession()->getDriver()->getClient()->followRedirects(false);
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/authorize',
            [
                'client_id'     => 'client1',
                'redirect_uri'  => 'https://example.com/redirection/callback',
                'response_type' => 'code',
                'state'         => '0123456789',
                'id_token_hint' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJub25lIiwia2lkIjoiS1JTV3dLcENFODFrb0hTa0ZwbFhBOTFnOTFkM253YTQ1MVdGWnd0enlGSGxDMHl4cjluaU13Ymd1NmFBY21yMkFkZ01GcU1Sd2phWlFXLXdYMURTTEEifQ.eyJuYW1lIjoiSm9obiBEb2UiLCJnaXZlbl9uYW1lIjoiSm9obiIsIm1pZGRsZV9uYW1lIjoiSmFjayIsImZhbWlseV9uYW1lIjoiRG9lIiwibmlja25hbWUiOiJMaXR0bGUgSm9obiIsInByZWZlcnJlZF91c2VybmFtZSI6ImotZCIsInByb2ZpbGUiOiJodHRwczpcL1wvcHJvZmlsZS5kb2UuZnJcL2pvaG5cLyIsInBpY3R1cmUiOiJodHRwczpcL1wvd3d3Lmdvb2dsZS5jb20iLCJ3ZWJzaXRlIjoiaHR0cHM6XC9cL2pvaG4uZG9lLmNvbSIsImdlbmRlciI6Ik0iLCJiaXJ0aGRhdGUiOiIxOTUwLTAxLTAxIiwiem9uZWluZm8iOiJFdXJvcGVcL1BhcmlzIiwibG9jYWxlIjoiZW4iLCJ1cGRhdGVkX2F0IjoxNDg1NDMxMjMyLCJlbWFpbCI6InJvb3RAbG9jYWxob3N0LmNvbSIsImVtYWlsX3ZlcmlmaWVkIjpmYWxzZSwicGhvbmVfbnVtYmVyIjoiKzAxMjM0NTY3ODkiLCJwaG9uZV9udW1iZXJfdmVyaWZpZWQiOnRydWUsInN1YiI6IlVncU80U0xjTnVwWUJYekdKNXVuQjR0SWY1UTlabzVHYXU1cDJ2QjJGbGZyQTZ2MU1YS09Ib2JvOS12STU1Q2kiLCJpYXQiOjE0ODk2NjU4MjAsIm5iZiI6MTQ4OTY2NTgyMCwiZXhwIjoxNDg5NjY5NDIwLCJqdGkiOiJBNllYZDM5MkdKSGRTZTl5dHhaNGc4ZUpORjg1c0pRdS13IiwiaXNzIjoiaHR0cHM6XC9cL3d3dy5teS1zZXJ2aWNlLmNvbSJ9.',
            ],
            [],
            []
        );
        $this->minkContext->getSession()->getDriver()->getClient()->followRedirects($isFollowingRedirects);
    }

    /**
     * @When a client that set userinfo algorithm parameters sends a valid Userinfo request
     */
    public function aClientThatSetUserinfoAlgorithmParametersSendsAValidUserinfoRequest()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/userinfo',
            [],
            [],
            [
                'HTTP_Authorization' => 'Bearer VALID_ACCESS_TOKEN_FOR_SIGNED_USERINFO',
            ]
        );
    }

    /**
     * @return string
     */
    private function generateValidIdToken(): string
    {
        $headers = [
            'typ' => 'JWT',
            'alg' => 'RS256',
            'kid' => 'KRSWwKpCE81koHSkFplXA91g91d3nwa451WFZwtzyFHlC0yxr9niMwbgu6aAcmr2AdgMFqMRwjaZQW-wX1DSLA',
        ];

        $payload = [
            'name' => 'John Doe',
            'given_name' => 'John',
            'middle_name' => 'Jack',
            'family_name' => 'Doe',
            'nickname' => 'Little John',
            'preferred_username' => 'j-d',
            'profile' => 'https://profile.doe.fr/john/',
            'picture' => 'https://www.google.com',
            'website' => 'https://john.doe.com',
            'gender' => 'M',
            'birthdate' => '1950-01-01',
            'zoneinfo' => 'Europe/Paris',
            'locale' => 'en',
            'updated_at' => time() - 10,
            'email' => 'root@localhost.com',
            'email_verified' => false,
            'phone_number' => '+0123456789',
            'phone_number_verified' => true,
            'sub' => 'UgqO4SLcNupYBXzGJ5unB4tIf5Q9Zo5Gau5p2vB2FlfrA6v1MXKOHobo9-vI55Ci',
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 1800,
            'jti' => 'A6YXd392GJHdSe9ytxZ4g8eJNF85sJQu-w',
            'iss' => 'https://www.my-service.com',
        ];

        $key = $this->getContainer()->get('oauth2_server.grant.id_token.key_set')->selectKey('sig', 'RS256');
        Assertion::notNull($key);

        return $this->getContainer()->get(JWTCreatorInterface::class)->sign($payload, $headers, $key);
    }
}
