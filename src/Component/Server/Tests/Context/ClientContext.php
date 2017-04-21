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

namespace OAuth2Framework\Component\Server\Tests\Context;

use Assert\Assertion;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Http\Factory\Diactoros\StreamFactory;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;

/**
 * Defines application features from the specific context.
 */
final class ClientContext implements Context
{
    /**
     * @var null|array
     */
    private $client = null;

    /**
     * @var ResponseContext
     */
    private $responseContext;

    /**
     * @var ApplicationContext
     */
    private $applicationContext;

    /**
     * @BeforeScenario
     *
     * @param BeforeScenarioScope $scope
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        $this->responseContext = $environment->getContext(ResponseContext::class);
        $this->applicationContext = $environment->getContext(ApplicationContext::class);
    }

    /**
     * @Given a valid client registration request is received
     */
    public function aValidClientRegistrationRequestIsReceived()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('POST');
        $request = $request->withBody((new StreamFactory())->createStream(json_encode([
            'redirect_uris' => ['https://www.foo.com'],
            'token_endpoint_auth_method' => 'client_secret_basic',
        ])));
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withHeader('Authorization', 'Bearer INITIAL_ACCESS_TOKEN_VALID');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getClientRegistrationPipe()->dispatch($request));
    }

    /**
     * @Given a client registration request is received with an expired initial access token
     */
    public function aClientRegistrationRequestIsReceivedWithAnExpiredInitialAccessToken()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('POST');
        $request = $request->withBody((new StreamFactory())->createStream(json_encode([
            'redirect_uris' => ['https://www.foo.com'],
        ])));
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withHeader('Authorization', 'Bearer INITIAL_ACCESS_TOKEN_EXPIRED');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getClientRegistrationPipe()->dispatch($request));
    }

    /**
     * @Given a client registration request is received with a revoked initial access token
     */
    public function aClientRegistrationRequestIsReceivedWithARevokedInitialAccessToken()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('POST');
        $request = $request->withBody((new StreamFactory())->createStream(json_encode([
            'redirect_uris' => ['https://www.foo.com'],
        ])));
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withHeader('Authorization', 'Bearer INITIAL_ACCESS_TOKEN_REVOKED');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getClientRegistrationPipe()->dispatch($request));
    }

    /**
     * @Given a client registration request is received but not initial access token is set
     */
    public function aClientRegistrationRequestIsReceivedButNotInitialAccessTokenIsSet()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('POST');
        $request = $request->withBody((new StreamFactory())->createStream(json_encode([
            'redirect_uris' => ['https://www.foo.com'],
        ])));
        $request = $request->withHeader('Content-Type', 'application/json');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getClientRegistrationPipe()->dispatch($request));
    }

    /**
     * @Given a client registration request is received but an invalid initial access token is set
     */
    public function aClientRegistrationRequestIsReceivedButAnInvalidInitialAccessTokenIsSet()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('POST');
        $request = $request->withBody((new StreamFactory())->createStream(json_encode([
            'redirect_uris' => ['https://www.foo.com'],
        ])));
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withHeader('Authorization', 'Bearer ***INVALID_INITIAL_ACCESS_TOKEN***');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getClientRegistrationPipe()->dispatch($request));
    }

    /**
     * @Given a valid client registration request with software statement is received
     */
    public function aValidClientRegistrationRequestWithSoftwareStatementIsReceived()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('POST');
        $request = $request->withBody((new StreamFactory())->createStream(json_encode([
            'redirect_uris' => ['https://www.foo.com'],
            'token_endpoint_auth_method' => 'client_secret_basic',
            'software_statement' => $this->createSoftwareStatement(),
        ])));
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withHeader('Authorization', 'Bearer INITIAL_ACCESS_TOKEN_VALID');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getClientRegistrationPipe()->dispatch($request));
    }

    /**
     * @Given a valid client configuration GET request is received
     */
    public function aValidClientConfigurationGetRequestIsReceived()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withHeader('Authorization', 'Bearer JNWuIxHkTKtUmmtEpipDtPlTc3ordUNpSVVPLbQXKrFKyYVDR7N3k1ZzrHmPWXoibr2J2HrTSSozN6zIhHuypA');
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('79b407fb-acc0-4880-ab98-254062c214ce'),
            DataBag::createFromArray([
                'registration_access_token' => 'JNWuIxHkTKtUmmtEpipDtPlTc3ordUNpSVVPLbQXKrFKyYVDR7N3k1ZzrHmPWXoibr2J2HrTSSozN6zIhHuypA',
                'grant_types' => [],
                'response_types' => [],
                'redirect_uris' => ['https://www.foo.com'],
                'software_statement' => 'eyJhbGciOiJFUzI1NiJ9.eyJzb2Z0d2FyZV92ZXJzaW9uIjoiMS4wIiwic29mdHdhcmVfbmFtZSI6Ik15IGFwcGxpY2F0aW9uIiwic29mdHdhcmVfbmFtZSNlbiI6Ik15IGFwcGxpY2F0aW9uIiwic29mdHdhcmVfbmFtZSNmciI6Ik1vbiBhcHBsaWNhdGlvbiJ9.88m8-YyguCCx1QNChwfNnMZ9APKpNC--nnfB1rVBpAYyHLixtsyMuuI09svqxuiRfTxwgXuRUvsg_5RozmtusQ',
                'software_version' => '1.0',
                'software_name' => 'My application',
                'software_name#en' => 'My application',
                'software_name#fr' => 'Mon application',
                'registration_client_uri' => 'https://www.config.example.com/client/79b407fb-acc0-4880-ab98-254062c214ce',
                'client_id_issued_at' => 1482177703,
            ]),
            UserAccountId::create('john.1')
        );
        $request = $request->withAttribute('client', $client);

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getClientConfigurationPipe()->dispatch($request));
    }

    /**
     * @Given a client configuration GET request is received but no Registration Token is set
     */
    public function aClientConfigurationGetRequestIsReceivedButNoRegistrationTokenIsSet()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withHeader('Content-Type', 'application/json');
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('79b407fb-acc0-4880-ab98-254062c214ce'),
            DataBag::createFromArray([
                'registration_access_token' => 'JNWuIxHkTKtUmmtEpipDtPlTc3ordUNpSVVPLbQXKrFKyYVDR7N3k1ZzrHmPWXoibr2J2HrTSSozN6zIhHuypA',
                'grant_types' => [],
                'response_types' => [],
                'redirect_uris' => ['https://www.foo.com'],
                'software_statement' => 'eyJhbGciOiJFUzI1NiJ9.eyJzb2Z0d2FyZV92ZXJzaW9uIjoiMS4wIiwic29mdHdhcmVfbmFtZSI6Ik15IGFwcGxpY2F0aW9uIiwic29mdHdhcmVfbmFtZSNlbiI6Ik15IGFwcGxpY2F0aW9uIiwic29mdHdhcmVfbmFtZSNmciI6Ik1vbiBhcHBsaWNhdGlvbiJ9.88m8-YyguCCx1QNChwfNnMZ9APKpNC--nnfB1rVBpAYyHLixtsyMuuI09svqxuiRfTxwgXuRUvsg_5RozmtusQ',
                'software_version' => '1.0',
                'software_name' => 'My application',
                'software_name#en' => 'My application',
                'software_name#fr' => 'Mon application',
                'registration_client_uri' => 'https://www.config.example.com/client/79b407fb-acc0-4880-ab98-254062c214ce',
                'client_id_issued_at' => 1482177703,
            ]),
            UserAccountId::create('john.1')
        );
        $request = $request->withAttribute('client', $client);

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getClientConfigurationPipe()->dispatch($request));
    }

    /**
     * @Given a client configuration GET request is received but the Registration Token is invalid
     */
    public function aClientConfigurationGetRequestIsReceivedButTheRegistrationTokenIsInvalid()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withHeader('Authorization', 'Bearer InvALID_ToKEn');
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('79b407fb-acc0-4880-ab98-254062c214ce'),
            DataBag::createFromArray([
                'registration_access_token' => 'JNWuIxHkTKtUmmtEpipDtPlTc3ordUNpSVVPLbQXKrFKyYVDR7N3k1ZzrHmPWXoibr2J2HrTSSozN6zIhHuypA',
                'grant_types' => [],
                'response_types' => [],
                'redirect_uris' => ['https://www.foo.com'],
                'software_statement' => 'eyJhbGciOiJFUzI1NiJ9.eyJzb2Z0d2FyZV92ZXJzaW9uIjoiMS4wIiwic29mdHdhcmVfbmFtZSI6Ik15IGFwcGxpY2F0aW9uIiwic29mdHdhcmVfbmFtZSNlbiI6Ik15IGFwcGxpY2F0aW9uIiwic29mdHdhcmVfbmFtZSNmciI6Ik1vbiBhcHBsaWNhdGlvbiJ9.88m8-YyguCCx1QNChwfNnMZ9APKpNC--nnfB1rVBpAYyHLixtsyMuuI09svqxuiRfTxwgXuRUvsg_5RozmtusQ',
                'software_version' => '1.0',
                'software_name' => 'My application',
                'software_name#en' => 'My application',
                'software_name#fr' => 'Mon application',
                'registration_client_uri' => 'https://www.config.example.com/client/79b407fb-acc0-4880-ab98-254062c214ce',
                'client_id_issued_at' => 1482177703,
            ]),
            UserAccountId::create('john.1')
        );
        $request = $request->withAttribute('client', $client);

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getClientConfigurationPipe()->dispatch($request));
    }

    /**
     * @Given a valid client configuration DELETE request is received
     */
    public function aValidClientConfigurationDeleteRequestIsReceived()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('DELETE');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withHeader('Authorization', 'Bearer JNWuIxHkTKtUmmtEpipDtPlTc3ordUNpSVVPLbQXKrFKyYVDR7N3k1ZzrHmPWXoibr2J2HrTSSozN6zIhHuypA');
        $client = $this->applicationContext->getApplication()->getClientRepository()->find(ClientId::create('79b407fb-acc0-4880-ab98-254062c214ce'));
        $request = $request->withAttribute('client', $client);

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getClientConfigurationPipe()->dispatch($request));
    }

    /**
     * @Given a client configuration DELETE request is received but no Registration Token is set
     */
    public function aClientConfigurationDeleteRequestIsReceivedButNoRegistrationTokenIsSet()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('DELETE');
        $request = $request->withHeader('Content-Type', 'application/json');
        $client = $this->applicationContext->getApplication()->getClientRepository()->find(ClientId::create('79b407fb-acc0-4880-ab98-254062c214ce'));
        $request = $request->withAttribute('client', $client);

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getClientConfigurationPipe()->dispatch($request));
    }

    /**
     * @Given a client configuration PUT request is received but no Registration Token is set
     */
    public function aClientConfigurationPutRequestIsReceivedButNoRegistrationTokenIsSet()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('PUT');
        $request = $request->withBody((new StreamFactory())->createStream(json_encode([
            'redirect_uris' => ['https://www.foo.com'],
        ])));
        $request = $request->withHeader('Content-Type', 'application/json');
        $client = $this->applicationContext->getApplication()->getClientRepository()->find(ClientId::create('79b407fb-acc0-4880-ab98-254062c214ce'));
        $request = $request->withAttribute('client', $client);

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getClientConfigurationPipe()->dispatch($request));
    }

    /**
     * @Given a valid client configuration PUT request is received
     */
    public function aValidClientConfigurationPutRequestIsReceived()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('PUT');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withBody((new StreamFactory())->createStream(json_encode([
            'redirect_uris' => ['https://www.bar.com'],
            'token_endpoint_auth_method' => 'client_secret_basic',
        ])));
        $request = $request->withHeader('Authorization', 'Bearer JNWuIxHkTKtUmmtEpipDtPlTc3ordUNpSVVPLbQXKrFKyYVDR7N3k1ZzrHmPWXoibr2J2HrTSSozN6zIhHuypA');
        $client = $this->applicationContext->getApplication()->getClientRepository()->find(ClientId::create('79b407fb-acc0-4880-ab98-254062c214ce'));
        $request = $request->withAttribute('client', $client);

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getClientConfigurationPipe()->dispatch($request));
    }

    /**
     * @Given the response contains the updated client
     */
    public function theResponseContainsTheUpdatedClient()
    {
        $response = (string) $this->responseContext->getResponse()->getBody()->getContents();
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
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('PUT');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withBody((new StreamFactory())->createStream(json_encode([
            'redirect_uris' => ['https://www.bar.com'],
            'token_endpoint_auth_method' => 'client_secret_basic',
            'software_statement' => $this->createSoftwareStatement(),
        ])));
        $request = $request->withHeader('Authorization', 'Bearer JNWuIxHkTKtUmmtEpipDtPlTc3ordUNpSVVPLbQXKrFKyYVDR7N3k1ZzrHmPWXoibr2J2HrTSSozN6zIhHuypA');
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('79b407fb-acc0-4880-ab98-254062c214ce'),
            DataBag::createFromArray([
                'registration_access_token' => 'JNWuIxHkTKtUmmtEpipDtPlTc3ordUNpSVVPLbQXKrFKyYVDR7N3k1ZzrHmPWXoibr2J2HrTSSozN6zIhHuypA',
                'grant_types' => [],
                'response_types' => [],
                'redirect_uris' => ['https://www.foo.com'],
                'registration_client_uri' => 'https://www.config.example.com/client/79b407fb-acc0-4880-ab98-254062c214ce',
                'client_id_issued_at' => 1482177703,
            ]),
            UserAccountId::create('john.1')
        );
        $this->applicationContext->getApplication()->getClientRepository()->save($client);
        $request = $request->withAttribute('client', $client);

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getClientConfigurationPipe()->dispatch($request));
    }

    /**
     * @Given a valid client configuration PUT request with software statement is received but the algorithm is not supported
     */
    public function aValidClientConfigurationPutRequestWithSoftwareStatementIsReceivedButTheAlgorithmIsNotSupported()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('PUT');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withBody((new StreamFactory())->createStream(json_encode([
            'redirect_uris' => ['https://www.bar.com'],
            'token_endpoint_auth_method' => 'client_secret_basic',
            'software_statement' => $this->createInvalidSoftwareStatement(),
        ])));
        $request = $request->withHeader('Authorization', 'Bearer JNWuIxHkTKtUmmtEpipDtPlTc3ordUNpSVVPLbQXKrFKyYVDR7N3k1ZzrHmPWXoibr2J2HrTSSozN6zIhHuypA');
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('79b407fb-acc0-4880-ab98-254062c214ce'),
            DataBag::createFromArray([
                'registration_access_token' => 'JNWuIxHkTKtUmmtEpipDtPlTc3ordUNpSVVPLbQXKrFKyYVDR7N3k1ZzrHmPWXoibr2J2HrTSSozN6zIhHuypA',
                'grant_types' => [],
                'response_types' => [],
                'redirect_uris' => ['https://www.foo.com'],
                'registration_client_uri' => 'https://www.config.example.com/client/79b407fb-acc0-4880-ab98-254062c214ce',
                'client_id_issued_at' => 1482177703,
            ]),
            UserAccountId::create('john.1')
        );
        $this->applicationContext->getApplication()->getClientRepository()->save($client);
        $request = $request->withAttribute('client', $client);

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getClientConfigurationPipe()->dispatch($request));
    }

    /**
     * @Then a client deleted event should be recorded
     */
    public function aClientDeletedEventShouldBeRecorded()
    {
        $events = $this->applicationContext->getApplication()->getClientDeletedEventHandler()->getEvents();
        Assertion::eq(1, count($events));
    }

    /**
     * @Then no client deleted event should be recorded
     */
    public function noClientDeletedEventShouldBeRecorded()
    {
        $events = $this->applicationContext->getApplication()->getClientDeletedEventHandler()->getEvents();
        Assertion::eq(0, count($events));
    }

    /**
     * @Then no client updated event should be recorded
     */
    public function noClientUpdatedEventShouldBeRecorded()
    {
        $events = $this->applicationContext->getApplication()->getClientUpdatedEventHandler()->getEvents();
        Assertion::eq(0, count($events));
    }

    /**
     * @Then a client created event should be recorded
     */
    public function aClientCreatedEventShouldBeRecorded()
    {
        $events = $this->applicationContext->getApplication()->getClientCreatedEventHandler()->getEvents();
        Assertion::eq(1, count($events));
    }

    /**
     * @Then a client updated event should be recorded
     */
    public function aClientUpdatedEventShouldBeRecorded()
    {
        $events = $this->applicationContext->getApplication()->getClientUpdatedEventHandler()->getEvents();
        Assertion::eq(1, count($events));
    }

    /**
     * @Then the response contains a client
     */
    public function theResponseContainsAClient()
    {
        $response = $this->responseContext->getResponse()->getBody()->getContents();
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
        $events = $this->applicationContext->getApplication()->getClientCreatedEventHandler()->getEvents();
        Assertion::eq(0, count($events));
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
        $claims = [
            'software_version' => '1.0',
            'software_name' => 'My application',
            'software_name#en' => 'My application',
            'software_name#fr' => 'Mon application',
        ];
        $headers = [
            'alg' => 'ES256',
        ];
        $key = $this->applicationContext->getApplication()->getPrivateKeys()->getKey(0);

        return $this->applicationContext->getApplication()->getJwTCreator()->sign($claims, $headers, $key);
    }

    /**
     * @return string
     */
    private function createInvalidSoftwareStatement(): string
    {
        $claims = [
            'software_version' => '1.0',
            'software_name' => 'My application',
            'software_name#en' => 'My application',
            'software_name#fr' => 'Mon application',
        ];
        $headers = [
            'alg' => 'none',
        ];
        $key = \Jose\Factory\JWKFactory::createNoneKey([]);

        return $this->applicationContext->getApplication()->getJwTCreator()->sign($claims, $headers, $key);
    }
}
