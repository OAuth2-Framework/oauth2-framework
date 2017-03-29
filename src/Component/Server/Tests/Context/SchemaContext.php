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
use OAuth2Framework\Component\Server\Event\AccessToken\AccessTokenCreatedEvent;
use OAuth2Framework\Component\Server\Event\AccessToken\AccessTokenRevokedEvent;
use OAuth2Framework\Component\Server\Event\AuthCode\AuthCodeCreatedEvent;
use OAuth2Framework\Component\Server\Event\AuthCode\AuthCodeMarkedAsUsedEvent;
use OAuth2Framework\Component\Server\Event\AuthCode\AuthCodeRevokedEvent;
use OAuth2Framework\Component\Server\Event\Client\ClientCreatedEvent;
use OAuth2Framework\Component\Server\Event\Client\ClientDeletedEvent;
use OAuth2Framework\Component\Server\Event\Client\ClientOwnerChangedEvent;
use OAuth2Framework\Component\Server\Event\Client\ClientParametersUpdatedEvent;
use OAuth2Framework\Component\Server\Event\InitialAccessToken\InitialAccessTokenCreatedEvent;
use OAuth2Framework\Component\Server\Event\InitialAccessToken\InitialAccessTokenRevokedEvent;
use OAuth2Framework\Component\Server\Event\PreConfiguredAuthorization\PreConfiguredAuthorizationCreatedEvent;
use OAuth2Framework\Component\Server\Event\PreConfiguredAuthorization\PreConfiguredAuthorizationRevokedEvent;
use OAuth2Framework\Component\Server\Event\RefreshToken\AccessTokenAddedToRefreshTokenEvent;
use OAuth2Framework\Component\Server\Event\RefreshToken\RefreshTokenCreatedEvent;
use OAuth2Framework\Component\Server\Event\RefreshToken\RefreshTokenRevokedEvent;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Server\Model\AuthCode\AuthCode;
use OAuth2Framework\Component\Server\Model\AuthCode\AuthCodeId;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\InitialAccessToken\InitialAccessToken;
use OAuth2Framework\Component\Server\Model\InitialAccessToken\InitialAccessTokenId;
use OAuth2Framework\Component\Server\Model\PreConfiguredAuthorization\PreConfiguredAuthorization;
use OAuth2Framework\Component\Server\Model\PreConfiguredAuthorization\PreConfiguredAuthorizationId;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshToken;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenId;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Schema\DomainObjectInterface;

final class SchemaContext implements Context
{
    /**
     * @var null|DomainObjectInterface
     */
    private $domainObject = null;

    /**
     * @var null|string
     */
    private $jsonObject = null;

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

