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
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\Converter\StandardJsonConverter;
use Jose\Component\Core\JWKSet;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\None;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use OAuth2Framework\Bundle\Server\Tests\TestBundle\Listener;

/**
 * Defines application features from the specific context.
 */
final class ClientContext implements Context
{
    use KernelDictionary;

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
    }

    /**
     * @var null|array
     */
    private $client = null;

    /**
     * @Given a valid client registration request is received
     */
    public function aValidClientRegistrationRequestIsReceived()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/client/management', [],
        [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer INITIAL_ACCESS_TOKEN_VALID',
        ], json_encode([
            'redirect_uris' => ['https://www.foo.com'],
            'token_endpoint_auth_method' => 'none',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @Given a client registration request is received with an expired initial access token
     */
    public function aClientRegistrationRequestIsReceivedWithAnExpiredInitialAccessToken()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/client/management', [],
        [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => 'Bearer INITIAL_ACCESS_TOKEN_EXPIRED',
        ], json_encode([
            'redirect_uris' => ['https://www.foo.com'],
            'token_endpoint_auth_method' => 'none',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @Given a client registration request is received with a revoked initial access token
     */
    public function aClientRegistrationRequestIsReceivedWithARevokedInitialAccessToken()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/client/management', [],
        [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer INITIAL_ACCESS_TOKEN_REVOKED',
        ], json_encode([
            'redirect_uris' => ['https://www.foo.com'],
            'token_endpoint_auth_method' => 'none',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @Given a client registration request is received but not initial access token is set
     */
    public function aClientRegistrationRequestIsReceivedButNotInitialAccessTokenIsSet()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/client/management', [],
        [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'redirect_uris' => ['https://www.foo.com'],
            'token_endpoint_auth_method' => 'none',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @Given a client registration request is received but an invalid initial access token is set
     */
    public function aClientRegistrationRequestIsReceivedButAnInvalidInitialAccessTokenIsSet()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/client/management', [],
        [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer **Invalid Initial Access Token**',
        ], json_encode([
            'redirect_uris' => ['https://www.foo.com'],
            'token_endpoint_auth_method' => 'none',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @Given a valid client registration request with software statement is received
     */
    public function aValidClientRegistrationRequestWithSoftwareStatementIsReceived()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/client/management', [],
        [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer INITIAL_ACCESS_TOKEN_VALID',
        ], json_encode([
            'redirect_uris' => ['https://www.foo.com'],
            'token_endpoint_auth_method' => 'none',
            'software_statement' => $this->createSoftwareStatement(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @Given a valid client configuration GET request is received
     */
    public function aValidClientConfigurationGetRequestIsReceived()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('GET', 'https://oauth2.test/client/configure/client1', [], [], [
            'HTTP_Authorization' => 'Bearer REGISTRATION_ACCESS_TOKEN',
        ]);
    }

    /**
     * @Given a client registration request without redirect Uris is received
     */
    public function aClientRegistrationRequestWithoutRedirectUrisIsReceived()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/client/management', [],
            [], [
                'HTTP_Authorization' => 'Bearer INITIAL_ACCESS_TOKEN_VALID',
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([
                'token_endpoint_auth_method' => 'none',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @Given a client registration request but the contact list is not an array
     */
    public function aClientRegistrationRequestButTheContactListIsNotAnArray()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/client/management', [],
            [], [
                'HTTP_Authorization' => 'Bearer INITIAL_ACCESS_TOKEN_VALID',
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([
                'redirect_uris' => [
                    'https://www.foo.com/',
                ],
                'contacts' => true,
                'token_endpoint_auth_method' => 'none',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @Given a client registration request but the contact list contains invalid values
     */
    public function aClientRegistrationRequestButTheContactListContainsInvalidValues()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/client/management', [],
            [], [
                'HTTP_Authorization' => 'Bearer INITIAL_ACCESS_TOKEN_VALID',
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([
                'redirect_uris' => [
                    'https://www.foo.com/',
                ],
                'contacts' => 'BAD!!!',
                'token_endpoint_auth_method' => 'none',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @Given a client registration request with redirect Uris that contain fragments is received
     */
    public function aClientRegistrationRequestWithRedirectUrisThatContainFragmentsIsReceived()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/client/management', [],
            [], [
                'HTTP_Authorization' => 'Bearer INITIAL_ACCESS_TOKEN_VALID',
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([
                'redirect_uris' => [
                    'https://www.foo.com/#not_allowed=trur',
                ],
                'response_types' => ['id_token token'],
                'token_endpoint_auth_method' => 'none',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @Given a web client registration request is received with a redirect Uri that contain has localhost as host but the client uses the Implicit Grant Type
     */
    public function aWebClientRegistrationRequestIsReceivedWithARedirectUriThatContainHasLocalhostAsHostButTheClientUsesTheImplicitGrantType()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/client/management', [],
            [], [
                'HTTP_Authorization' => 'Bearer INITIAL_ACCESS_TOKEN_VALID',
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([
                'redirect_uris' => [
                    'https://localhost/',
                ],
                'response_types' => ['id_token token'],
                'token_endpoint_auth_method' => 'none',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @Given a web client registration request is received with an unsecured redirect Uri but the client uses the Implicit Grant Type
     */
    public function aWebClientRegistrationRequestIsReceivedWithAnUnsecuredRedirectUriButTheClientUsesTheImplicitGrantType()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/client/management', [],
            [], [
                'HTTP_Authorization' => 'Bearer INITIAL_ACCESS_TOKEN_VALID',
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([
                'redirect_uris' => [
                    'http://www.foo.com/',
                ],
                'response_types' => ['id_token token'],
                'token_endpoint_auth_method' => 'none',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @Given a client configuration GET request is received but no Registration Token is set
     */
    public function aClientConfigurationGetRequestIsReceivedButNoRegistrationTokenIsSet()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('GET', 'https://oauth2.test/client/configure/client1');
    }

    /**
     * @Given a client configuration GET request is received but the Registration Token is invalid
     */
    public function aClientConfigurationGetRequestIsReceivedButTheRegistrationTokenIsInvalid()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('GET', 'https://oauth2.test/client/configure/client1', [], [], [
            'HTTP_Authorization' => 'Bearer **INVALID_REGISTRATION_ACCESS_TOKEN**',
        ]);
    }

    /**
     * @Given a valid client configuration DELETE request is received
     */
    public function aValidClientConfigurationDeleteRequestIsReceived()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('DELETE', 'https://oauth2.test/client/configure/client1', [], [], [
            'HTTP_Authorization' => 'Bearer REGISTRATION_ACCESS_TOKEN',
        ]);
    }

    /**
     * @Given a client configuration DELETE request is received but no Registration Token is set
     */
    public function aClientConfigurationDeleteRequestIsReceivedButNoRegistrationTokenIsSet()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('DELETE', 'https://oauth2.test/client/configure/client1');
    }

    /**
     * @Given a client configuration PUT request is received but no Registration Token is set
     */
    public function aClientConfigurationPutRequestIsReceivedButNoRegistrationTokenIsSet()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('PUT', 'https://oauth2.test/client/configure/client1');
    }

    /**
     * @Given a valid client configuration PUT request is received
     */
    public function aValidClientConfigurationPutRequestIsReceived()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('PUT', 'https://oauth2.test/client/configure/client1', [], [], [
            'HTTP_Authorization' => 'Bearer REGISTRATION_ACCESS_TOKEN',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'redirect_uris' => ['https://www.bar.com'],
            'token_endpoint_auth_method' => 'client_secret_basic',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @Given the response contains the updated client
     */
    public function theResponseContainsTheUpdatedClient()
    {
        $response = $this->minkContext->getSession()->getPage()->getContent();
        $json = json_decode($response, true);
        Assertion::isArray($json);
        Assertion::keyExists($json, 'client_id');
        $this->client = $json;
    }

    /**
     * @Given a valid client configuration PUT request with software statement is received
     */
    public function aValidClientConfigurationPutRequestWithSoftwareStatementIsReceived()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('PUT', 'https://oauth2.test/client/configure/client1', [], [], [
            'HTTP_Authorization' => 'Bearer REGISTRATION_ACCESS_TOKEN',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'redirect_uris' => ['https://www.bar.com'],
            'token_endpoint_auth_method' => 'client_secret_basic',
            'software_statement' => $this->createSoftwareStatement(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @Given a valid client configuration PUT request with software statement is received but the algorithm is not supported
     */
    public function aValidClientConfigurationPutRequestWithSoftwareStatementIsReceivedButTheAlgorithmIsNotSupported()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('PUT', 'https://oauth2.test/client/configure/client1', [], [], [
            'HTTP_Authorization' => 'Bearer REGISTRATION_ACCESS_TOKEN',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'redirect_uris' => ['https://www.bar.com'],
            'token_endpoint_auth_method' => 'client_secret_basic',
            'software_statement' => $this->createInvalidSoftwareStatement(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @Then a client deleted event should be recorded
     */
    public function aClientDeletedEventShouldBeRecorded()
    {
        $events = $this->getContainer()->get(Listener\ClientDeletedListener::class)->getEvents();
        Assertion::eq(1, count($events));
    }

    /**
     * @Then no client deleted event should be recorded
     */
    public function noClientDeletedEventShouldBeRecorded()
    {
        $events = $this->getContainer()->get(Listener\ClientDeletedListener::class)->getEvents();
        Assertion::eq(0, count($events));
    }

    /**
     * @Then no client updated event should be recorded
     */
    public function noClientUpdatedEventShouldBeRecorded()
    {
        $events = $this->getContainer()->get(Listener\ClientParametersUpdatedListener::class)->getEvents();

        Assertion::eq(0, count($events));
    }

    /**
     * @Then a client created event should be recorded
     */
    public function aClientCreatedEventShouldBeRecorded()
    {
        $events = $this->getContainer()->get(Listener\ClientCreatedListener::class)->getEvents();
        Assertion::eq(1, count($events));
    }

    /**
     * @Then a client updated event should be recorded
     */
    public function aClientUpdatedEventShouldBeRecorded()
    {
        $events = $this->getContainer()->get(Listener\ClientParametersUpdatedListener::class)->getEvents();

        Assertion::eq(1, count($events));
    }

    /**
     * @Then the response contains a client
     */
    public function theResponseContainsAClient()
    {
        $response = $this->minkContext->getSession()->getPage()->getContent();
        $json = json_decode($response, true);
        Assertion::isArray($json);
        Assertion::keyExists($json, 'client_id');
        $this->client = $json;
    }

    /**
     * @Then no client should be created
     */
    public function noClientShouldBeCreated()
    {
        $events = $this->getContainer()->get(Listener\ClientCreatedListener::class)->getEvents();
        Assertion::eq(0, count($events), sprintf('Received the following event(s): %s', json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
    }

    /**
     * @Then the software statement parameters are in the client parameters
     */
    public function theSoftwareStatementParametersAreInTheClientParameters()
    {
        Assertion::keyExists($this->client, 'software_statement');
        Assertion::keyExists($this->client, 'software_version');
        Assertion::keyExists($this->client, 'software_name');
        Assertion::keyExists($this->client, 'software_name#en');
        Assertion::keyExists($this->client, 'software_name#fr');
        Assertion::eq($this->client['software_version'], '1.0');
        Assertion::eq($this->client['software_name'], 'My application');
        Assertion::eq($this->client['software_name#en'], 'My application');
        Assertion::eq($this->client['software_name#fr'], 'Mon application');
    }

    /**
     * @return string
     */
    private function createSoftwareStatement(): string
    {
        $algorithm = new RS256();
        /** @var JWKSet $keySet */
        $keySet = $this->getContainer()->get('oauth2_server.endpoint.client_registration.software_statement.key_set');
        $key = $keySet->selectKey('sig', $algorithm);

        $headers = [
            'alg' => $algorithm->name(),
        ];
        $claims = [
            'software_version' => '1.0',
            'software_name' => 'My application',
            'software_name#en' => 'My application',
            'software_name#fr' => 'Mon application',
        ];

        $jwsBuilder = new JWSBuilder(
            new StandardJsonConverter(),
            AlgorithmManager::create([new RS256()])
        );
        $serializer = new CompactSerializer();
        $jws = $jwsBuilder
            ->create()
            ->withPayload($claims)
            ->addSignature($key, $headers)
            ->build();

        return $serializer->serialize($jws, 0);
    }

    /**
     * @return string
     */
    private function createInvalidSoftwareStatement(): string
    {
        $algorithm = 'none';
        $key = JWKFactory::createNoneKey([]);

        $headers = [
            'alg' => $algorithm,
        ];
        $claims = [
            'software_version' => '1.0',
            'software_name' => 'My application',
            'software_name#en' => 'My application',
            'software_name#fr' => 'Mon application',
        ];

        $jwsBuilder = new JWSBuilder(
            new StandardJsonConverter(),
            AlgorithmManager::create([new None()])
        );
        $serializer = new CompactSerializer();
        $jws = $jwsBuilder
            ->create()
            ->withPayload($claims)
            ->addSignature($key, $headers)
            ->build();

        return $serializer->serialize($jws, 0);
    }
}
