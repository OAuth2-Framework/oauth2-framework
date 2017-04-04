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

namespace OAuth2Framework\Component\Server\Tests\Application;

use Http\Client\HttpClient;
use Http\Factory\Diactoros\ResponseFactory;
use Http\Factory\Diactoros\ServerRequestFactory;
use Http\Factory\Diactoros\UriFactory;
use Http\Mock\Client;
use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\Factory\ServerRequestFactoryInterface;
use Interop\Http\Factory\UriFactoryInterface;
use Jose\Checker\CheckerManager;
use Jose\Checker\CriticalHeaderChecker;
use Jose\Checker\ExpirationTimeChecker;
use Jose\Checker\IssuedAtChecker;
use Jose\Checker\NotBeforeChecker;
use Jose\Decrypter;
use Jose\Encrypter;
use Jose\Factory\JWKFactory;
use Jose\JWTCreator;
use Jose\JWTLoader;
use Jose\Object\JWK;
use Jose\Object\JWKSet;
use Jose\Object\JWKSetInterface;
use Jose\Object\JWKSets;
use Jose\Object\StorableJWKSet;
use Jose\Signer;
use Jose\Verifier;
use OAuth2Framework\Component\Server\Command\AccessToken\CreateAccessTokenCommand;
use OAuth2Framework\Component\Server\Command\AccessToken\CreateAccessTokenCommandHandler;
use OAuth2Framework\Component\Server\Command\AccessToken\CreateAccessTokenWithRefreshTokenCommand;
use OAuth2Framework\Component\Server\Command\AccessToken\CreateAccessTokenWithRefreshTokenCommandHandler;
use OAuth2Framework\Component\Server\Command\AccessToken\RevokeAccessTokenCommand;
use OAuth2Framework\Component\Server\Command\AccessToken\RevokeAccessTokenCommandHandler;
use OAuth2Framework\Component\Server\Command\AuthCode\CreateAuthCodeCommand;
use OAuth2Framework\Component\Server\Command\AuthCode\CreateAuthCodeCommandHandler;
use OAuth2Framework\Component\Server\Command\AuthCode\MarkAuthCodeAsUsedCommand;
use OAuth2Framework\Component\Server\Command\AuthCode\MarkAuthCodeAsUsedCommandHandler;
use OAuth2Framework\Component\Server\Command\AuthCode\RevokeAuthCodeCommand;
use OAuth2Framework\Component\Server\Command\AuthCode\RevokeAuthCodeCommandHandler;
use OAuth2Framework\Component\Server\Command\Client\CreateClientCommand;
use OAuth2Framework\Component\Server\Command\Client\CreateClientCommandHandler;
use OAuth2Framework\Component\Server\Command\Client\DeleteClientCommand;
use OAuth2Framework\Component\Server\Command\Client\DeleteClientCommandHandler;
use OAuth2Framework\Component\Server\Command\Client\UpdateClientCommand;
use OAuth2Framework\Component\Server\Command\Client\UpdateClientCommandHandler;
use OAuth2Framework\Component\Server\Command\RefreshToken\CreateRefreshTokenCommand;
use OAuth2Framework\Component\Server\Command\RefreshToken\CreateRefreshTokenCommandHandler;
use OAuth2Framework\Component\Server\Command\RefreshToken\RevokeRefreshTokenCommand;
use OAuth2Framework\Component\Server\Command\RefreshToken\RevokeRefreshTokenCommandHandler;
use OAuth2Framework\Component\Server\Command\ResourceServer\CreateResourceServerCommand;
use OAuth2Framework\Component\Server\Command\ResourceServer\CreateResourceServerCommandHandler;
use OAuth2Framework\Component\Server\Command\ResourceServer\DeleteResourceServerCommand;
use OAuth2Framework\Component\Server\Command\ResourceServer\DeleteResourceServerCommandHandler;
use OAuth2Framework\Component\Server\Command\ResourceServer\UpdateResourceServerCommand;
use OAuth2Framework\Component\Server\Command\ResourceServer\UpdateResourceServerCommandHandler;
use OAuth2Framework\Component\Server\Endpoint\Authorization\AfterConsentScreen\AfterConsentScreenManager;
use OAuth2Framework\Component\Server\Endpoint\Authorization\AuthorizationFactory;
use OAuth2Framework\Component\Server\Endpoint\Authorization\AuthorizationRequestLoader;
use OAuth2Framework\Component\Server\Endpoint\Authorization\BeforeConsentScreen\BeforeConsentScreenManager;
use OAuth2Framework\Component\Server\Endpoint\Authorization\BeforeConsentScreen\PreConfiguredAuthorizationExtension;
use OAuth2Framework\Component\Server\Endpoint\Authorization\ParameterChecker\DisplayParameterChecker;
use OAuth2Framework\Component\Server\Endpoint\Authorization\ParameterChecker\NonceParameterChecker;
use OAuth2Framework\Component\Server\Endpoint\Authorization\ParameterChecker\ParameterCheckerManager;
use OAuth2Framework\Component\Server\Endpoint\Authorization\ParameterChecker\PromptParameterChecker;
use OAuth2Framework\Component\Server\Endpoint\Authorization\ParameterChecker\RedirectUriParameterChecker;
use OAuth2Framework\Component\Server\Endpoint\Authorization\ParameterChecker\ResponseTypeAndResponseModeParameterChecker;
use OAuth2Framework\Component\Server\Endpoint\Authorization\ParameterChecker\ScopeParameterChecker;
use OAuth2Framework\Component\Server\Endpoint\Authorization\ParameterChecker\StateParameterChecker;
use OAuth2Framework\Component\Server\Endpoint\Authorization\ParameterChecker\TokenTypeParameterChecker;
use OAuth2Framework\Component\Server\Endpoint\Authorization\UserAccountDiscovery\IdTokenHintDiscovery;
use OAuth2Framework\Component\Server\Endpoint\Authorization\UserAccountDiscovery\LoginParameterChecker;
use OAuth2Framework\Component\Server\Endpoint\Authorization\UserAccountDiscovery\MaxAgeParameterChecker;
use OAuth2Framework\Component\Server\Endpoint\Authorization\UserAccountDiscovery\PromptNoneParameterChecker;
use OAuth2Framework\Component\Server\Endpoint\Authorization\UserAccountDiscovery\UserAccountDiscoveryManager;
use OAuth2Framework\Component\Server\Endpoint\ClientConfiguration\ClientConfigurationEndpoint;
use OAuth2Framework\Component\Server\Endpoint\ClientRegistration\ClientRegistrationEndpoint;
use OAuth2Framework\Component\Server\Endpoint\IFrame\IFrameEndpoint;
use OAuth2Framework\Component\Server\Endpoint\IssuerDiscovery\IssuerDiscoveryEndpoint;
use OAuth2Framework\Component\Server\Endpoint\JWKSet\JWKSetEndpoint;
use OAuth2Framework\Component\Server\Endpoint\Metadata\Metadata;
use OAuth2Framework\Component\Server\Endpoint\Metadata\MetadataEndpoint;
use OAuth2Framework\Component\Server\Endpoint\Token\Extension\OpenIdConnectExtension;
use OAuth2Framework\Component\Server\Endpoint\Token\Processor\ProcessorManager;
use OAuth2Framework\Component\Server\Endpoint\Token\TokenEndpoint;
use OAuth2Framework\Component\Server\Endpoint\Token\TokenEndpointExtensionManager;
use OAuth2Framework\Component\Server\Endpoint\TokenIntrospection\TokenIntrospectionEndpoint;
use OAuth2Framework\Component\Server\Endpoint\TokenRevocation\TokenRevocationGetEndpoint;
use OAuth2Framework\Component\Server\Endpoint\TokenRevocation\TokenRevocationPostEndpoint;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\ClaimSource\ClaimSourceManager;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\Pairwise\EncryptedSubjectIdentifier;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\Pairwise\PairwiseSubjectIdentifierAlgorithmInterface;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\ScopeSupport\AddressScopeSupport;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\ScopeSupport\EmailScopeSupport;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\ScopeSupport\PhoneScopeSupport;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\ScopeSupport\ProfilScopeSupport;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\ScopeSupport\UserInfoScopeSupportManager;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\UserInfo;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\UserInfoEndpoint;
use OAuth2Framework\Component\Server\Event\AccessToken\AccessTokenCreatedEvent;
use OAuth2Framework\Component\Server\Event\AccessToken\AccessTokenRevokedEvent;
use OAuth2Framework\Component\Server\Event\AuthCode\AuthCodeCreatedEvent;
use OAuth2Framework\Component\Server\Event\AuthCode\AuthCodeMarkedAsUsedEvent;
use OAuth2Framework\Component\Server\Event\AuthCode\AuthCodeRevokedEvent;
use OAuth2Framework\Component\Server\Event\Client\ClientCreatedEvent;
use OAuth2Framework\Component\Server\Event\Client\ClientDeletedEvent;
use OAuth2Framework\Component\Server\Event\Client\ClientParametersUpdatedEvent;
use OAuth2Framework\Component\Server\Event\InitialAccessToken\InitialAccessTokenCreatedEvent;
use OAuth2Framework\Component\Server\Event\InitialAccessToken\InitialAccessTokenRevokedEvent;
use OAuth2Framework\Component\Server\Event\RefreshToken\RefreshTokenCreatedEvent;
use OAuth2Framework\Component\Server\Event\RefreshToken\RefreshTokenRevokedEvent;
use OAuth2Framework\Component\Server\GrantType\AuthorizationCodeGrantType;
use OAuth2Framework\Component\Server\GrantType\ClientCredentialsGrantType;
use OAuth2Framework\Component\Server\GrantType\GrantTypeManager;
use OAuth2Framework\Component\Server\GrantType\JWTBearerGrantType;
use OAuth2Framework\Component\Server\GrantType\PKCEMethod\PKCEMethodInterface;
use OAuth2Framework\Component\Server\GrantType\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\Server\GrantType\PKCEMethod\Plain;
use OAuth2Framework\Component\Server\GrantType\PKCEMethod\S256;
use OAuth2Framework\Component\Server\GrantType\RefreshTokenGrantType;
use OAuth2Framework\Component\Server\GrantType\ResourceOwnerPasswordCredentialsGrantType;
use OAuth2Framework\Component\Server\Middleware\ClientAuthenticationMiddleware;
use OAuth2Framework\Component\Server\Middleware\GrantTypeMiddleware;
use OAuth2Framework\Component\Server\Middleware\HttpMethod;
use OAuth2Framework\Component\Server\Middleware\InitialAccessTokenMiddleware;
use OAuth2Framework\Component\Server\Middleware\OAuth2ResponseMiddleware;
use OAuth2Framework\Component\Server\Middleware\OAuth2SecurityMiddleware;
use OAuth2Framework\Component\Server\Middleware\Pipe;
use OAuth2Framework\Component\Server\Middleware\ResourceServerAuthenticationMiddleware;
use OAuth2Framework\Component\Server\Middleware\TokenTypeMiddleware;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenRepositoryInterface;
use OAuth2Framework\Component\Server\Model\AuthCode\AuthCodeRepositoryInterface;
use OAuth2Framework\Component\Server\Model\Client\Rule\CommonParametersRule;
use OAuth2Framework\Component\Server\Model\Client\Rule\GrantTypeFlowRule;
use OAuth2Framework\Component\Server\Model\Client\Rule\RedirectionUriRule;
use OAuth2Framework\Component\Server\Model\Client\Rule\RuleManager;
use OAuth2Framework\Component\Server\Model\Client\Rule\ScopeRule;
use OAuth2Framework\Component\Server\Model\Client\Rule\SoftwareRule;
use OAuth2Framework\Component\Server\Model\Client\Rule\SubjectTypeRule;
use OAuth2Framework\Component\Server\Model\Client\Rule\TokenEndpointAuthMethodEndpointRule;
use OAuth2Framework\Component\Server\Model\Event\EventStoreInterface;
use OAuth2Framework\Component\Server\Model\IdToken\IdTokenBuilderFactory;
use OAuth2Framework\Component\Server\Model\IdToken\IdTokenLoader;
use OAuth2Framework\Component\Server\Model\InitialAccessToken\InitialAccessTokenRepositoryInterface;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenRepositoryInterface;
use OAuth2Framework\Component\Server\Model\Scope\DefaultScopePolicy;
use OAuth2Framework\Component\Server\Model\Scope\ErrorScopePolicy;
use OAuth2Framework\Component\Server\Model\Scope\NoScopePolicy;
use OAuth2Framework\Component\Server\Model\Scope\ScopePolicyInterface;
use OAuth2Framework\Component\Server\Model\Scope\ScopePolicyManager;
use OAuth2Framework\Component\Server\Model\Scope\ScopeRepositoryInterface;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountManagerInterface;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountRepositoryInterface;
use OAuth2Framework\Component\Server\Response\Factory\AccessDeniedResponseFactory;
use OAuth2Framework\Component\Server\Response\Factory\BadRequestResponseFactory;
use OAuth2Framework\Component\Server\Response\Factory\MethodNotAllowedResponseFactory;
use OAuth2Framework\Component\Server\Response\Factory\NotImplementedResponseFactory;
use OAuth2Framework\Component\Server\Response\Factory\RedirectResponseFactory;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use OAuth2Framework\Component\Server\ResponseMode\FormPostResponseMode;
use OAuth2Framework\Component\Server\ResponseMode\FragmentResponseMode;
use OAuth2Framework\Component\Server\ResponseMode\QueryResponseMode;
use OAuth2Framework\Component\Server\ResponseMode\ResponseModeManager;
use OAuth2Framework\Component\Server\ResponseType\CodeResponseType;
use OAuth2Framework\Component\Server\ResponseType\IdTokenResponseType;
use OAuth2Framework\Component\Server\ResponseType\NoneResponseType;
use OAuth2Framework\Component\Server\ResponseType\ResponseTypeManager;
use OAuth2Framework\Component\Server\ResponseType\TokenResponseType;
use OAuth2Framework\Component\Server\Schema\DomainConverter;
use OAuth2Framework\Component\Server\Security\AccessTokenHandlerManager;
use OAuth2Framework\Component\Server\Tests\Stub\AccessTokenHandlerUsingRepository;
use OAuth2Framework\Component\Server\Tests\Stub\AccessTokenRepository;
use OAuth2Framework\Component\Server\Tests\Stub\AuthCodeRepository;
use OAuth2Framework\Component\Server\Tests\Stub\AuthenticateResponseFactory;
use OAuth2Framework\Component\Server\Tests\Stub\AuthenticateResponseFactoryForTokenIntrospection;
use OAuth2Framework\Component\Server\Tests\Stub\AuthorizationEndpoint;
use OAuth2Framework\Component\Server\Tests\Stub\ClientAssertionJwt;
use OAuth2Framework\Component\Server\Tests\Stub\ClientIdRule;
use OAuth2Framework\Component\Server\Tests\Stub\ClientRegistrationManagementRule;
use OAuth2Framework\Component\Server\Tests\Stub\ClientRepository;
use OAuth2Framework\Component\Server\Tests\Stub\ClientSecretBasic;
use OAuth2Framework\Component\Server\Tests\Stub\ClientSecretPost;
use OAuth2Framework\Component\Server\Tests\Stub\Container;
use OAuth2Framework\Component\Server\Tests\Stub\DistributedClaimSource;
use OAuth2Framework\Component\Server\Tests\Stub\Event\AccessTokenCreatedEventHandler;
use OAuth2Framework\Component\Server\Tests\Stub\Event\AccessTokenRevokedEventHandler;
use OAuth2Framework\Component\Server\Tests\Stub\Event\AuthCodeCreatedEventHandler;
use OAuth2Framework\Component\Server\Tests\Stub\Event\AuthCodeMarkedAsUsedEventHandler;
use OAuth2Framework\Component\Server\Tests\Stub\Event\AuthCodeRevokedEventHandler;
use OAuth2Framework\Component\Server\Tests\Stub\Event\ClientCreatedEventHandler;
use OAuth2Framework\Component\Server\Tests\Stub\Event\ClientDeletedEventHandler;
use OAuth2Framework\Component\Server\Tests\Stub\Event\ClientUpdatedEventHandler;
use OAuth2Framework\Component\Server\Tests\Stub\Event\EventStore;
use OAuth2Framework\Component\Server\Tests\Stub\Event\RefreshTokenCreatedEventHandler;
use OAuth2Framework\Component\Server\Tests\Stub\Event\RefreshTokenRevokedEventHandler;
use OAuth2Framework\Component\Server\Tests\Stub\FormPostResponseRenderer;
use OAuth2Framework\Component\Server\Tests\Stub\InitialAccessTokenRepository;
use OAuth2Framework\Component\Server\Tests\Stub\IpAddressMiddleware;
use OAuth2Framework\Component\Server\Tests\Stub\MacToken;
use OAuth2Framework\Component\Server\Tests\Stub\PreConfiguredAuthorizationRepository;
use OAuth2Framework\Component\Server\Tests\Stub\RefreshTokenRepository;
use OAuth2Framework\Component\Server\Tests\Stub\ResourceRepository;
use OAuth2Framework\Component\Server\Tests\Stub\ResourceServerAuthMethodByIpAddress;
use OAuth2Framework\Component\Server\Tests\Stub\ResourceServerRepository;
use OAuth2Framework\Component\Server\Tests\Stub\ScopeRepository;
use OAuth2Framework\Component\Server\Tests\Stub\SecurityLayer;
use OAuth2Framework\Component\Server\Tests\Stub\ServiceLocator;
use OAuth2Framework\Component\Server\Tests\Stub\SessionStateParameterExtension;
use OAuth2Framework\Component\Server\Tests\Stub\SubjectChecker;
use OAuth2Framework\Component\Server\Tests\Stub\TrustedIssuer;
use OAuth2Framework\Component\Server\Tests\Stub\UriExtension;
use OAuth2Framework\Component\Server\Tests\Stub\UserAccountManager;
use OAuth2Framework\Component\Server\Tests\Stub\UserAccountRepository;
use OAuth2Framework\Component\Server\TokenEndpointAuthMethod\None;
use OAuth2Framework\Component\Server\TokenEndpointAuthMethod\TokenEndpointAuthMethodManager;
use OAuth2Framework\Component\Server\TokenIntrospectionEndpointAuthMethod\TokenIntrospectionEndpointAuthMethodManager;
use OAuth2Framework\Component\Server\TokenType\BearerToken;
use OAuth2Framework\Component\Server\TokenType\TokenTypeManager;
use OAuth2Framework\Component\Server\TokenTypeHint\AccessTokenTypeHint;
use OAuth2Framework\Component\Server\TokenTypeHint\AuthCodeTypeHint;
use OAuth2Framework\Component\Server\TokenTypeHint\RefreshTokenTypeHint;
use OAuth2Framework\Component\Server\TokenTypeHint\TokenTypeHintManager;
use SimpleBus\Message\Bus\Middleware\FinishesHandlingMessageBeforeHandlingNext;
use SimpleBus\Message\Bus\Middleware\MessageBusSupportingMiddleware;
use SimpleBus\Message\CallableResolver\CallableCollection;
use SimpleBus\Message\CallableResolver\CallableMap;
use SimpleBus\Message\CallableResolver\ServiceLocatorAwareCallableResolver;
use SimpleBus\Message\Handler\DelegatesToMessageHandlerMiddleware;
use SimpleBus\Message\Handler\Resolver\NameBasedMessageHandlerResolver;
use SimpleBus\Message\Name\ClassBasedNameResolver;
use SimpleBus\Message\Recorder\HandlesRecordedMessagesMiddleware;
use SimpleBus\Message\Recorder\PublicMessageRecorder;
use SimpleBus\Message\Subscriber\NotifiesMessageSubscribersMiddleware;
use SimpleBus\Message\Subscriber\Resolver\NameBasedMessageSubscriberResolver;