        $this->applicationContext = $environment->getContext(ApplicationContext::class);
    }

    /**
     * @Given I have a valid Access Token Created Event object
     */
    public function iHaveAValidAccessTokenCreatedEventObject()
    {
        $this->domainObject = AccessTokenCreatedEvent::create(
            AccessTokenId::create('AccessTOKEN'),
            UserAccountId::create('UserACCOUNT'),
            ClientId::create('Client'),
            DataBag::createFromArray([]),
            DataBag::createFromArray([]),
            [],
            new \DateTimeImmutable('now +1 hour'),
            null,
            null
        );
    }

    /**
     * @Given I have a valid Access Token Revoked Event object
     */
    public function iHaveAValidAccessTokenRevokedEventObject()
    {
        $this->domainObject = AccessTokenRevokedEvent::create(
            AccessTokenId::create('AccessTOKEN')
        );
    }

    /**
     * @Given I have a valid Pre-Configured Authorization Created Event object
     */
    public function iHaveAValidPreConfiguredAuthorizationCreatedEventObject()
    {
        $this->domainObject = PreConfiguredAuthorizationCreatedEvent::create(
            PreConfiguredAuthorizationId::create('PreConfiguredAuthorization'),
            ClientId::create('Client'),
            UserAccountId::create('UserACCOUNT'),
            []
        );
    }

    /**
     * @Given I have a valid Pre-Configured Authorization Revoked Event object
     */
    public function iHaveAValidPreConfiguredAuthorizationRevokedEventObject()
    {
        $this->domainObject = PreConfiguredAuthorizationRevokedEvent::create(
            PreConfiguredAuthorizationId::create('PreConfiguredAuthorization')
        );
    }

    /**
     * @Given I have a valid Initial Access Token Created Event object
     */
    public function iHaveAValidInitialAccessTokenCreatedEventObject()
    {
        $this->domainObject = InitialAccessTokenCreatedEvent::create(
            InitialAccessTokenId::create('InitialAccessTOKEN'),
            UserAccountId::create('UserACCOUNT'),
            new \DateTimeImmutable('now +1 hour')
        );
    }

    /**
     * @Given I have a valid Initial Access Token Revoked Event object
     */
    public function iHaveAValidInitialAccessTokenRevokedEventObject()
    {
        $this->domainObject = InitialAccessTokenRevokedEvent::create(
            InitialAccessTokenId::create('InitialAccessTOKEN')
        );
    }

    /**
     * @Given I have a valid Refresh Token Created Event object
     */
    public function iHaveAValidRefreshTokenCreatedEventObject()
    {
        $this->domainObject = RefreshTokenCreatedEvent::create(
            RefreshTokenId::create('RefreshTOKEN'),
            UserAccountId::create('UserACCOUNT'),
            ClientId::create('Client'),
            DataBag::createFromArray([]),
            DataBag::createFromArray([]),
            new \DateTimeImmutable('now +1 hour'),
            [],
            null
        );
    }

    /**
     * @Given I have a valid Access Token Added To Refresh Token Event object
     */
    public function iHaveAValidAccessTokenAddedToRefreshTokenEventObject()
    {
        $this->domainObject = AccessTokenAddedToRefreshTokenEvent::create(
            RefreshTokenId::create('RefreshTOKEN'),
            AccessTokenId::create('AccessToken')
        );
    }

    /**
     * @Given I have a valid Refresh Token Revoked Event object
     */
    public function iHaveAValidRefreshTokenRevokedEventObject()
    {
        $this->domainObject = RefreshTokenRevokedEvent::create(
            RefreshTokenId::create('RefreshTOKEN')
        );
    }

    /**
     * @Given I have a valid Client Created Event object
     */
    public function iHaveAValidClientCreatedEventObject()
    {
        $this->domainObject = ClientCreatedEvent::create(
            ClientId::create('Client'),
            DataBag::createFromArray([]),
            UserAccountId::create('UserACCOUNT')
        );
    }

    /**
     * @Given I have a valid Client Owner Changed Event object
     */
    public function iHaveAValidClientOwnerChangedEventObject()
    {
        $this->domainObject = ClientOwnerChangedEvent::create(
            ClientId::create('Client'),
            UserAccountId::create('UserACCOUNT')
        );
    }

    /**
     * @Given I have a valid Client Parameters Updated Event object
     */
    public function iHaveAValidClientParametersUpdatedEventObject()
    {
        $this->domainObject = ClientParametersUpdatedEvent::create(
            ClientId::create('Client'),
            DataBag::createFromArray([])
        );
    }

    /**
     * @Given I have a valid Client Deleted Event object
     */
    public function iHaveAValidClientDeletedEventObject()
    {
        $this->domainObject = ClientDeletedEvent::create(
            ClientId::create('Client')
        );
    }

    /**
     * @Given I have a valid Authorization Code Created Event object
     */
    public function iHaveAValidAuthorizationCodeCreatedEventObject()
    {
        $this->domainObject = AuthCodeCreatedEvent::create(
            AuthCodeId::create('AccessTOKEN'),
            ClientId::create('Client'),
            UserAccountId::create('UserACCOUNT'),
            [],
            'redirect_uri',
            new \DateTimeImmutable('now +1 hour'),
            DataBag::createFromArray([]),
            DataBag::createFromArray([]),
            [],
            false,
            null
        );
    }

    /**
     * @Given I have a valid Authorization Code Marked As Used Event object
     */
    public function iHaveAValidAuthorizationCodeMarkedAsUsedEventObject()
    {
        $this->domainObject = AuthCodeMarkedAsUsedEvent::create(
            AuthCodeId::create('AccessTOKEN')
        );
    }

    /**
     * @Given I have a valid Authorization Code Revoked Event object
     */
    public function iHaveAValidAuthorizationCodeRevokedEventObject()
    {
        $this->domainObject = AuthCodeRevokedEvent::create(
            AuthCodeId::create('AccessTOKEN')
        );
    }

    /**
     * @Given I have an Access Token Object
     */
    public function iHaveAnAccessTokenObject()
    {
        $accessToken = AccessToken::createEmpty();
        $accessToken = $accessToken->create(
            AccessTokenId::create('AccessTokenId'),
            UserAccountId::create('UserAccountId'),
            ClientId::create('ClientId'),
            DataBag::createFromArray([
                'foo' => 'bar',
            ]),
            DataBag::createFromArray([
                'plic' => 'ploc',
            ]),
            ['openid'],
            new \DateTimeImmutable('now +1 hour'),
            RefreshTokenId::create('RefreshTokenId'),
            ResourceServerId::create('ResourceServerId')
        );
        $this->domainObject = $accessToken;
    }

    /**
     * @Given I have an Initial Access Token Object
     */
    public function iHaveAnInitialAccessTokenObject()
    {
        $initialAccessToken = InitialAccessToken::createEmpty();
        $initialAccessToken = $initialAccessToken->create(
            InitialAccessTokenId::create('InitialAccessTokenId'),
            UserAccountId::create('UserAccountId'),
            new \DateTimeImmutable('now +1 hour')
        );
        $initialAccessToken = $initialAccessToken->markAsRevoked();
        $this->domainObject = $initialAccessToken;
    }

    /**
     * @Given I have an Authorization Code Object
     */
    public function iHaveAnAuthorizationCodeObject()
    {
        $authCode = AuthCode::createEmpty();
        $authCode = $authCode->create(
            AuthCodeId::create('AuthCodeId'),
            ClientId::create('ClientId'),
            UserAccountId::create('UserAccountId'),
            [
                'foo' => 'bar',
            ],
            'redirect_uri',
            new \DateTimeImmutable('now +1 hour'),
            DataBag::createFromArray([
                'foo' => 'bar',
            ]),
            DataBag::createFromArray([
                'plic' => 'ploc',
            ]),
            ['openid'],
            true,
            ResourceServerId::create('ResourceServerId')
        );
        $authCode = $authCode->markAsUsed();
        $authCode = $authCode->markAsRevoked();
        $this->domainObject = $authCode;
    }

    /**
     * @Given I have an Client Object
     */
    public function iHaveAnClientObject()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('ClientId'),
            DataBag::createFromArray([
                'foo' => 'bar',
            ]),
            UserAccountId::create('UserAccountId')
        );
        $client = $client->withOwnerId(UserAccountId::create('UserAccountId'));
        $client = $client->withParameters(DataBag::createFromArray(['foo' => 'bar']));
        $client = $client->markAsDeleted();
        $this->domainObject = $client;
    }

    /**
     * @Given I have a Pre-Configured Authorization Object
     */
    public function iHaveAPreConfiguredAuthorizationObject()
    {
        $preConfiguredAuthorization = PreConfiguredAuthorization::createEmpty();
        $preConfiguredAuthorization = $preConfiguredAuthorization->create(
            PreConfiguredAuthorizationId::create('PreConfiguredAuthorizationId'),
            UserAccountId::create('UserAccountId'),
            ClientId::create('ClientId'),
            ['openid']
        );
        $preConfiguredAuthorization = $preConfiguredAuthorization->markAsRevoked();
        $this->domainObject = $preConfiguredAuthorization;
    }

    /**
     * @Given I have an Refresh Token Object
     */
    public function iHaveAnRefreshTokenObject()
    {
        $refreshToken = RefreshToken::createEmpty();
        $refreshToken = $refreshToken->create(
            RefreshTokenId::create('RefreshTokenId'),
            UserAccountId::create('UserAccountId'),
            ClientId::create('ClientId'),
            DataBag::createFromArray([
                'foo' => 'bar',
            ]),
            DataBag::createFromArray([
                'plic' => 'ploc',
            ]),
            ['openid'],
            new \DateTimeImmutable('now +1 hour'),
            ResourceServerId::create('ResourceServerId')
        );
        $refreshToken = $refreshToken->addAccessToken(
            AccessTokenId::create('AccessTokenId')
        );
        $refreshToken = $refreshToken->markAsRevoked();
        $this->domainObject = $refreshToken;
    }

    /**
     * @When I convert the Domain Object into a Json Object
     */
    public function iConvertTheDomainObjectIntoAJsonObject()
    {
        Assertion::notNull($this->domainObject, 'Domain object is not set.');
        $converter = $this->applicationContext->getApplication()->getDomainConverter();
        $jsonObject = $converter->toJson($this->domainObject);
        Assertion::string($jsonObject, 'Invalid JSON object.');
        $this->jsonObject = $jsonObject;
    }

    /**
     * @Then I can recover the event from the Json Object and its class is :class
     */
    public function iCanRecoverTheEventFromTheJsonObjectAndItsClassIs($class)
    {
        Assertion::string($this->jsonObject, 'Invalid JSON object.');
        $converter = $this->applicationContext->getApplication()->getDomainConverter();
        $domainObject = $converter->fromJson($this->jsonObject);
        Assertion::isInstanceOf($domainObject, DomainObjectInterface::class, 'Invalid domain object.');
        Assertion::isInstanceOf($domainObject, $class, sprintf('Invalid class. I got %s.', get_class($domainObject)));
        Assertion::eq($this->domainObject->jsonSerialize(), $domainObject->jsonSerialize());
    }
}