final class Application
{
    /**
     * @var string
     */
    private $pairwiseKey = 'This is my secret Key !!!';
    /**
     * @var string
     */
    private $pairwiseAdditionalData = 'This is my salt or my IV !!!';

    public function __construct()
    {
        if (PHP_SESSION_ACTIVE !== session_status()) {
            session_start();
        }
    }

    public function __destruct()
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_destroy();
        }

        foreach (['getPrivateECKeys', 'getPrivateRSAKeys', 'getPrivateNoneKeys'] as $method) {
            $keyset = $this->$method();
            $keyset->delete();
        }
    }

    /**
     * @return string
     */
    protected function getPairwiseKey()
    {
        return $this->pairwiseKey;
    }

    /**
     * @return string
     */
    protected function getPairwiseAdditionalData()
    {
        return mb_substr($this->pairwiseAdditionalData, 0, 16, '8bit');
    }

    /**
     * @var null|OAuth2ResponseFactoryManager
     */
    private $oauth2ResponseFactory = null;

    /**
     * @var null|OAuth2ResponseMiddleware
     */
    private $oauth2ResponseMiddleware = null;

    /**
     * @return OAuth2ResponseFactoryManager
     */
    public function getOAuth2ResponseFactory(): OAuth2ResponseFactoryManager
    {
        if (null === $this->oauth2ResponseFactory) {
            $this->oauth2ResponseFactory = new OAuth2ResponseFactoryManager($this->getResponseFactory());
            $this->oauth2ResponseFactory->addExtension(new UriExtension());

            $this->oauth2ResponseFactory->addResponseFactory(new AuthenticateResponseFactory(
                $this->getTokenEndpointAuthMethodManager()
            ));
            $this->oauth2ResponseFactory->addResponseFactory(new AccessDeniedResponseFactory());
            $this->oauth2ResponseFactory->addResponseFactory(new BadRequestResponseFactory());
            $this->oauth2ResponseFactory->addResponseFactory(new MethodNotAllowedResponseFactory());
            $this->oauth2ResponseFactory->addResponseFactory(new NotImplementedResponseFactory());
            $this->oauth2ResponseFactory->addResponseFactory(new RedirectResponseFactory());
        }

        return $this->oauth2ResponseFactory;
    }

    /**
     * @return OAuth2ResponseFactoryManager
     */
    public function getOAuth2ResponseFactoryForTokenIntrospection(): OAuth2ResponseFactoryManager
    {
        if (null === $this->oauth2ResponseFactory) {
            $this->oauth2ResponseFactory = new OAuth2ResponseFactoryManager($this->getResponseFactory());
            $this->oauth2ResponseFactory->addResponseFactory(new AuthenticateResponseFactoryForTokenIntrospection(
                $this->getTokenIntrospectionEndpointAuthMethodManager()
            ));
            $this->oauth2ResponseFactory->addResponseFactory(new BadRequestResponseFactory());
        }

        return $this->oauth2ResponseFactory;
    }

    /**
     * @return OAuth2ResponseMiddleware
     */
    public function getOAuth2ResponseMiddleware(): OAuth2ResponseMiddleware
    {
        if (null === $this->oauth2ResponseMiddleware) {
            $this->oauth2ResponseMiddleware = new OAuth2ResponseMiddleware(
                $this->getOAuth2ResponseFactory()
            );
        }

        return $this->oauth2ResponseMiddleware;
    }

    /**
     * @var null|ClientRepository
     */
    private $clientRepository = null;

    /**
     * @return ClientRepository
     */
    public function getClientRepository(): ClientRepository
    {
        if (null === $this->clientRepository) {
            $this->clientRepository = new ClientRepository(
                $this->getClientEventStore(),
                $this->getPublicMessageRecorder()
            );
        }

        return $this->clientRepository;
    }

    /**
     * @var null|ResourceServerRepository
     */
    private $resourceServerRepository = null;

    /**
     * @return ResourceServerRepository
     */
    public function getResourceServerRepository(): ResourceServerRepository
    {
        if (null === $this->resourceServerRepository) {
            $this->resourceServerRepository = new ResourceServerRepository(
                $this->getResourceServerEventStore(),
                $this->getPublicMessageRecorder()
            );
        }

        return $this->resourceServerRepository;
    }

    /**
     * @var null|ClientRegistrationEndpoint
     */
    private $clientRegistrationEndpoint = null;

    /**
     * @return ClientRegistrationEndpoint
     */
    public function getClientRegistrationEndpoint(): ClientRegistrationEndpoint
    {
        if (null === $this->clientRegistrationEndpoint) {
            $this->clientRegistrationEndpoint = new ClientRegistrationEndpoint(
                $this->getResponseFactory(),
                $this->getCommandBus()
            );
        }

        return $this->clientRegistrationEndpoint;
    }

    /**
     * @var null|Pipe
     */
    private $clientRegistrationPipe = null;

    /**
     * @return Pipe
     */
    public function getClientRegistrationPipe(): Pipe
    {
        if (null === $this->clientRegistrationPipe) {
            $this->clientRegistrationPipe = new Pipe();

            $this->clientRegistrationPipe->appendMiddleware($this->getOAuth2ResponseMiddleware());
            $this->clientRegistrationPipe->appendMiddleware($this->getInitialAccessTokenMiddleware());
            $this->clientRegistrationPipe->appendMiddleware($this->getClientRegistrationEndpoint());
        }

        return $this->clientRegistrationPipe;
    }

    /**
     * @var null|ClientAuthenticationMiddleware
     */
    private $clientAuthenticationMiddleware = null;

    /**
     * @return ClientAuthenticationMiddleware
     */
    public function getClientAuthenticationMiddleware(): ClientAuthenticationMiddleware
    {
        if (null === $this->clientAuthenticationMiddleware) {
            $this->clientAuthenticationMiddleware = new ClientAuthenticationMiddleware(
                $this->getClientRepository(),
                $this->getTokenEndpointAuthMethodManager(),
                false
            );
        }

        return $this->clientAuthenticationMiddleware;
    }

    /**
     * @var null|ResourceServerAuthenticationMiddleware
     */
    private $resourceServerAuthenticationMiddleware = null;

    /**
     * @return ResourceServerAuthenticationMiddleware
     */
    public function getResourceServerAuthenticationMiddleware(): ResourceServerAuthenticationMiddleware
    {
        if (null === $this->resourceServerAuthenticationMiddleware) {
            $this->resourceServerAuthenticationMiddleware = new ResourceServerAuthenticationMiddleware(
                $this->getResourceServerRepository(),
                $this->getTokenIntrospectionEndpointAuthMethodManager()
            );
        }

        return $this->resourceServerAuthenticationMiddleware;
    }

    /**
     * @var null|ClientAuthenticationMiddleware
     */
    private $clientAuthenticationMiddlewareWithRequirement = null;

    /**
     * @return ClientAuthenticationMiddleware
     */
    public function getClientAuthenticationMiddlewareWithRequirement(): ClientAuthenticationMiddleware
    {
        if (null === $this->clientAuthenticationMiddlewareWithRequirement) {
            $this->clientAuthenticationMiddlewareWithRequirement = new ClientAuthenticationMiddleware(
                $this->getClientRepository(),
                $this->getTokenEndpointAuthMethodManager(),
                true
            );
        }

        return $this->clientAuthenticationMiddlewareWithRequirement;
    }

    /**
     * @var null|TokenEndpointAuthMethodManager
     */
    private $tokenEndpointAuthMethodManager = null;

    /**
     * @return TokenEndpointAuthMethodManager
     */
    public function getTokenEndpointAuthMethodManager(): TokenEndpointAuthMethodManager
    {
        if (null === $this->tokenEndpointAuthMethodManager) {
            $this->tokenEndpointAuthMethodManager = new TokenEndpointAuthMethodManager();
            $this->tokenEndpointAuthMethodManager->addTokenEndpointAuthMethod(new None());
            $this->tokenEndpointAuthMethodManager->addTokenEndpointAuthMethod(new ClientSecretBasic('My service'));
            $this->tokenEndpointAuthMethodManager->addTokenEndpointAuthMethod(new ClientSecretPost());
            $this->tokenEndpointAuthMethodManager->addTokenEndpointAuthMethod(new ClientAssertionJwt(
                $this->getJwtLoader()
            ));
        }

        return $this->tokenEndpointAuthMethodManager;
    }

    /**
     * @var null|TokenIntrospectionEndpointAuthMethodManager
     */
    private $tokenIntrospectionEndpointAuthMethodManager = null;

    /**
     * @return TokenIntrospectionEndpointAuthMethodManager
     */
    public function getTokenIntrospectionEndpointAuthMethodManager(): TokenIntrospectionEndpointAuthMethodManager
    {
        if (null === $this->tokenIntrospectionEndpointAuthMethodManager) {
            $this->tokenIntrospectionEndpointAuthMethodManager = new TokenIntrospectionEndpointAuthMethodManager();
            $this->tokenIntrospectionEndpointAuthMethodManager->addTokenIntrospectionEndpointAuthMethod(new ResourceServerAuthMethodByIpAddress());
        }

        return $this->tokenIntrospectionEndpointAuthMethodManager;
    }

    /**
     * @var null|AuthCodeCreatedEventHandler
     */
    private $authCodeCreatedEventHandler = null;

    /**
     * @return AuthCodeCreatedEventHandler
     */
    public function getAuthCodeCreatedEventHandler(): AuthCodeCreatedEventHandler
    {
        if (null === $this->authCodeCreatedEventHandler) {
            $this->authCodeCreatedEventHandler = new AuthCodeCreatedEventHandler();
        }

        return $this->authCodeCreatedEventHandler;
    }

    /**
     * @var null|AuthCodeMarkedAsUsedEventHandler
     */
    private $authCodeMarkedAsUsedEventHandler = null;

    /**
     * @return AuthCodeMarkedAsUsedEventHandler
     */
    public function getAuthCodeMarkedAsUsedEventHandler(): AuthCodeMarkedAsUsedEventHandler
    {
        if (null === $this->authCodeMarkedAsUsedEventHandler) {
            $this->authCodeMarkedAsUsedEventHandler = new AuthCodeMarkedAsUsedEventHandler();
        }

        return $this->authCodeMarkedAsUsedEventHandler;
    }

    /**
     * @var null|AuthCodeRevokedEventHandler
     */
    private $authCodeRevokedEventHandler = null;

    /**
     * @return AuthCodeRevokedEventHandler
     */
    public function getAuthCodeRevokedEventHandler(): AuthCodeRevokedEventHandler
    {
        if (null === $this->authCodeRevokedEventHandler) {
            $this->authCodeRevokedEventHandler = new AuthCodeRevokedEventHandler();
        }

        return $this->authCodeRevokedEventHandler;
    }

    /**
     * @var null|ClientCreatedEventHandler
     */
    private $clientCreatedEventHandler = null;

    /**
     * @return ClientCreatedEventHandler
     */
    public function getClientCreatedEventHandler(): ClientCreatedEventHandler
    {
        if (null === $this->clientCreatedEventHandler) {
            $this->clientCreatedEventHandler = new ClientCreatedEventHandler(
                $this->getClientRepository()
            );
        }

        return $this->clientCreatedEventHandler;
    }

    /**
     * @var null|ClientDeletedEventHandler
     */
    private $clientDeletedEventHandler = null;

    /**
     * @return ClientDeletedEventHandler
     */
    public function getClientDeletedEventHandler(): ClientDeletedEventHandler
    {
        if (null === $this->clientDeletedEventHandler) {
            $this->clientDeletedEventHandler = new ClientDeletedEventHandler();
        }

        return $this->clientDeletedEventHandler;
    }

    /**
     * @var null|ClientUpdatedEventHandler
     */
    private $clientUpdatedEventHandler = null;

    /**
     * @return ClientUpdatedEventHandler
     */
    public function getClientUpdatedEventHandler(): ClientUpdatedEventHandler
    {
        if (null === $this->clientUpdatedEventHandler) {
            $this->clientUpdatedEventHandler = new ClientUpdatedEventHandler();
        }

        return $this->clientUpdatedEventHandler;
    }

    /**
     * @var null|MessageBusSupportingMiddleware
     */
    private $commandBus = null;

    /**
     * @return MessageBusSupportingMiddleware
     */
    public function getCommandBus(): MessageBusSupportingMiddleware
    {
        if (null === $this->commandBus) {
            $this->commandBus = new MessageBusSupportingMiddleware();
            $this->commandBus->appendMiddleware(new HandlesRecordedMessagesMiddleware(
                $this->getPublicMessageRecorder(),
                $this->getEventBus()
            ));
            $this->commandBus->appendMiddleware(new FinishesHandlingMessageBeforeHandlingNext());
            $this->commandBus->appendMiddleware(new DelegatesToMessageHandlerMiddleware(
                $this->getCommandHandlerResolver()
            ));
        }

        return $this->commandBus;
    }

    /**
     * @var null|CallableMap
     */
    private $commandHandlerMap = null;

    /**
     * @return CallableMap
     */
    public function getCommandHandlerMap(): CallableMap
    {
        if (null === $this->commandHandlerMap) {
            $this->commandHandlerMap = new CallableMap(
                [
                    CreateClientCommand::class => CreateClientCommandHandler::class,
                    DeleteClientCommand::class => DeleteClientCommandHandler::class,
                    UpdateClientCommand::class => UpdateClientCommandHandler::class,
                    CreateResourceServerCommand::class => CreateResourceServerCommandHandler::class,
                    DeleteResourceServerCommand::class => DeleteResourceServerCommandHandler::class,
                    UpdateResourceServerCommand::class => UpdateResourceServerCommandHandler::class,
                    CreateAccessTokenCommand::class => CreateAccessTokenCommandHandler::class,
                    CreateAccessTokenWithRefreshTokenCommand::class => CreateAccessTokenWithRefreshTokenCommandHandler::class,
                    RevokeAccessTokenCommand::class => RevokeAccessTokenCommandHandler::class,

                    CreateRefreshTokenCommand::class => CreateRefreshTokenCommandHandler::class,
                    RevokeRefreshTokenCommand::class => RevokeRefreshTokenCommandHandler::class,

                    CreateAuthCodeCommand::class => CreateAuthCodeCommandHandler::class,
                    MarkAuthCodeAsUsedCommand::class => MarkAuthCodeAsUsedCommandHandler::class,
                    RevokeAuthCodeCommand::class => RevokeAuthCodeCommandHandler::class,
                ],
                $this->getServiceLocatorAwareCallableResolver()
            );
        }

        return $this->commandHandlerMap;
    }

    /**
     * @var null|NameBasedMessageHandlerResolver
     */
    private $commandHandlerResolver = null;

    /**
     * @return NameBasedMessageHandlerResolver
     */
    public function getCommandHandlerResolver(): NameBasedMessageHandlerResolver
    {
        if (null === $this->commandHandlerResolver) {
            $this->commandHandlerResolver = new NameBasedMessageHandlerResolver(
                new ClassBasedNameResolver(),
                $this->getCommandHandlerMap()
            );
        }

        return $this->commandHandlerResolver;
    }

    /**
     * @var null|Container
     */
    private $container = null;

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        if (null === $this->container) {
            $this->container = new Container();

            $this->container->add($this->getCreateClientCommandHandler());
            $this->container->add($this->getDeleteClientCommandHandler());
            $this->container->add($this->getUpdateClientCommandHandler());

            $this->container->add($this->getCreateResourceServerCommandHandler());
            $this->container->add($this->getDeleteResourceServerCommandHandler());
            $this->container->add($this->getUpdateResourceServerCommandHandler());

            $this->container->add($this->getCreateAccessTokenCommandHandler());
            $this->container->add($this->getCreateAccessTokenWithRefreshTokenCommandHandler());
            $this->container->add($this->getRevokeAccessTokenCommandHandler());

            $this->container->add($this->getCreateRefreshTokenCommandHandler());
            $this->container->add($this->getRevokeRefreshTokenCommandHandler());

            $this->container->add($this->getCreateAuthCodeCommandHandler());
            $this->container->add($this->getMarkAuthCodeAsUsedCommandHandler());
            $this->container->add($this->getRevokeAuthCodeCommandHandler());

            $this->container->add($this->getClientCreatedEventHandler());
            $this->container->add($this->getClientDeletedEventHandler());
            $this->container->add($this->getClientUpdatedEventHandler());

            $this->container->add($this->getAuthCodeCreatedEventHandler());
            $this->container->add($this->getAuthCodeMarkedAsUsedEventHandler());
            $this->container->add($this->getAuthCodeRevokedEventHandler());

            $this->container->add($this->getAccessTokenRevokedEventHandler());
            $this->container->add($this->getAccessTokenCreatedEventHandler());

            $this->container->add($this->getRefreshTokenCreatedEventHandler());
            $this->container->add($this->getRefreshTokenRevokedEventHandler());
        }

        return $this->container;
    }

    /**
     * @var null|CreateClientCommandHandler
     */
    private $createClientCommandHandler = null;

    /**
     * @return CreateClientCommandHandler
     */
    public function getCreateClientCommandHandler(): CreateClientCommandHandler
    {
        if (null === $this->createClientCommandHandler) {
            $this->createClientCommandHandler = new CreateClientCommandHandler(
                $this->getClientRepository(),
                $this->getRuleManager()
            );
        }

        return $this->createClientCommandHandler;
    }

    /**
     * @var null|DeleteClientCommandHandler
     */
    private $deleteClientCommandHandler = null;

    /**
     * @return DeleteClientCommandHandler
     */
    public function getDeleteClientCommandHandler(): DeleteClientCommandHandler
    {
        if (null === $this->deleteClientCommandHandler) {
            $this->deleteClientCommandHandler = new DeleteClientCommandHandler(
                $this->getClientRepository()
            );
        }

        return $this->deleteClientCommandHandler;
    }

    /**
     * @var null|UpdateClientCommandHandler
     */
    private $updateClientCommandHandler = null;

    /**
     * @return UpdateClientCommandHandler
     */
    public function getUpdateClientCommandHandler(): UpdateClientCommandHandler
    {
        if (null === $this->updateClientCommandHandler) {
            $this->updateClientCommandHandler = new UpdateClientCommandHandler(
                $this->getClientRepository(),
                $this->getRuleManager()
            );
        }

        return $this->updateClientCommandHandler;
    }

    /**
     * @var null|CreateResourceServerCommandHandler
     */
    private $createResourceServerCommandHandler = null;

    /**
     * @return CreateResourceServerCommandHandler
     */
    public function getCreateResourceServerCommandHandler(): CreateResourceServerCommandHandler
    {
        if (null === $this->createResourceServerCommandHandler) {
            $this->createResourceServerCommandHandler = new CreateResourceServerCommandHandler(
                $this->getResourceServerRepository()
            );
        }

        return $this->createResourceServerCommandHandler;
    }

    /**
     * @var null|DeleteResourceServerCommandHandler
     */
    private $deleteResourceServerCommandHandler = null;

    /**
     * @return DeleteResourceServerCommandHandler
     */
    public function getDeleteResourceServerCommandHandler(): DeleteResourceServerCommandHandler
    {
        if (null === $this->deleteResourceServerCommandHandler) {
            $this->deleteResourceServerCommandHandler = new DeleteResourceServerCommandHandler(
                $this->getResourceServerRepository()
            );
        }

        return $this->deleteResourceServerCommandHandler;
    }

    /**
     * @var null|UpdateResourceServerCommandHandler
     */
    private $updateResourceServerCommandHandler = null;

    /**
     * @return UpdateResourceServerCommandHandler
     */
    public function getUpdateResourceServerCommandHandler(): UpdateResourceServerCommandHandler
    {
        if (null === $this->updateResourceServerCommandHandler) {
            $this->updateResourceServerCommandHandler = new UpdateResourceServerCommandHandler(
                $this->getResourceServerRepository()
            );
        }

        return $this->updateResourceServerCommandHandler;
    }

    /**
     * @var null|MessageBusSupportingMiddleware
     */
    private $eventBus = null;

    /**
     * @return MessageBusSupportingMiddleware
     */
    public function getEventBus(): MessageBusSupportingMiddleware
    {
        if (null === $this->eventBus) {
            $this->eventBus = new MessageBusSupportingMiddleware();
            $this->eventBus->appendMiddleware(new FinishesHandlingMessageBeforeHandlingNext());
            $this->eventBus->appendMiddleware(new NotifiesMessageSubscribersMiddleware(
                $this->getEventHandlerResolver()
            ));
        }

        return $this->eventBus;
    }

    /**
     * @var null|NameBasedMessageSubscriberResolver
     */
    private $eventHandlerResolver = null;

    /**
     * @return NameBasedMessageSubscriberResolver
     */
    public function getEventHandlerResolver(): NameBasedMessageSubscriberResolver
    {
        if (null === $this->eventHandlerResolver) {
            $this->eventHandlerResolver = new NameBasedMessageSubscriberResolver(
                new ClassBasedNameResolver(),
                $this->getEventHandlerCollection()
            );
        }

        return $this->eventHandlerResolver;
    }

    /**
     * @var null|CallableCollection
     */
    private $eventHandlerCollection = null;

    /**
     * @return CallableCollection
     */
    public function getEventHandlerCollection(): CallableCollection
    {
        if (null === $this->eventHandlerCollection) {
            $this->eventHandlerCollection = new CallableCollection(
                [
                    AccessTokenCreatedEvent::class => [AccessTokenCreatedEventHandler::class],
                    AccessTokenRevokedEvent::class => [AccessTokenRevokedEventHandler::class],
                    AuthCodeCreatedEvent::class => [AuthCodeCreatedEventHandler::class],
                    AuthCodeMarkedAsUsedEvent::class => [AuthCodeMarkedAsUsedEventHandler::class],
                    AuthCodeRevokedEvent::class => [AuthCodeRevokedEventHandler::class],
                    ClientCreatedEvent::class => [ClientCreatedEventHandler::class],
                    ClientDeletedEvent::class => [ClientDeletedEventHandler::class],
                    ClientParametersUpdatedEvent::class => [ClientUpdatedEventHandler::class],
                    InitialAccessTokenCreatedEvent::class => [],
                    InitialAccessTokenRevokedEvent::class => [],
                    RefreshTokenCreatedEvent::class => [RefreshTokenCreatedEventHandler::class],
                    RefreshTokenRevokedEvent::class => [RefreshTokenRevokedEventHandler::class],
                ],
                $this->getServiceLocatorAwareCallableResolver()
            );
        }

        return $this->eventHandlerCollection;
    }

    /**
     * @var null|PublicMessageRecorder
     */
    private $publicMessageRecorder = null;

    /**
     * @return PublicMessageRecorder
     */
    public function getPublicMessageRecorder(): PublicMessageRecorder
    {
        if (null === $this->publicMessageRecorder) {
            $this->publicMessageRecorder = new PublicMessageRecorder();
        }

        return $this->publicMessageRecorder;
    }

    /**
     * @var null|ResponseFactoryInterface
     */
    private $responseFactory = null;

    /**
     * @return ResponseFactoryInterface
     */
    public function getResponseFactory(): ResponseFactoryInterface
    {
        if (null === $this->responseFactory) {
            $this->responseFactory = new ResponseFactory();
        }

        return $this->responseFactory;
    }

    /**
     * @var null|UriFactoryInterface
     */
    private $uriFactory = null;

    /**
     * @return UriFactoryInterface
     */
    public function getUriFactory(): UriFactoryInterface
    {
        if (null === $this->uriFactory) {
            $this->uriFactory = new UriFactory();
        }

        return $this->uriFactory;
    }

    /**
     * @var null|RuleManager
     */
    private $ruleManager = null;

    /**
     * @return RuleManager
     */
    public function getRuleManager(): RuleManager
    {
        if (null === $this->ruleManager) {
            $this->ruleManager = new RuleManager(
                new ClientIdRule()
            );
            $this->ruleManager
                ->add(new ClientRegistrationManagementRule())
                ->add(new CommonParametersRule())
                ->add($this->getGrantTypeFlowRule())
                ->add(new RedirectionUriRule())
                ->add(new ScopeRule($this->getScopeRepository()))
                ->add($this->getSoftwareRule())
                ->add(new SubjectTypeRule($this->getUserInfo()))
                ->add(new TokenEndpointAuthMethodEndpointRule($this->getTokenEndpointAuthMethodManager()));
        }

        return $this->ruleManager;
    }

    /**
     * @var null|SoftwareRule
     */
    private $softwareRule = null;

    /**
     * @return SoftwareRule
     */
    private function getSoftwareRule(): SoftwareRule
    {
        if (null === $this->softwareRule) {
            $this->softwareRule = new SoftwareRule(
                $this->getJwtLoader(),
                $this->getPublicKeys(),
                false,
                ['ES256']
            );
        }

        return $this->softwareRule;
    }

    /**
     * @return JWKSetInterface
     */
    private function getPublicKeys(): JWKSetInterface
    {
        return JWKFactory::createPublicKeySet($this->getPrivateKeys());
    }

    /**
     * @var null|JWKSetInterface
     */
    private $privateKeys = null;

    /**
     * @return JWKSetInterface
     */
    public function getPrivateKeys(): JWKSetInterface
    {
        if (null === $this->privateKeys) {
            $ecKeys = $this->getPrivateECKeys();
            $rsaKeys = $this->getPrivateRSAKeys();
            $noneKeys = $this->getPrivateNoneKeys();

            $this->privateKeys = new JWKSets([
                $ecKeys,
                $rsaKeys,
                $noneKeys,
            ]);
        }

        return $this->privateKeys;
    }

    /**
     * @var null|StorableJWKSet
     */
    private $privateECKeys = null;

    /**
     * @return StorableJWKSet
     */
    public function getPrivateECKeys(): StorableJWKSet
    {
        if (null === $this->privateECKeys) {
            $this->privateECKeys = JWKFactory::createStorableKeySet(
                tempnam(sys_get_temp_dir(), 'EC.keys'),
                [
                    'kty' => 'EC',
                    'alg' => 'ES256',
                    'crv' => 'P-256',
                ],
                2
            );
        }

        return $this->privateECKeys;
    }

    /**
     * @var null|StorableJWKSet
     */
    private $privateNoneKeys = null;

    /**
     * @return StorableJWKSet
     */
    public function getPrivateNoneKeys(): StorableJWKSet
    {
        if (null === $this->privateNoneKeys) {
            $this->privateNoneKeys = JWKFactory::createStorableKeySet(
                tempnam(sys_get_temp_dir(), 'none.keys'),
                [
                    'kty' => 'none',
                    'alg' => 'none',
                ],
                1
            );
        }

        return $this->privateNoneKeys;
    }

    /**
     * @var null|StorableJWKSet
     */
    private $privateRSAKeys = null;

    /**
     * @return StorableJWKSet
     */
    public function getPrivateRSAKeys(): StorableJWKSet
    {
        if (null === $this->privateRSAKeys) {
            $this->privateRSAKeys = JWKFactory::createStorableKeySet(
                tempnam(sys_get_temp_dir(), 'RSA.keys'),
                [
                    'kty' => 'RSA',
                    'alg' => 'RS256',
                    'size' => '1024',
                ],
                2
            );
        }

        return $this->privateRSAKeys;
    }

    /**
     * @var null|ServerRequestFactoryInterface
     */
    private $serverRequestFactory = null;

    /**
     * @return ServerRequestFactoryInterface
     */
    public function getServerRequestFactory(): ServerRequestFactoryInterface
    {
        if (null === $this->serverRequestFactory) {
            $this->serverRequestFactory = new ServerRequestFactory();
        }

        return $this->serverRequestFactory;
    }

    /**
     * @var null|ServiceLocatorAwareCallableResolver
     */
    private $serviceLocatorAwareCallableResolver = null;

    /**
     * @return ServiceLocatorAwareCallableResolver
     */
    public function getServiceLocatorAwareCallableResolver(): ServiceLocatorAwareCallableResolver
    {
        if (null === $this->serviceLocatorAwareCallableResolver) {
            $this->serviceLocatorAwareCallableResolver = new ServiceLocatorAwareCallableResolver(
                $this->getServiceLocator()
            );
        }

        return $this->serviceLocatorAwareCallableResolver;
    }

    /**
     * @var null|ServiceLocator
     */
    private $serviceLocator = null;

    /**
     * @return ServiceLocator
     */
    public function getServiceLocator(): ServiceLocator
    {
        if (null === $this->serviceLocator) {
            $this->serviceLocator = new ServiceLocator(
                $this->getContainer()
            );
        }

        return $this->serviceLocator;
    }

    /**
     * @var null|GrantTypeFlowRule
     */
    private $grantTypeFlowRule = null;

    /**
     * @return GrantTypeFlowRule
     */
    public function getGrantTypeFlowRule(): GrantTypeFlowRule
    {
        if (null === $this->grantTypeFlowRule) {
            $this->grantTypeFlowRule = new GrantTypeFlowRule(
                $this->getGrantTypeManager(),
                $this->getResponseTypeManager()
            );
        }

        return $this->grantTypeFlowRule;
    }

    /**
     * @var null|GrantTypeManager
     */
    private $grantTypeManager = null;

    /**
     * @return GrantTypeManager
     */
    public function getGrantTypeManager(): GrantTypeManager
    {
        if (null === $this->grantTypeManager) {
            $this->grantTypeManager = new GrantTypeManager();
            $this->grantTypeManager->add($this->getAuthorizationCodeGrantType());
            $this->grantTypeManager->add($this->getClientCredentialsGrantType());
            $this->grantTypeManager->add($this->getJWTBearerGrantType());
            $this->grantTypeManager->add($this->getResourceOwnerPasswordCredentialsGrantType());
            $this->grantTypeManager->add($this->getRefreshTokenGrantType());
        }

        return $this->grantTypeManager;
    }

    /**
     * @var null|ResponseTypeManager
     */
    private $responseTypeManager = null;

    /**
     * @return ResponseTypeManager
     */
    public function getResponseTypeManager(): ResponseTypeManager
    {
        if (null === $this->responseTypeManager) {
            $this->responseTypeManager = new ResponseTypeManager();
            $this->responseTypeManager->add($this->getCodeResponseType());
            $this->responseTypeManager->add($this->getTokenResponseType());
            $this->responseTypeManager->add($this->getIdTokenResponseType());
            $this->responseTypeManager->add($this->getNoneResponseType());
        }

        return $this->responseTypeManager;
    }

    /**
     * @var null|ClientCredentialsGrantType
     */
    private $clientCredentialsGrantType = null;

    /**
     * @return ClientCredentialsGrantType
     */
    public function getClientCredentialsGrantType(): ClientCredentialsGrantType
    {
        if (null === $this->clientCredentialsGrantType) {
            $this->clientCredentialsGrantType = new ClientCredentialsGrantType(false);
        }

        return $this->clientCredentialsGrantType;
    }

    /**
     * @var null|AuthorizationCodeGrantType
     */
    private $authorizationCodeGrantType = null;

    /**
     * @return AuthorizationCodeGrantType
     */
    public function getAuthorizationCodeGrantType(): AuthorizationCodeGrantType
    {
        if (null === $this->authorizationCodeGrantType) {
            $this->authorizationCodeGrantType = new AuthorizationCodeGrantType(
                $this->getAuthorizationCodeRepository(),
                $this->getPKCEMethodManager(),
                $this->getCommandBus()
            );
        }

        return $this->authorizationCodeGrantType;
    }

    /**
     * @var null|RefreshTokenGrantType
     */
    private $refreshTokenGrantType = null;

    /**
     * @return RefreshTokenGrantType
     */
    public function getRefreshTokenGrantType(): RefreshTokenGrantType
    {
        if (null === $this->refreshTokenGrantType) {
            $this->refreshTokenGrantType = new RefreshTokenGrantType(
                $this->getRefreshTokenRepository()
            );
        }

        return $this->refreshTokenGrantType;
    }

    /**
     * @var null|ResourceOwnerPasswordCredentialsGrantType
     */
    private $resourceOwnerPasswordCredentialsGrantType = null;

    /**
     * @return ResourceOwnerPasswordCredentialsGrantType
     */
    public function getResourceOwnerPasswordCredentialsGrantType(): ResourceOwnerPasswordCredentialsGrantType
    {
        if (null === $this->resourceOwnerPasswordCredentialsGrantType) {
            $this->resourceOwnerPasswordCredentialsGrantType = new ResourceOwnerPasswordCredentialsGrantType(
                $this->getUserAccountManager(),
                $this->getUserAccountRepository(),
                true,
                false
            );
        }

        return $this->resourceOwnerPasswordCredentialsGrantType;
    }

    /**
     * @var null|JWTBearerGrantType
     */
    private $jwtBearerGrantType = null;

    /**
     * @return JWTBearerGrantType
     */
    public function getJWTBearerGrantType(): JWTBearerGrantType
    {
        if (null === $this->jwtBearerGrantType) {
            $this->jwtBearerGrantType = new JWTBearerGrantType($this->getJwtLoader(), $this->getClientRepository(), $this->getUserAccountRepository());
            $this->jwtBearerGrantType->enableEncryptedAssertions(false, $this->getPrivateKeys());

            $publicKeys = new JWKSet();
            $publicKeys->addKey(new JWK([
                'kty' => 'RSA',
                'kid' => 'bilbo.baggins@hobbiton.example',
                'use' => 'sig',
                'n' => 'n4EPtAOCc9AlkeQHPzHStgAbgs7bTZLwUBZdR8_KuKPEHLd4rHVTeT-O-XV2jRojdNhxJWTDvNd7nqQ0VEiZQHz_AJmSCpMaJMRBSFKrKb2wqVwGU_NsYOYL-QtiWN2lbzcEe6XC0dApr5ydQLrHqkHHig3RBordaZ6Aj-oBHqFEHYpPe7Tpe-OfVfHd1E6cS6M1FZcD1NNLYD5lFHpPI9bTwJlsde3uhGqC0ZCuEHg8lhzwOHrtIQbS0FVbb9k3-tVTU4fg_3L_vniUFAKwuCLqKnS2BYwdq_mzSnbLY7h_qixoR7jig3__kRhuaxwUkRz5iaiQkqgc5gHdrNP5zw',
                'e' => 'AQAB',
                'd' => 'bWUC9B-EFRIo8kpGfh0ZuyGPvMNKvYWNtB_ikiH9k20eT-O1q_I78eiZkpXxXQ0UTEs2LsNRS-8uJbvQ-A1irkwMSMkK1J3XTGgdrhCku9gRldY7sNA_AKZGh-Q661_42rINLRCe8W-nZ34ui_qOfkLnK9QWDDqpaIsA-bMwWWSDFu2MUBYwkHTMEzLYGqOe04noqeq1hExBTHBOBdkMXiuFhUq1BU6l-DqEiWxqg82sXt2h-LMnT3046AOYJoRioz75tSUQfGCshWTBnP5uDjd18kKhyv07lhfSJdrPdM5Plyl21hsFf4L_mHCuoFau7gdsPfHPxxjVOcOpBrQzwQ',
                'p' => '3Slxg_DwTXJcb6095RoXygQCAZ5RnAvZlno1yhHtnUex_fp7AZ_9nRaO7HX_-SFfGQeutao2TDjDAWU4Vupk8rw9JR0AzZ0N2fvuIAmr_WCsmGpeNqQnev1T7IyEsnh8UMt-n5CafhkikzhEsrmndH6LxOrvRJlsPp6Zv8bUq0k',
                'q' => 'uKE2dh-cTf6ERF4k4e_jy78GfPYUIaUyoSSJuBzp3Cubk3OCqs6grT8bR_cu0Dm1MZwWmtdqDyI95HrUeq3MP15vMMON8lHTeZu2lmKvwqW7anV5UzhM1iZ7z4yMkuUwFWoBvyY898EXvRD-hdqRxHlSqAZ192zB3pVFJ0s7pFc',
                'dp' => 'B8PVvXkvJrj2L-GYQ7v3y9r6Kw5g9SahXBwsWUzp19TVlgI-YV85q1NIb1rxQtD-IsXXR3-TanevuRPRt5OBOdiMGQp8pbt26gljYfKU_E9xn-RULHz0-ed9E9gXLKD4VGngpz-PfQ_q29pk5xWHoJp009Qf1HvChixRX59ehik',
                'dq' => 'CLDmDGduhylc9o7r84rEUVn7pzQ6PF83Y-iBZx5NT-TpnOZKF1pErAMVeKzFEl41DlHHqqBLSM0W1sOFbwTxYWZDm6sI6og5iTbwQGIC3gnJKbi_7k_vJgGHwHxgPaX2PnvP-zyEkDERuf-ry4c_Z11Cq9AqC2yeL6kdKT1cYF8',
                'qi' => '3PiqvXQN0zwMeE-sBvZgi289XP9XCQF3VWqPzMKnIgQp7_Tugo6-NZBKCQsMf3HaEGBjTVJs_jcK8-TRXvaKe-7ZMaQj8VfBdYkssbu0NKDDhjJ-GtiseaDVWt7dcH0cfwxgFUHpQh7FoCrjFJ6h6ZEpMF6xmujs4qMpPz8aaI4',
            ]));
            $this->jwtBearerGrantType->addTrustedIssuer(new TrustedIssuer(
                'https://my.trusted.issuer',
                ['RS256'],
                $publicKeys
            ));
        }

        return $this->jwtBearerGrantType;
    }

    /**
     * @var null|UserAccountRepositoryInterface
     */
    private $userAccountRepository = null;

    /**
     * @return UserAccountRepositoryInterface
     */
    public function getUserAccountRepository(): UserAccountRepositoryInterface
    {
        if (null === $this->userAccountRepository) {
            $this->userAccountRepository = new UserAccountRepository();
        }

        return $this->userAccountRepository;
    }

    /**
     * @var null|UserAccountManagerInterface
     */
    private $userAccountManager = null;

    /**
     * @return UserAccountManagerInterface
     */
    public function getUserAccountManager(): UserAccountManagerInterface
    {
        if (null === $this->userAccountManager) {
            $this->userAccountManager = new UserAccountManager(
                $this->getUserAccountRepository()
            );
        }

        return $this->userAccountManager;
    }

    /**
     * @var null|PKCEMethodManager
     */
    private $pkceMethodManager = null;

    /**
     * @var null|PKCEMethodInterface
     */
    private $pkceMethodPlain = null;

    /**
     * @var null|PKCEMethodInterface
     */
    private $pkceMethodS256 = null;

    /**
     * @return PKCEMethodManager
     */
    public function getPKCEMethodManager(): PKCEMethodManager
    {
        if (null === $this->pkceMethodManager) {
            $this->pkceMethodManager = new PKCEMethodManager();
            $this->pkceMethodManager
                ->add($this->getPKCEMethodPlain())
                ->add($this->getPKCEMethodS256());
        }

        return $this->pkceMethodManager;
    }

    /**
     * @return PKCEMethodInterface
     */
    protected function getPKCEMethodPlain(): PKCEMethodInterface
    {
        if (null === $this->pkceMethodPlain) {
            $this->pkceMethodPlain = new Plain();
        }

        return $this->pkceMethodPlain;
    }

    /**
     * @return PKCEMethodInterface
     */
    protected function getPKCEMethodS256(): PKCEMethodInterface
    {
        if (null === $this->pkceMethodS256) {
            $this->pkceMethodS256 = new S256();
        }

        return $this->pkceMethodS256;
    }

    /**
     * @var null|ScopeRepository
     */
    private $scopeRepository = null;

    /**
     * @var null|ScopePolicyInterface
     */
    private $scopePolicyDefault = null;

    /**
     * @var null|ScopePolicyInterface
     */
    private $scopePolicyError = null;

    /**
     * @return ScopeRepositoryInterface
     */
    public function getScopeRepository(): ScopeRepositoryInterface
    {
        if (null === $this->scopeRepository) {
            $this->scopeRepository = new ScopeRepository(
                ['data_read', 'data_write', 'openid', 'profile', 'email', 'phone', 'address', 'offline_access']
            );
            $this->scopeRepository
                ->addScopePolicy($this->getScopePolicyNone())
                ->addScopePolicy($this->getScopePolicyDefault())
                ->addScopePolicy($this->getScopePolicyError());
        }

        return $this->scopeRepository;
    }

    /**
     * @var null|ScopePolicyManager
     */
    private $scopePolicyManager = null;

    /**
     * @return ScopePolicyManager
     */
    public function getScopePolicyManager(): ScopePolicyManager
    {
        if (null === $this->scopePolicyManager) {
            $this->scopePolicyManager = new ScopePolicyManager();
            $this->scopePolicyManager
                ->add($this->getScopePolicyNone())
                ->add($this->getScopePolicyDefault())
                ->add($this->getScopePolicyError());
        }

        return $this->scopePolicyManager;
    }

    /**
     * @return ScopePolicyInterface
     */
    public function getScopePolicyNone(): ScopePolicyInterface
    {
        return new NoScopePolicy();
    }

    /**
     * @return ScopePolicyInterface
     */
    public function getScopePolicyDefault(): ScopePolicyInterface
    {
        if (null === $this->scopePolicyDefault) {
            $this->scopePolicyDefault = new DefaultScopePolicy([
                'data_read',
            ]);
        }

        return $this->scopePolicyDefault;
    }

    /**
     * @return ScopePolicyInterface
     */
    public function getScopePolicyError(): ScopePolicyInterface
    {
        if (null === $this->scopePolicyError) {
            $this->scopePolicyError = new ErrorScopePolicy();
        }

        return $this->scopePolicyError;
    }

    /**
     * @var null|InitialAccessTokenMiddleware
     */
    private $initialAccessTokenMiddleware = null;

    /**
     * @return InitialAccessTokenMiddleware
     */
    public function getInitialAccessTokenMiddleware(): InitialAccessTokenMiddleware
    {
        if (null === $this->initialAccessTokenMiddleware) {
            $this->initialAccessTokenMiddleware = new InitialAccessTokenMiddleware(
                $this->getBearerTokenType(),
                $this->getInitialAccessTokenRepository()
            );
        }

        return $this->initialAccessTokenMiddleware;
    }

    /**
     * @var null|BearerToken
     */
    private $bearerTokenType = null;

    /**
     * @return BearerToken
     */
    public function getBearerTokenType(): BearerToken
    {
        if (null === $this->bearerTokenType) {
            $this->bearerTokenType = new BearerToken(
                '**My Service**',
                true,
                false,
                false
            );
        }

        return $this->bearerTokenType;
    }

    /**
     * @var null|MacToken
     */
    private $macTokenType = null;

    /**
     * @return MacToken
     */
    public function getMacTokenType(): MacToken
    {
        if (null === $this->macTokenType) {
            $this->macTokenType = new MacToken('hmac-sha-256', 30);
        }

        return $this->macTokenType;
    }

    /**
     * @var null|InitialAccessTokenRepositoryInterface
     */
    private $initialAccessTokenRepository = null;

    /**
     * @return InitialAccessTokenRepositoryInterface
     */
    public function getInitialAccessTokenRepository(): InitialAccessTokenRepositoryInterface
    {
        if (null === $this->initialAccessTokenRepository) {
            $this->initialAccessTokenRepository = new InitialAccessTokenRepository(
                $this->getInitialAccessTokenEventStore(),
                $this->getPublicMessageRecorder()
            );
        }

        return $this->initialAccessTokenRepository;
    }

    /**
     * @var null|JWTCreator
     */
    private $jwtCreator = null;

    /**
     * @var null|JWTLoader
     */
    private $jwtLoader = null;

    /**
     * @var null|Signer
     */
    private $jwtSigner = null;

    /**
     * @var null|Verifier
     */
    private $jwtVerifier = null;

    /**
     * @var null|Encrypter
     */
    private $jwtEncrypter = null;

    /**
     * @var null|Decrypter
     */
    private $jwtDecrypter = null;

    /**
     * @var null|CheckerManager
     */
    private $jwtCheckerManager = null;

    /**
     * @return JWTCreator
     */
    public function getJwtCreator(): JWTCreator
    {
        if (null === $this->jwtCreator) {
            $this->jwtCreator = new JWTCreator(
                $this->getJwtSigner()
            );
            $this->jwtCreator->enableEncryptionSupport(
                $this->getJwtEncrypter()
            );
        }

        return $this->jwtCreator;
    }

    /**
     * @return JWTLoader
     */
    public function getJwtLoader(): JWTLoader
    {
        if (null === $this->jwtLoader) {
            $this->jwtLoader = new JWTLoader(
                $this->getJwtChecker(),
                $this->getJwtVerifier()
            );

            $this->jwtLoader->enableDecryptionSupport(
                $this->getJwtDecrypter()
            );
        }

        return $this->jwtLoader;
    }

    private function getJwtChecker(): CheckerManager
    {
        if (null === $this->jwtCheckerManager) {
            $this->jwtCheckerManager = new CheckerManager();
            //$this->jwtCheckerManager->addHeaderChecker(new CriticalHeaderChecker());
            //$this->jwtCheckerManager->addClaimChecker(new IssuedAtChecker());
            //$this->jwtCheckerManager->addClaimChecker(new NotBeforeChecker());
            //$this->jwtCheckerManager->addClaimChecker(new ExpirationTimeChecker());
            //$this->jwtCheckerManager->addClaimChecker(new SubjectChecker());
        }

        return $this->jwtCheckerManager;
    }

    private function getJwtSigner(): Signer
    {
        if (null === $this->jwtSigner) {
            $this->jwtSigner = new Signer([
                'HS256',
                'RS256',
                'ES256',
                'none',
            ]);
        }

        return $this->jwtSigner;
    }

    private function getJwtVerifier(): Verifier
    {
        if (null === $this->jwtVerifier) {
            $this->jwtVerifier = new Verifier([
                'HS256',
                'RS256',
                'ES256',
                'none',
            ]);
        }

        return $this->jwtVerifier;
    }

    private function getJwtEncrypter(): Encrypter
    {
        if (null === $this->jwtEncrypter) {
            $this->jwtEncrypter = new Encrypter(
                ['RSA-OAEP', 'RSA-OAEP-256'],
                ['A256GCM', 'A256CBC-HS512'],
                ['DEF']
            );
        }

        return $this->jwtEncrypter;
    }

    private function getJwtDecrypter(): Decrypter
    {
        if (null === $this->jwtDecrypter) {
            $this->jwtDecrypter = new Decrypter(
                ['RSA-OAEP', 'RSA-OAEP-256'],
                ['A256GCM', 'A256CBC-HS512'],
                ['DEF']
            );
        }

        return $this->jwtDecrypter;
    }

    /**
     * @var null|ClientConfigurationEndpoint
     */
    private $clientConfigurationEndpoint = null;

    /**
     * @return ClientConfigurationEndpoint
     */
    public function getClientConfigurationEndpoint(): ClientConfigurationEndpoint
    {
        if (null === $this->clientConfigurationEndpoint) {
            $this->clientConfigurationEndpoint = new ClientConfigurationEndpoint(
                $this->getBearerTokenType(),
                $this->getCommandBus(),
                $this->getResponseFactory()
            );
        }

        return $this->clientConfigurationEndpoint;
    }

    /**
     * @var null|Pipe
     */
    private $clientConfigurationPipe = null;

    /**
     * @return Pipe
     */
    public function getClientConfigurationPipe(): Pipe
    {
        if (null === $this->clientConfigurationPipe) {
            $this->clientConfigurationPipe = new Pipe();

            $this->clientConfigurationPipe->appendMiddleware($this->getOAuth2ResponseMiddleware());
            $this->clientConfigurationPipe->appendMiddleware($this->getClientConfigurationEndpoint());
        }

        return $this->clientConfigurationPipe;
    }

    /**
     * @var null|TokenTypeHintManager
     */
    private $tokenTypeHintManager = null;

    /**
     * @return TokenTypeHintManager
     */
    public function getTokenTypeHintManager(): TokenTypeHintManager
    {
        if (null === $this->tokenTypeHintManager) {
            $this->tokenTypeHintManager = new TokenTypeHintManager();
            $this->tokenTypeHintManager->add($this->getAccessTokenTypeHint()); // Access Token
            $this->tokenTypeHintManager->add($this->getRefreshTokenTypeHint()); // Refresh Token
            $this->tokenTypeHintManager->add($this->getAuthCodeTypeHint()); // Auth Code
        }

        return $this->tokenTypeHintManager;
    }

    /**
     * @var null|TokenRevocationGetEndpoint
     */
    private $tokenRevocationGetEndpoint = null;

    /**
     * @return TokenRevocationGetEndpoint
     */
    public function getTokenRevocationGetEndpoint(): TokenRevocationGetEndpoint
    {
        if (null === $this->tokenRevocationGetEndpoint) {
            $this->tokenRevocationGetEndpoint = new TokenRevocationGetEndpoint(
                $this->getTokenTypeHintManager(),
                $this->getResponseFactory(),
                true
            );
        }

        return $this->tokenRevocationGetEndpoint;
    }

    /**
     * @var null|TokenRevocationPostEndpoint
     */
    private $tokenRevocationPostEndpoint = null;

    /**
     * @return TokenRevocationPostEndpoint
     */
    public function getTokenRevocationPostEndpoint(): TokenRevocationPostEndpoint
    {
        if (null === $this->tokenRevocationPostEndpoint) {
            $this->tokenRevocationPostEndpoint = new TokenRevocationPostEndpoint(
                $this->getTokenTypeHintManager(),
                $this->getResponseFactory()
            );
        }

        return $this->tokenRevocationPostEndpoint;
    }

    /**
     * @var null|Pipe
     */
    private $tokenRevocationPipe = null;

    /**
     * @return Pipe
     */
    public function getTokenRevocationPipe(): Pipe
    {
        if (null === $this->tokenRevocationPipe) {
            $this->tokenRevocationPipe = new Pipe();

            $this->tokenRevocationPipe->appendMiddleware($this->getOAuth2ResponseMiddleware());
            $this->tokenRevocationPipe->appendMiddleware($this->getClientAuthenticationMiddlewareWithRequirement());
            $this->tokenRevocationPipe->appendMiddleware($this->getTokenRevocationHttpMethod());
        }

        return $this->tokenRevocationPipe;
    }

    /**
     * @var null|HttpMethod
     */
    private $tokenRevocationHttpMethod = null;

    /**
     * @return HttpMethod
     */
    public function getTokenRevocationHttpMethod(): HttpMethod
    {
        if (null === $this->tokenRevocationHttpMethod) {
            $this->tokenRevocationHttpMethod = new HttpMethod();
            $this->tokenRevocationHttpMethod->addMiddleware('POST', $this->getTokenRevocationPostEndpoint());
            $this->tokenRevocationHttpMethod->addMiddleware('GET', $this->getTokenRevocationGetEndpoint());
        }

        return $this->tokenRevocationHttpMethod;
    }

    /**
     * @var null|TokenIntrospectionEndpoint
     */
    private $tokenIntrospectionEndpoint = null;

    /**
     * @return TokenIntrospectionEndpoint
     */
    public function getTokenIntrospectionEndpoint(): TokenIntrospectionEndpoint
    {
        if (null === $this->tokenIntrospectionEndpoint) {
            $this->tokenIntrospectionEndpoint = new TokenIntrospectionEndpoint(
                $this->getTokenTypeHintManager(),
                $this->getResponseFactory()
            );
        }

        return $this->tokenIntrospectionEndpoint;
    }

    /**
     * @var null|Pipe
     */
    private $tokenIntrospectionPipe = null;

    /**
     * @return Pipe
     */
    public function getTokenIntrospectionPipe(): Pipe
    {
        if (null === $this->tokenIntrospectionPipe) {
            $this->tokenIntrospectionPipe = new Pipe();

            $this->tokenIntrospectionPipe->appendMiddleware(new IpAddressMiddleware());
            $this->tokenIntrospectionPipe->appendMiddleware($this->getOAuth2ResponseMiddleware());
            $this->tokenIntrospectionPipe->appendMiddleware($this->getResourceServerAuthenticationMiddleware());
            $this->tokenIntrospectionPipe->appendMiddleware($this->getTokenIntrospectionHttpMethod());
        }

        return $this->tokenIntrospectionPipe;
    }

    /**
     * @var null|HttpMethod
     */
    private $tokenIntrospectionHttpMethod = null;

    /**
     * @return HttpMethod
     */
    public function getTokenIntrospectionHttpMethod(): HttpMethod
    {
        if (null === $this->tokenIntrospectionHttpMethod) {
            $this->tokenIntrospectionHttpMethod = new HttpMethod();
            $this->tokenIntrospectionHttpMethod->addMiddleware('POST', $this->getTokenIntrospectionEndpoint());
        }

        return $this->tokenIntrospectionHttpMethod;
    }

    /**
     * @var null|AccessTokenTypeHint
     */
    private $accessTokenTypeHint = null;

    /**
     * @return AccessTokenTypeHint
     */
    public function getAccessTokenTypeHint(): AccessTokenTypeHint
    {
        if (null === $this->accessTokenTypeHint) {
            $this->accessTokenTypeHint = new AccessTokenTypeHint(
                $this->getAccessTokenRepository(),
                $this->getCommandBus()
            );
        }

        return $this->accessTokenTypeHint;
    }

    /**
     * @var null|RefreshTokenTypeHint
     */
    private $refreshTokenTypeHint = null;

    /**
     * @return RefreshTokenTypeHint
     */
    public function getRefreshTokenTypeHint(): RefreshTokenTypeHint
    {
        if (null === $this->refreshTokenTypeHint) {
            $this->refreshTokenTypeHint = new RefreshTokenTypeHint(
                $this->getRefreshTokenRepository(),
                $this->getCommandBus()
            );
        }

        return $this->refreshTokenTypeHint;
    }

    /**
     * @var null|AuthCodeTypeHint
     */
    private $authCodeTypeHint = null;

    /**
     * @return AuthCodeTypeHint
     */
    public function getAuthCodeTypeHint(): AuthCodeTypeHint
    {
        if (null === $this->authCodeTypeHint) {
            $this->authCodeTypeHint = new AuthCodeTypeHint(
                $this->getAuthorizationCodeRepository(),
                $this->getCommandBus()
            );
        }

        return $this->authCodeTypeHint;
    }

    /**
     * @var null|AccessTokenRepositoryInterface
     */
    private $accessTokenRepository = null;

    /**
     * @return AccessTokenRepositoryInterface
     */
    public function getAccessTokenRepository(): AccessTokenRepositoryInterface
    {
        if (null === $this->accessTokenRepository) {
            $this->accessTokenRepository = new AccessTokenRepository(
                $this->getAccessTokenEventStore(),
                $this->getPublicMessageRecorder(),
                'now +10 minutes'
            );
        }

        return $this->accessTokenRepository;
    }

    /**
     * @var null|RefreshTokenRepositoryInterface
     */
    private $refreshTokenRepository = null;

    /**
     * @return RefreshTokenRepositoryInterface
     */
    public function getRefreshTokenRepository(): RefreshTokenRepositoryInterface
    {
        if (null === $this->refreshTokenRepository) {
            $this->refreshTokenRepository = new RefreshTokenRepository(
                $this->getRefreshTokenEventStore(),
                $this->getPublicMessageRecorder(),
                'now +7 day'
            );
        }

        return $this->refreshTokenRepository;
    }

    /**
     * @var null|EventStoreInterface
     */
    private $accessTokenEventStore = null;

    /**
     * @return EventStoreInterface
     */
    public function getAccessTokenEventStore(): EventStoreInterface
    {
        if (null === $this->accessTokenEventStore) {
            $this->accessTokenEventStore = new EventStore(
                $this->getDomainConverter()
            );
        }

        return $this->accessTokenEventStore;
    }

    /**
     * @var null|EventStoreInterface
     */
    private $initialAccessTokenEventStore = null;

    /**
     * @return EventStoreInterface
     */
    public function getInitialAccessTokenEventStore(): EventStoreInterface
    {
        if (null === $this->initialAccessTokenEventStore) {
            $this->initialAccessTokenEventStore = new EventStore(
                $this->getDomainConverter()
            );
        }

        return $this->initialAccessTokenEventStore;
    }

    /**
     * @var null|EventStoreInterface
     */
    private $preConfiguredAuthorizationEventStore = null;

    /**
     * @return EventStoreInterface
     */
    public function getPreConfiguredAuthorizationEventStore(): EventStoreInterface
    {
        if (null === $this->preConfiguredAuthorizationEventStore) {
            $this->preConfiguredAuthorizationEventStore = new EventStore(
                $this->getDomainConverter()
            );
        }

        return $this->preConfiguredAuthorizationEventStore;
    }

    /**
     * @var null|EventStoreInterface
     */
    private $refreshTokenEventStore = null;

    /**
     * @return EventStoreInterface
     */
    public function getRefreshTokenEventStore(): EventStoreInterface
    {
        if (null === $this->refreshTokenEventStore) {
            $this->refreshTokenEventStore = new EventStore(
                $this->getDomainConverter()
            );
        }

        return $this->refreshTokenEventStore;
    }

    /**
     * @var null|EventStoreInterface
     */
    private $authCodeEventStore = null;

    /**
     * @return EventStoreInterface
     */
    public function getAuthCodeEventStore(): EventStoreInterface
    {
        if (null === $this->authCodeEventStore) {
            $this->authCodeEventStore = new EventStore(
                $this->getDomainConverter()
            );
        }

        return $this->authCodeEventStore;
    }

    /**
     * @var null|EventStoreInterface
     */
    private $clientEventStore = null;

    /**
     * @return EventStoreInterface
     */
    public function getClientEventStore(): EventStoreInterface
    {
        if (null === $this->clientEventStore) {
            $this->clientEventStore = new EventStore(
                $this->getDomainConverter()
            );
        }

        return $this->clientEventStore;
    }

    /**
     * @var null|EventStoreInterface
     */
    private $resourceServerEventStore = null;

    /**
     * @return EventStoreInterface
     */
    public function getResourceServerEventStore(): EventStoreInterface
    {
        if (null === $this->resourceServerEventStore) {
            $this->resourceServerEventStore = new EventStore(
                $this->getDomainConverter()
            );
        }

        return $this->resourceServerEventStore;
    }

    /**
     * @var null|AuthCodeRepositoryInterface
     */
    private $authCodeRepository = null;

    /**
     * @return AuthCodeRepositoryInterface
     */
    public function getAuthorizationCodeRepository(): AuthCodeRepositoryInterface
    {
        if (null === $this->authCodeRepository) {
            $this->authCodeRepository = new AuthCodeRepository(
                $this->getAuthCodeEventStore(),
                $this->getPublicMessageRecorder(),
                'now +30 seconds'
            );
        }

        return $this->authCodeRepository;
    }

    /**
     * @var null|RevokeAccessTokenCommandHandler
     */
    private $revokeAccessTokenCommandHandler = null;

    /**
     * @return RevokeAccessTokenCommandHandler
     */
    public function getRevokeAccessTokenCommandHandler(): RevokeAccessTokenCommandHandler
    {
        if (null === $this->revokeAccessTokenCommandHandler) {
            $this->revokeAccessTokenCommandHandler = new RevokeAccessTokenCommandHandler(
                $this->getAccessTokenRepository()
            );
        }

        return $this->revokeAccessTokenCommandHandler;
    }

    /**
     * @var null|AccessTokenRevokedEventHandler
     */
    private $accessTokenRevokedEventHandler = null;

    /**
     * @return AccessTokenRevokedEventHandler
     */
    public function getAccessTokenRevokedEventHandler(): AccessTokenRevokedEventHandler
    {
        if (null === $this->accessTokenRevokedEventHandler) {
            $this->accessTokenRevokedEventHandler = new AccessTokenRevokedEventHandler();
        }

        return $this->accessTokenRevokedEventHandler;
    }

    /**
     * @var null|AccessTokenCreatedEventHandler
     */
    private $accessTokenCreatedEventHandler = null;

    /**
     * @return AccessTokenCreatedEventHandler
     */
    public function getAccessTokenCreatedEventHandler(): AccessTokenCreatedEventHandler
    {
        if (null === $this->accessTokenCreatedEventHandler) {
            $this->accessTokenCreatedEventHandler = new AccessTokenCreatedEventHandler();
        }

        return $this->accessTokenCreatedEventHandler;
    }

    /**
     * @var null|RefreshTokenCreatedEventHandler
     */
    private $refreshTokenCreatedEventHandler = null;

    /**
     * @return RefreshTokenCreatedEventHandler
     */
    public function getRefreshTokenCreatedEventHandler(): RefreshTokenCreatedEventHandler
    {
        if (null === $this->refreshTokenCreatedEventHandler) {
            $this->refreshTokenCreatedEventHandler = new RefreshTokenCreatedEventHandler();
        }

        return $this->refreshTokenCreatedEventHandler;
    }

    /**
     * @var null|RefreshTokenCreatedEventHandler
     */
    private $refreshTokenRevokedEventHandler = null;

    /**
     * @return RefreshTokenRevokedEventHandler
     */
    public function getRefreshTokenRevokedEventHandler(): RefreshTokenRevokedEventHandler
    {
        if (null === $this->refreshTokenRevokedEventHandler) {
            $this->refreshTokenRevokedEventHandler = new RefreshTokenRevokedEventHandler();
        }

        return $this->refreshTokenRevokedEventHandler;
    }

    /**
     * @var null|CreateRefreshTokenCommandHandler
     */
    private $createRefreshTokenCommandHandler = null;

    /**
     * @return CreateRefreshTokenCommandHandler
     */
    public function getCreateRefreshTokenCommandHandler(): CreateRefreshTokenCommandHandler
    {
        if (null === $this->createRefreshTokenCommandHandler) {
            $this->createRefreshTokenCommandHandler = new CreateRefreshTokenCommandHandler(
                $this->getRefreshTokenRepository()
            );
        }

        return $this->createRefreshTokenCommandHandler;
    }

    /**
     * @var null|RevokeRefreshTokenCommandHandler
     */
    private $revokeRefreshTokenCommandHandler = null;

    /**
     * @return RevokeRefreshTokenCommandHandler
     */
    public function getRevokeRefreshTokenCommandHandler(): RevokeRefreshTokenCommandHandler
    {
        if (null === $this->revokeRefreshTokenCommandHandler) {
            $this->revokeRefreshTokenCommandHandler = new RevokeRefreshTokenCommandHandler(
                $this->getRefreshTokenRepository()
            );
        }

        return $this->revokeRefreshTokenCommandHandler;
    }

    /**
     * @var null|CreateAuthCodeCommandHandler
     */
    private $createAuthCodeCommandHandler = null;

    /**
     * @return CreateAuthCodeCommandHandler
     */
    public function getCreateAuthCodeCommandHandler(): CreateAuthCodeCommandHandler
    {
        if (null === $this->createAuthCodeCommandHandler) {
            $this->createAuthCodeCommandHandler = new CreateAuthCodeCommandHandler(
                $this->getAuthorizationCodeRepository()
            );
        }

        return $this->createAuthCodeCommandHandler;
    }

    /**
     * @var null|MarkAuthCodeAsUsedCommandHandler
     */
    private $markAuthCodeAsUsedCommandHandler = null;

    /**
     * @return MarkAuthCodeAsUsedCommandHandler
     */
    public function getMarkAuthCodeAsUsedCommandHandler(): MarkAuthCodeAsUsedCommandHandler
    {
        if (null === $this->markAuthCodeAsUsedCommandHandler) {
            $this->markAuthCodeAsUsedCommandHandler = new MarkAuthCodeAsUsedCommandHandler(
                $this->getAuthorizationCodeRepository()
            );
        }

        return $this->markAuthCodeAsUsedCommandHandler;
    }

    /**
     * @var null|RevokeAuthCodeCommandHandler
     */
    private $revokeAuthCodeCommandHandler = null;

    /**
     * @return RevokeAuthCodeCommandHandler
     */
    public function getRevokeAuthCodeCommandHandler(): RevokeAuthCodeCommandHandler
    {
        if (null === $this->revokeAuthCodeCommandHandler) {
            $this->revokeAuthCodeCommandHandler = new RevokeAuthCodeCommandHandler(
                $this->getAuthorizationCodeRepository()
            );
        }

        return $this->revokeAuthCodeCommandHandler;
    }

    /**
     * @var null|CodeResponseType
     */
    private $grantCodeResponseType = null;

    /**
     * @return CodeResponseType
     */
    public function getCodeResponseType(): CodeResponseType
    {
        if (null === $this->grantCodeResponseType) {
            $this->grantCodeResponseType = new CodeResponseType(
                $this->getCommandBus(),
                $this->getPKCEMethodManager(),
                true
            );
        }

        return $this->grantCodeResponseType;
    }

    /**
     * @var null|TokenResponseType
     */
    private $tokenResponseType = null;

    /**
     * @return TokenResponseType
     */
    public function getTokenResponseType(): TokenResponseType
    {
        if (null === $this->tokenResponseType) {
            $this->tokenResponseType = new TokenResponseType(
                $this->getCommandBus()
            );
        }

        return $this->tokenResponseType;
    }

    /**
     * @var null|IdTokenResponseType
     */
    private $idTokenResponseType = null;

    /**
     * @return IdTokenResponseType
     */
    public function getIdTokenResponseType(): IdTokenResponseType
    {
        if (null === $this->idTokenResponseType) {
            $this->idTokenResponseType = new IdTokenResponseType(
                $this->getIdTokenBuilderFactory(),
                'RS256'
            );
        }

        return $this->idTokenResponseType;
    }

    /**
     * @var null|NoneResponseType
     */
    private $noneResponseType = null;

    /**
     * @return NoneResponseType
     */
    public function getNoneResponseType(): NoneResponseType
    {
        if (null === $this->noneResponseType) {
            $this->noneResponseType = new NoneResponseType(
                $this->getCommandBus()
            );
        }

        return $this->noneResponseType;
    }

    /**
     * @var null|TokenEndpoint
     */
    private $tokenEndpoint = null;

    /**
     * @return TokenEndpoint
     */
    public function getTokenEndpoint(): TokenEndpoint
    {
        if (null === $this->tokenEndpoint) {
            $this->tokenEndpoint = new TokenEndpoint(
                $this->getProcessorManager(),
                $this->getClientRepository(),
                $this->getUserAccountRepository(),
                $this->getTokenEndpointExtensionManager(),
                $this->getResponseFactory(),
                $this->getCommandBus()
            );
        }

        return $this->tokenEndpoint;
    }

    /**
     * @var null|ProcessorManager
     */
    private $processorManager = null;

    /**
     * @return ProcessorManager
     */
    public function getProcessorManager(): ProcessorManager
    {
        if (null === $this->processorManager) {
            $this->processorManager = new ProcessorManager(
                $this->getScopeRepository()
            );
        }

        return $this->processorManager;
    }

    /**
     * @var null|TokenTypeManager
     */
    private $tokenTypeManager = null;

    /**
     * @return TokenTypeManager
     */
    public function getTokenTypeManager(): TokenTypeManager
    {
        if (null === $this->tokenTypeManager) {
            $this->tokenTypeManager = new TokenTypeManager();
            $this->tokenTypeManager->add($this->getBearerTokenType());
            $this->tokenTypeManager->add($this->getMacTokenType());
        }

        return $this->tokenTypeManager;
    }

    /**
     * @var null|GrantTypeMiddleware
     */
    private $grantTypeMiddleware = null;

    /**
     * @return GrantTypeMiddleware
     */
    public function getGrantTypeMiddleware(): GrantTypeMiddleware
    {
        if (null === $this->grantTypeMiddleware) {
            $this->grantTypeMiddleware = new GrantTypeMiddleware(
                $this->getGrantTypeManager()
            );
        }

        return $this->grantTypeMiddleware;
    }

    /**
     * @var null|Pipe
     */
    private $tokenEndpointPipe = null;

    /**
     * @return Pipe
     */
    public function getTokenEndpointPipe(): Pipe
    {
        if (null === $this->tokenEndpointPipe) {
            $this->tokenEndpointPipe = new Pipe();
            $this->tokenEndpointPipe->appendMiddleware($this->getOAuth2ResponseMiddleware());
            $this->tokenEndpointPipe->appendMiddleware($this->getClientAuthenticationMiddleware());
            $this->tokenEndpointPipe->appendMiddleware($this->getGrantTypeMiddleware());
            $this->tokenEndpointPipe->appendMiddleware($this->getTokenTypeMiddleware());
            $this->tokenEndpointPipe->appendMiddleware($this->getTokenEndpoint());
        }

        return $this->tokenEndpointPipe;
    }

    /**
     * @var null|TokenTypeMiddleware
     */
    private $tokenTypeMiddleware = null;

    /**
     * @return TokenTypeMiddleware
     */
    public function getTokenTypeMiddleware(): TokenTypeMiddleware
    {
        if (null === $this->tokenTypeMiddleware) {
            $this->tokenTypeMiddleware = new TokenTypeMiddleware(
                $this->getTokenTypeManager(),
                true
            );
        }

        return $this->tokenTypeMiddleware;
    }

    /**
     * @var null|CreateAccessTokenCommandHandler
     */
    private $createAccessTokenCommandHandler = null;

    /**
     * @return CreateAccessTokenCommandHandler
     */
    public function getCreateAccessTokenCommandHandler(): CreateAccessTokenCommandHandler
    {
        if (null === $this->createAccessTokenCommandHandler) {
            $this->createAccessTokenCommandHandler = new CreateAccessTokenCommandHandler(
                $this->getAccessTokenRepository()
            );
        }

        return $this->createAccessTokenCommandHandler;
    }

    /**
     * @var null|CreateAccessTokenWithRefreshTokenCommandHandler
     */
    private $createAccessTokenWithRefreshTokenCommandHandler = null;

    /**
     * @return CreateAccessTokenWithRefreshTokenCommandHandler
     */
    public function getCreateAccessTokenWithRefreshTokenCommandHandler(): CreateAccessTokenWithRefreshTokenCommandHandler
    {
        if (null === $this->createAccessTokenWithRefreshTokenCommandHandler) {
            $this->createAccessTokenWithRefreshTokenCommandHandler = new CreateAccessTokenWithRefreshTokenCommandHandler(
                $this->getAccessTokenRepository(),
                $this->getRefreshTokenRepository()
            );
        }

        return $this->createAccessTokenWithRefreshTokenCommandHandler;
    }

    /**
     * @var null|UserInfoEndpoint
     */
    private $userInfoEndpoint = null;

    /**
     * @return UserInfoEndpoint
     */
    public function getUserInfoEndpoint(): UserInfoEndpoint
    {
        if (null === $this->userInfoEndpoint) {
            $this->userInfoEndpoint = new UserInfoEndpoint(
                $this->getIdTokenBuilderFactory(),
                $this->getClientRepository(),
                $this->getUserAccountRepository(),
                $this->getResponseFactory()
            );
        }

        return $this->userInfoEndpoint;
    }

    /**
     * @var null|UserInfo
     */
    private $userInfo = null;

    /**
     * @return UserInfo
     */
    public function getUserInfo(): UserInfo
    {
        if (null === $this->userInfo) {
            $this->userInfo = new UserInfo(
                $this->getUserInfoScopeSupportManager(),
                $this->getClaimSourceManager()
            );
            $this->userInfo->enablePairwiseSubject(
                $this->getPairwiseSubjectIdentifierAlgorithm(),
                true
            );
        }

        return $this->userInfo;
    }

    /**
     * @var null|PairwiseSubjectIdentifierAlgorithmInterface
     */
    private $pairwiseSubjectIdentifierAlgorithm = null;

    /**
     * @return PairwiseSubjectIdentifierAlgorithmInterface
     */
    public function getPairwiseSubjectIdentifierAlgorithm(): PairwiseSubjectIdentifierAlgorithmInterface
    {
        if (null === $this->pairwiseSubjectIdentifierAlgorithm) {
            $this->pairwiseSubjectIdentifierAlgorithm = new EncryptedSubjectIdentifier(
                $this->getPairwiseKey(),
                'aes-128-cbc',
                $this->getPairwiseAdditionalData(),
                $this->getPairwiseAdditionalData()
            );
        }

        return $this->pairwiseSubjectIdentifierAlgorithm;
    }

    /**
     * @var null|UserInfoScopeSupportManager
     */
    private $userInfoScopeSupportManager = null;

    /**
     * @return UserInfoScopeSupportManager
     */
    public function getUserInfoScopeSupportManager(): UserInfoScopeSupportManager
    {
        if (null === $this->userInfoScopeSupportManager) {
            $this->userInfoScopeSupportManager = new UserInfoScopeSupportManager();
            $this->userInfoScopeSupportManager->add(new AddressScopeSupport());
            $this->userInfoScopeSupportManager->add(new EmailScopeSupport());
            $this->userInfoScopeSupportManager->add(new PhoneScopeSupport());
            $this->userInfoScopeSupportManager->add(new ProfilScopeSupport());
        }

        return $this->userInfoScopeSupportManager;
    }

    /**
     * @var null|ClaimSourceManager
     */
    private $claimSourceManager = null;

    /**
     * @return ClaimSourceManager
     */
    public function getClaimSourceManager(): ClaimSourceManager
    {
        if (null === $this->claimSourceManager) {
            $this->claimSourceManager = new ClaimSourceManager();
            $this->claimSourceManager->add(new DistributedClaimSource());
        }

        return $this->claimSourceManager;
    }

    /**
     * @var null|Pipe
     */
    private $userInfoEndpointPipe = null;

    /**
     * @return Pipe
     */
    public function getUserInfoEndpointPipe(): Pipe
    {
        if (null === $this->userInfoEndpointPipe) {
            $this->userInfoEndpointPipe = new Pipe();
            $this->userInfoEndpointPipe->appendMiddleware($this->getOAuth2ResponseMiddleware());
            $this->userInfoEndpointPipe->appendMiddleware($this->getSecurityMiddleware());
            $this->userInfoEndpointPipe->appendMiddleware($this->getUserInfoEndpoint());
        }

        return $this->userInfoEndpointPipe;
    }

    /**
     * @var null|OAuth2SecurityMiddleware
     */
    private $securityMiddleware = null;

    /**
     * @return OAuth2SecurityMiddleware
     */
    public function getSecurityMiddleware(): OAuth2SecurityMiddleware
    {
        if (null === $this->securityMiddleware) {
            $this->securityMiddleware = new OAuth2SecurityMiddleware(
                $this->getTokenTypeManager(),
                $this->getAccessTokenHandlerManager(),
                'openid'
            );
        }

        return $this->securityMiddleware;
    }

    /**
     * @var null|AccessTokenHandlerManager
     */
    private $accessTokenHandlerManager = null;

    /**
     * @return AccessTokenHandlerManager
     */
    public function getAccessTokenHandlerManager(): AccessTokenHandlerManager
    {
        if (null === $this->accessTokenHandlerManager) {
            $this->accessTokenHandlerManager = new AccessTokenHandlerManager();
            $this->accessTokenHandlerManager->add(new AccessTokenHandlerUsingRepository(
                $this->getAccessTokenRepository()
            ));
        }

        return $this->accessTokenHandlerManager;
    }

    /**
     * @var null|IssuerDiscoveryEndpoint
     */
    private $issuerDiscoveryEndpoint = null;

    /**
     * @return IssuerDiscoveryEndpoint
     */
    public function getIssuerDiscoveryEndpoint(): IssuerDiscoveryEndpoint
    {
        if (null === $this->issuerDiscoveryEndpoint) {
            $this->issuerDiscoveryEndpoint = new IssuerDiscoveryEndpoint(
                $this->getResourceRepository(),
                $this->getResponseFactory(),
                $this->getUriFactory(),
                'https://my-service.com:9000/'
            );
        }

        return $this->issuerDiscoveryEndpoint;
    }

    /**
     * @var null|ResourceRepository
     */
    private $resourceRepository = null;

    /**
     * @return ResourceRepository
     */
    public function getResourceRepository(): ResourceRepository
    {
        if (null === $this->resourceRepository) {
            $this->resourceRepository = new ResourceRepository();
        }

        return $this->resourceRepository;
    }

    /**
     * @var null|Pipe
     */
    private $issuerDiscoveryPipe = null;

    /**
     * @return Pipe
     */
    public function getIssuerDiscoveryPipe(): Pipe
    {
        if (null === $this->issuerDiscoveryPipe) {
            $this->issuerDiscoveryPipe = new Pipe();
            $this->issuerDiscoveryPipe->appendMiddleware($this->getOAuth2ResponseMiddleware());
            $this->issuerDiscoveryPipe->appendMiddleware($this->getIssuerDiscoveryEndpoint());
        }

        return $this->issuerDiscoveryPipe;
    }

    /**
     * @var null|Pipe
     */
    private $JWKSetEndpointPipe = null;

    /**
     * @return Pipe
     */
    public function getJWKSetEndpointPipe(): Pipe
    {
        if (null === $this->JWKSetEndpointPipe) {
            $this->JWKSetEndpointPipe = new Pipe();
            $this->JWKSetEndpointPipe->appendMiddleware($this->getJWKSetEndpoint());
        }

        return $this->JWKSetEndpointPipe;
    }

    /**
     * @var null|JWKSetEndpoint
     */
    private $JWKSetEndpoint = null;

    /**
     * @return JWKSetEndpoint
     */
    public function getJWKSetEndpoint(): JWKSetEndpoint
    {
        if (null === $this->JWKSetEndpoint) {
            $this->JWKSetEndpoint = new JWKSetEndpoint(
                $this->getResponseFactory(),
                $this->getPublicKeys()
            );
        }

        return $this->JWKSetEndpoint;
    }

    /**
     * @var null|Pipe
     */
    private $iFrameEndpointPipe = null;

    /**
     * @return Pipe
     */
    public function getIFrameEndpointPipe(): Pipe
    {
        if (null === $this->iFrameEndpointPipe) {
            $this->iFrameEndpointPipe = new Pipe();
            $this->iFrameEndpointPipe->appendMiddleware($this->getIFrameEndpoint());
        }

        return $this->iFrameEndpointPipe;
    }

    /**
     * @var null|IFrameEndpoint
     */
    private $iFrameEndpoint = null;

    /**
     * @return IFrameEndpoint
     */
    public function getIFrameEndpoint(): IFrameEndpoint
    {
        if (null === $this->iFrameEndpoint) {
            $this->iFrameEndpoint = new IFrameEndpoint(
                $this->getResponseFactory());
        }

        return $this->iFrameEndpoint;
    }

    /**
     * @var null|Pipe
     */
    private $metadataEndpointPipe = null;

    /**
     * @return Pipe
     */
    public function getMetadataEndpointPipe(): Pipe
    {
        if (null === $this->metadataEndpointPipe) {
            $this->metadataEndpointPipe = new Pipe();
            $this->metadataEndpointPipe->appendMiddleware($this->getMetadataEndpoint());
        }

        return $this->metadataEndpointPipe;
    }

    /**
     * @var null|MetadataEndpoint
     */
    private $metadataEndpoint = null;

    /**
     * @return MetadataEndpoint
     */
    public function getMetadataEndpoint(): MetadataEndpoint
    {
        if (null === $this->metadataEndpoint) {
            $this->metadataEndpoint = new MetadataEndpoint(
                $this->getResponseFactory(),
                $this->getMetadata()
            );
            $this->metadataEndpoint->enableSignedMetadata(
                $this->getJwtCreator(),
                'RS256',
                $this->getPrivateKeys()
            );
        }

        return $this->metadataEndpoint;
    }

    /**
     * @var null|Metadata
     */
    private $metadata = null;

    /**
     * @return Metadata
     */
    public function getMetadata(): Metadata
    {
        if (null === $this->metadata) {
            $this->metadata = new Metadata();
            $this->metadata->set('issuer', 'https://my.server.com/');
            $this->metadata->set('authorization_endpoint', 'https://my.server.com/authorize');
            $this->metadata->set('token_endpoint', 'https://my.server.com/token');
            $this->metadata->set('userinfo_endpoint', 'https://my.server.com/user_info');
            $this->metadata->set('jwks_uri', 'https://my.server.com/jwks');
            $this->metadata->set('registration_endpoint', 'https://my.server.com/register');
            $this->metadata->set('scopes_supported', $this->getScopeRepository()->getSupportedScopes());
            $this->metadata->set('response_types_supported', $this->getResponseTypeManager()->all());
            if ($this->getResponseTypeAndResponseModeParameterChecker()->isResponseModeParameterInAuthorizationRequestAllowed()) {
                $this->metadata->set('response_modes_supported', $this->getResponseModeManager()->getSupportedResponseModes());
            }
            $this->metadata->set('grant_types_supported', $this->getGrantTypeManager()->getSupportedGrantTypes());
            $this->metadata->set('acr_values_supported', []);
            $this->metadata->set('subject_types_supported', $this->getUserInfo()->isPairwiseSubjectIdentifierSupported() ? ['public', 'pairwise'] : ['public']);
            $this->metadata->set('id_token_signing_alg_values_supported', $this->getJwtCreator()->getSupportedSignatureAlgorithms());
            $this->metadata->set('id_token_encryption_alg_values_supported', $this->getJwtCreator()->getSupportedKeyEncryptionAlgorithms());
            $this->metadata->set('id_token_encryption_enc_values_supported', $this->getJwtCreator()->getSupportedContentEncryptionAlgorithms());
            $this->metadata->set('userinfo_signing_alg_values_supported', $this->getJwtCreator()->getSupportedSignatureAlgorithms());
            $this->metadata->set('userinfo_encryption_alg_values_supported', $this->getJwtCreator()->getSupportedKeyEncryptionAlgorithms());
            $this->metadata->set('userinfo_encryption_enc_values_supported', $this->getJwtCreator()->getSupportedContentEncryptionAlgorithms());
            $this->metadata->set('request_object_signing_alg_values_supported', $this->getJWTLoader()->getSupportedSignatureAlgorithms());
            $this->metadata->set('request_object_encryption_alg_values_supported', $this->getJWTLoader()->getSupportedKeyEncryptionAlgorithms());
            $this->metadata->set('request_object_encryption_enc_values_supported', $this->getJWTLoader()->getSupportedContentEncryptionAlgorithms());
            $this->metadata->set('token_endpoint_auth_methods_supported', $this->getTokenEndpointAuthMethodManager()->getSupportedTokenEndpointAuthMethods());
            $this->metadata->set('token_endpoint_auth_signing_alg_values_supported', $this->getJWTLoader()->getSupportedSignatureAlgorithms());
            $this->metadata->set('token_endpoint_auth_encryption_alg_values_supported', $this->getJWTLoader()->getSupportedKeyEncryptionAlgorithms());
            $this->metadata->set('token_endpoint_auth_encryption_enc_values_supported', $this->getJWTLoader()->getSupportedContentEncryptionAlgorithms());
            $this->metadata->set('display_values_supported', ['page']);
            $this->metadata->set('claim_types_supported', false);
            $this->metadata->set('claims_supported', false);
            $this->metadata->set('service_documentation', 'https://my.server.com/documentation');
            $this->metadata->set('claims_locales_supported', []);
            $this->metadata->set('ui_locales_supported', ['en_US', 'fr_FR']);
            $this->metadata->set('claims_parameter_supported', false);
            $this->metadata->set('request_parameter_supported', $this->getAuthorizationRequestLoader()->isRequestObjectSupportEnabled());
            $this->metadata->set('request_uri_parameter_supported', $this->getAuthorizationRequestLoader()->isRequestObjectReferenceSupportEnabled());
            $this->metadata->set('require_request_uri_registration', true);
            $this->metadata->set('op_policy_uri', 'https://my.server.com/policy.html');
            $this->metadata->set('op_tos_uri', 'https://my.server.com/tos.html');
        }

        return $this->metadata;
    }

    /**
     * @var null|TokenEndpointExtensionManager
     */
    private $accessTokenParameterExtensionManager = null;

    /**
     * @return TokenEndpointExtensionManager
     */
    public function getTokenEndpointExtensionManager(): TokenEndpointExtensionManager
    {
        if (null === $this->accessTokenParameterExtensionManager) {
            $this->accessTokenParameterExtensionManager = new TokenEndpointExtensionManager();
            $this->accessTokenParameterExtensionManager->add($this->getOpenIdConnectExtension());
        }

        return $this->accessTokenParameterExtensionManager;
    }

    /**
     * @var null|OpenIdConnectExtension
     */
    private $openIdConnectExtension = null;

    /**
     * @return OpenIdConnectExtension
     */
    public function getOpenIdConnectExtension(): OpenIdConnectExtension
    {
        if (null === $this->openIdConnectExtension) {
            $this->openIdConnectExtension = new OpenIdConnectExtension(
                $this->getIdTokenBuilderFactory(),
                'RS256'
            );
        }

        return $this->openIdConnectExtension;
    }

    /**
     * @var null|IdTokenBuilderFactory
     */
    private $idTokenBuilderFactory = null;

    /**
     * @return IdTokenBuilderFactory
     */
    public function getIdTokenBuilderFactory(): IdTokenBuilderFactory
    {
        if (null === $this->idTokenBuilderFactory) {
            $this->idTokenBuilderFactory = new IdTokenBuilderFactory(
                $this->getJwtCreator(),
                'https://www.my-service.com',
                $this->getUserInfo(),
                $this->getPrivateKeys(),
                600
            );
        }

        return $this->idTokenBuilderFactory;
    }

    /**
     * @var null|IdTokenLoader
     */
    private $idTokenLoader = null;

    /**
     * @return IdTokenLoader
     */
    public function getIdTokenLoader(): IdTokenLoader
    {
        if (null === $this->idTokenLoader) {
            $this->idTokenLoader = new IdTokenLoader(
                $this->getJwtLoader(),
                $this->getPrivateKeys(),
                'RS256'
            );
        }

        return $this->idTokenLoader;
    }

    /**
     * @var null|ParameterCheckerManager
     */
    private $parameterCheckerManager = null;

    /**
     * @return ParameterCheckerManager
     */
    public function getParameterCheckerManager(): ParameterCheckerManager
    {
        if (null === $this->parameterCheckerManager) {
            $this->parameterCheckerManager = new ParameterCheckerManager();
            $this->parameterCheckerManager->add($this->getResponseTypeAndResponseModeParameterChecker());
            $this->parameterCheckerManager->add(new RedirectUriParameterChecker(true, true));
            $this->parameterCheckerManager->add(new DisplayParameterChecker());
            $this->parameterCheckerManager->add(new NonceParameterChecker());
            $this->parameterCheckerManager->add(new PromptParameterChecker());
            $this->parameterCheckerManager->add(new ScopeParameterChecker(
                $this->getScopeRepository(),
                $this->getScopePolicyManager));
            $this->parameterCheckerManager->add(new StateParameterChecker(true));
            $this->parameterCheckerManager->add(new TokenTypeParameterChecker($this->getTokenTypeManager(), true));
        }

        return $this->parameterCheckerManager;
    }

    /**
     * @var null|ResponseTypeAndResponseModeParameterChecker
     */
    private $responseTypeAndResponseModeParameterChecker = null;

    /**
     * @return ResponseTypeAndResponseModeParameterChecker
     */
    public function getResponseTypeAndResponseModeParameterChecker(): ResponseTypeAndResponseModeParameterChecker
    {
        if (null === $this->responseTypeAndResponseModeParameterChecker) {
            $this->responseTypeAndResponseModeParameterChecker = new ResponseTypeAndResponseModeParameterChecker(
                $this->getResponseTypeManager(),
                $this->getResponseModeManager(),
                true
            );
        }

        return $this->responseTypeAndResponseModeParameterChecker;
    }

    /**
     * @var null|ResponseModeManager
     */
    private $responseModeManager = null;

    /**
     * @return ResponseModeManager
     */
    public function getResponseModeManager(): ResponseModeManager
    {
        if (null === $this->responseModeManager) {
            $this->responseModeManager = new ResponseModeManager();
            $this->responseModeManager->add(new FragmentResponseMode(
                $this->getUriFactory(),
                $this->getResponseFactory())
            );
            $this->responseModeManager->add(new QueryResponseMode(
                $this->getUriFactory(),
                $this->getResponseFactory())
            );
            $this->responseModeManager->add(new FormPostResponseMode(
                new FormPostResponseRenderer(),
                $this->getResponseFactory())
            );
        }

        return $this->responseModeManager;
    }

    /**
     * @var null|HttpClient
     */
    private $httpClient = null;

    /**
     * @return HttpClient
     */
    public function getHttpClient(): HttpClient
    {
        if (null === $this->httpClient) {
            $this->httpClient = new Client();
        }

        return $this->httpClient;
    }

    /**
     * @var null|AuthorizationRequestLoader
     */
    private $authorizationRequestLoader = null;

    /**
     * @return AuthorizationRequestLoader
     */
    public function getAuthorizationRequestLoader(): AuthorizationRequestLoader
    {
        if (null === $this->authorizationRequestLoader) {
            $this->authorizationRequestLoader = new AuthorizationRequestLoader($this->getClientRepository());
            $this->authorizationRequestLoader->enableRequestObjectSupport($this->getJwtLoader());
            $this->authorizationRequestLoader->enableEncryptedRequestObjectSupport($this->getPrivateKeys(), false);
            $this->authorizationRequestLoader->enableRequestObjectReferenceSupport($this->getHttpClient(), true);
        }

        return $this->authorizationRequestLoader;
    }

    /**
     * @var null|AuthorizationFactory
     */
    private $authorizationFactory = null;

    /**
     * @return AuthorizationFactory
     */
    public function getAuthorizationFactory(): AuthorizationFactory
    {
        if (null === $this->authorizationFactory) {
            $this->authorizationFactory = new AuthorizationFactory($this->getAuthorizationRequestLoader(), $this->getParameterCheckerManager());
        }

        return $this->authorizationFactory;
    }

    /**
     * @var null|AuthorizationEndpoint
     */
    private $authorizationEndpoint = null;

    /**
     * @return AuthorizationEndpoint
     */
    public function getAuthorizationEndpoint(): AuthorizationEndpoint
    {
        if (null === $this->authorizationEndpoint) {
            $this->authorizationEndpoint = new AuthorizationEndpoint(
                $this->getResponseFactory(),
                $this->getAuthorizationFactory(),
                $this->getUserAccountDiscoveryManager(),
                $this->getBeforeConsentScreenManager(),
                $this->getAfterConsentScreenManager()
            );
        }

        return $this->authorizationEndpoint;
    }

    /**
     * @var null|Pipe
     */
    private $authorizationEndpointPipe = null;

    /**
     * @return Pipe
     */
    public function getAuthorizationEndpointPipe(): Pipe
    {
        if (null === $this->authorizationEndpointPipe) {
            $this->authorizationEndpointPipe = new Pipe();
            $this->authorizationEndpointPipe->appendMiddleware($this->getOAuth2ResponseMiddleware());
            $this->authorizationEndpointPipe->appendMiddleware($this->getTokenTypeMiddleware());
            $this->authorizationEndpointPipe->appendMiddleware($this->getAuthorizationEndpoint());
        }

        return $this->authorizationEndpointPipe;
    }

    /**
     * @var null|UserAccountDiscoveryManager
     */
    private $userAccountDiscoveryManager = null;

    /**
     * @return UserAccountDiscoveryManager
     */
    public function getUserAccountDiscoveryManager(): UserAccountDiscoveryManager
    {
        if (null === $this->userAccountDiscoveryManager) {
            $this->userAccountDiscoveryManager = new UserAccountDiscoveryManager();
            $this->userAccountDiscoveryManager->add($this->getIdTokenHintDiscovery());
            $this->userAccountDiscoveryManager->add($this->getSecurityLayer());
            $this->userAccountDiscoveryManager->add(new LoginParameterChecker());
            $this->userAccountDiscoveryManager->add(new MaxAgeParameterChecker());
            $this->userAccountDiscoveryManager->add(new PromptNoneParameterChecker());
        }

        return $this->userAccountDiscoveryManager;
    }

    /**
     * @var null|IdTokenHintDiscovery
     */
    private $idTokenHintDiscovery = null;

    /**
     * @return IdTokenHintDiscovery
     */
    public function getIdTokenHintDiscovery(): IdTokenHintDiscovery
    {
        if (null === $this->idTokenHintDiscovery) {
            $this->idTokenHintDiscovery = new IdTokenHintDiscovery(
                $this->getIdTokenLoader(),
                $this->getUserAccountRepository()
            );
            $this->idTokenHintDiscovery->enablePairwiseSubject($this->getPairwiseSubjectIdentifierAlgorithm());
        }

        return $this->idTokenHintDiscovery;
    }

    /**
     * @var null|SecurityLayer
     */
    private $securityLayer = null;

    /**
     * @return SecurityLayer
     */
    public function getSecurityLayer(): SecurityLayer
    {
        if (null === $this->securityLayer) {
            $this->securityLayer = new SecurityLayer();
        }

        return $this->securityLayer;
    }

    /**
     * @var null|BeforeConsentScreenManager
     */
    private $beforeConsentScreenManager = null;

    /**
     * @return BeforeConsentScreenManager
     */
    public function getBeforeConsentScreenManager(): BeforeConsentScreenManager
    {
        if (null === $this->beforeConsentScreenManager) {
            $this->beforeConsentScreenManager = new BeforeConsentScreenManager();
            $this->beforeConsentScreenManager->add(new PreConfiguredAuthorizationExtension(
                $this->getPreConfiguredAuthorizationRepository()
            ));
        }

        return $this->beforeConsentScreenManager;
    }

    /**
     * @var null|AfterConsentScreenManager
     */
    private $afterConsentScreenManager = null;

    /**
     * @return AfterConsentScreenManager
     */
    public function getAfterConsentScreenManager(): AfterConsentScreenManager
    {
        if (null === $this->afterConsentScreenManager) {
            $this->afterConsentScreenManager = new AfterConsentScreenManager();
            $this->afterConsentScreenManager->add(new SessionStateParameterExtension('DefaultStorage'));
        }

        return $this->afterConsentScreenManager;
    }

    /**
     * @var null|PreConfiguredAuthorizationRepository
     */
    private $preConfiguredAuthorizationRepository = null;

    /**
     * @return PreConfiguredAuthorizationRepository
     */
    public function getPreConfiguredAuthorizationRepository(): PreConfiguredAuthorizationRepository
    {
        if (null === $this->preConfiguredAuthorizationRepository) {
            $this->preConfiguredAuthorizationRepository = new PreConfiguredAuthorizationRepository(
                $this->getPreConfiguredAuthorizationEventStore(),
                $this->getPublicMessageRecorder()
            );
        }

        return $this->preConfiguredAuthorizationRepository;
    }

    /**
     * @var null|DomainConverter
     */
    private $eventConverter = null;

    /**
     * @return DomainConverter
     */
    public function getDomainConverter(): DomainConverter
    {
        if (null === $this->eventConverter) {
            $this->eventConverter = new DomainConverter();
        }

        return $this->eventConverter;
    }
}
