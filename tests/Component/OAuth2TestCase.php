<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component;

use function count;
use DateTimeImmutable;
use Jose\Component\Checker\AudienceChecker;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\IssuedAtChecker;
use Jose\Component\Checker\NotBeforeChecker;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use Jose\Component\Core\Util\JsonConverter;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256GCM;
use Jose\Component\Encryption\Algorithm\KeyEncryption\A256KW;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\Compression\Deflate;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\CompactSerializer as JweCompactSerializer;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer as JwsCompactSerializer;
use const JSON_THROW_ON_ERROR;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeGrantType;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeResponseType;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\Plain;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\S256;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequestStorage;
use OAuth2Framework\Component\AuthorizationEndpoint\ConsentHandler;
use OAuth2Framework\Component\AuthorizationEndpoint\Extension\ExtensionManager;
use OAuth2Framework\Component\AuthorizationEndpoint\LoginHandler;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\DisplayParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterCheckerManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\PromptParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\RedirectUriParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ResponseTypeParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\StateParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FormPostResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FragmentResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\QueryResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseTypeManager;
use OAuth2Framework\Component\AuthorizationEndpoint\SelectAccountHandler;
use OAuth2Framework\Component\AuthorizationEndpoint\User\MaxAgeParameterAuthenticationChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAuthenticationCheckerManager;
use OAuth2Framework\Component\BearerTokenType\AuthorizationHeaderTokenFinder;
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\BearerTokenType\QueryStringTokenFinder;
use OAuth2Framework\Component\BearerTokenType\RequestBodyTokenFinder;
use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\ClientAuthentication\ClientAssertionJwt;
use OAuth2Framework\Component\ClientAuthentication\ClientAuthenticationMiddleware;
use OAuth2Framework\Component\ClientAuthentication\ClientSecretBasic;
use OAuth2Framework\Component\ClientAuthentication\ClientSecretPost;
use OAuth2Framework\Component\ClientAuthentication\None;
use OAuth2Framework\Component\ClientConfigurationEndpoint\ClientConfigurationEndpoint;
use OAuth2Framework\Component\ClientCredentialsGrant\ClientCredentialsGrantType;
use OAuth2Framework\Component\ClientRegistrationEndpoint\ClientRegistrationEndpoint;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenMiddleware;
use OAuth2Framework\Component\ClientRule\RuleManager;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenIntrospectionTypeHint;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRevocationTypeHint;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use OAuth2Framework\Component\Core\TokenType\TokenTypeMiddleware;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\ImplicitGrant\ImplicitGrantType;
use OAuth2Framework\Component\ImplicitGrant\TokenResponseType;
use OAuth2Framework\Component\JwtBearerGrant\JwtBearerGrantType;
use OAuth2Framework\Component\NoneGrant\AuthorizationStorage;
use OAuth2Framework\Component\NoneGrant\NoneResponseType;
use OAuth2Framework\Component\OpenIdConnect\IdTokenGrant\IdTokenResponseType;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenGrantType;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenIntrospectionTypeHint;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRevocationTypeHint;
use OAuth2Framework\Component\ResourceOwnerPasswordCredentialsGrant\ResourceOwnerPasswordCredentialsGrantType;
use OAuth2Framework\Component\Scope\Policy\DefaultScopePolicy;
use OAuth2Framework\Component\Scope\Policy\ErrorScopePolicy;
use OAuth2Framework\Component\Scope\Policy\NoScopePolicy;
use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;
use OAuth2Framework\Component\Scope\ScopeParameterChecker;
use OAuth2Framework\Component\Scope\TokenEndpointScopeExtension;
use OAuth2Framework\Component\TokenEndpoint\Extension\TokenEndpointExtensionManager;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeManager;
use OAuth2Framework\Component\TokenEndpoint\TokenEndpoint;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHintManager as TokenIntrospectionTypeHintManager;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationGetEndpoint;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationPostEndpoint;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenTypeHintManager as TokenRevocationTypeHintManager;
use OAuth2Framework\Tests\Component\RefreshTokenGrant\RefreshToken;
use OAuth2Framework\Tests\TestBundle\Entity\AuthorizationCode;
use OAuth2Framework\Tests\TestBundle\Entity\Client;
use OAuth2Framework\Tests\TestBundle\Entity\TrustedIssuer;
use OAuth2Framework\Tests\TestBundle\Repository\AccessTokenRepository;
use OAuth2Framework\Tests\TestBundle\Repository\AuthorizationCodeRepository;
use OAuth2Framework\Tests\TestBundle\Repository\AuthorizationRepository;
use OAuth2Framework\Tests\TestBundle\Repository\ClientRepository;
use OAuth2Framework\Tests\TestBundle\Repository\InitialAccessTokenRepository;
use OAuth2Framework\Tests\TestBundle\Repository\RefreshTokenRepository;
use OAuth2Framework\Tests\TestBundle\Repository\ResourceOwnerPasswordCredentialRepository;
use OAuth2Framework\Tests\TestBundle\Repository\ScopeRepository;
use OAuth2Framework\Tests\TestBundle\Repository\TrustedIssuerRepository;
use OAuth2Framework\Tests\TestBundle\Repository\UserAccountRepository;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

abstract class OAuth2TestCase extends TestCase
{
    private ?TokenEndpoint $tokenEndpoint = null;

    private ?ClientRepository $clientRepository = null;

    private ?AuthenticationMethodManager $authenticationMethodManager = null;

    private ?UserAccountRepository $userAccountRepository = null;

    private ?AccessTokenRepository $accessTokenRepository = null;

    private ?PKCEMethodManager $pkceMethodManager = null;

    private ?AuthorizationCodeRepository $authorizationCodeRepository = null;

    private ?GrantTypeManager $grantTypeManager = null;

    private ?AuthorizationCodeGrantType $authorizationCodeGrantType = null;

    private ?JwtBearerGrantType $jwtBearerGrantType = null;

    private ?RefreshTokenRepository $refreshTokenRepository = null;

    private ?InitialAccessTokenRepository $initialAccessTokenRepository = null;

    private ?ClientRegistrationEndpoint $clientRegistrationEndpoint = null;

    private ?TokenTypeMiddleware $tokenTypeMiddleware = null;

    private ?TokenTypeManager $tokenTypeManager = null;

    private ?InitialAccessTokenMiddleware $middleware = null;

    private ?ClientConfigurationEndpoint $clientConfigurationEndpoint = null;

    private ?TokenRevocationTypeHintManager $tokenRevocationTypeHintManager = null;

    private ?TokenRevocationPostEndpoint $tokenRevocationPostEndpoint = null;

    private ?TokenRevocationGetEndpoint $tokenRevocationGetEndpoint = null;

    private ?TokenIntrospectionTypeHintManager $tokenIntrospectionTypeHintManager = null;

    private ?ScopeParameterChecker $scopeParameterChecker = null;

    private ?ScopeRepository $scopeRepository = null;

    private ?ScopePolicyManager $scopePolicyManager = null;

    private ?ResponseTypeManager $responseTypeManager = null;

    private ?ResponseModeManager $responseModeManager = null;

    private ?ExtensionManager $extensionManager = null;

    private ?LoginHandler $loginHandler = null;

    private ?UserAuthenticationCheckerManager $userAuthenticationCheckerManager = null;

    private ?SelectAccountHandler $selectAccountHandler = null;

    private ?ConsentHandler $consentHandler = null;

    private ?AccessTokenRevocationTypeHint $accessTokenRevocationTypeHint = null;

    private ?RefreshTokenRevocationTypeHint $refreshTokenRevocationTypeHint = null;

    private ?AccessTokenIntrospectionTypeHint $accessTokenIntrospectionTypeHint = null;

    private ?RefreshTokenIntrospectionTypeHint $refreshTokenIntrospectionTypeHint = null;

    private ?ParameterCheckerManager $parameterCheckerManager = null;

    private ?TokenEndpointScopeExtension $tokenEndpointScopeExtension = null;

    private ?AuthorizationStorage $authorizationStorage = null;

    private ?AuthorizationRequestStorage $authorizationRequestStorage = null;

    public function getAccessTokenIntrospectionTypeHint(): AccessTokenIntrospectionTypeHint
    {
        if ($this->accessTokenIntrospectionTypeHint === null) {
            $this->accessTokenIntrospectionTypeHint = AccessTokenIntrospectionTypeHint::create(
                $this->getAccessTokenRepository()
            );
        }

        return $this->accessTokenIntrospectionTypeHint;
    }

    public function getRefreshTokenIntrospectionTypeHint(): RefreshTokenIntrospectionTypeHint
    {
        if ($this->refreshTokenIntrospectionTypeHint === null) {
            $this->refreshTokenIntrospectionTypeHint = RefreshTokenIntrospectionTypeHint::create(
                $this->getRefreshTokenRepository()
            );
        }

        return $this->refreshTokenIntrospectionTypeHint;
    }

    protected function getGrantTypeManager(): GrantTypeManager
    {
        if ($this->grantTypeManager === null) {
            $this->grantTypeManager = GrantTypeManager::create()
                ->add(ClientCredentialsGrantType::create())
                ->add(ResourceOwnerPasswordCredentialsGrantType::create(
                    $this->getResourceOwnerPasswordCredentialManager()
                ))
                ->add(ImplicitGrantType::create())
                ->add($this->getAuthorizationCodeGrantType())
                ->add($this->getJwtBearerGrantType())
                ->add(RefreshTokenGrantType::create($this->getRefreshTokenRepository()))
            ;
        }

        return $this->grantTypeManager;
    }

    protected function getPkceMethodManager(): PKCEMethodManager
    {
        if ($this->pkceMethodManager === null) {
            $this->pkceMethodManager = PKCEMethodManager::create()
                ->add(Plain::create())
                ->add(S256::create())
            ;
        }

        return $this->pkceMethodManager;
    }

    protected function getAuthenticationMethodManager(): AuthenticationMethodManager
    {
        if ($this->authenticationMethodManager === null) {
            $this->authenticationMethodManager = AuthenticationMethodManager::create()
                ->add(None::create())
                ->add(ClientSecretPost::create())
                ->add(ClientSecretBasic::create('My Service'))
                ->add(ClientAssertionJwt::create(
                    new JWSVerifier(new AlgorithmManager([new HS256(), new RS256()])),
                    new HeaderCheckerManager([], [new JWSTokenSupport()]),
                    new ClaimCheckerManager([]),
                    3600
                ))
            ;
        }

        return $this->authenticationMethodManager;
    }

    protected function buildRequest(
        string $method = 'GET',
        array $data = [],
        array $headers = [],
        array $queryParameters = [],
        string $contentType = 'application/x-www-form-urlencoded'
    ): ServerRequestInterface {
        $uri = '/';
        if (count($queryParameters) !== 0) {
            $uri .= '?' . http_build_query($queryParameters);
        }
        $request = new ServerRequest($method, $uri);
        $request->getBody()
            ->write(
                $contentType === 'application/x-www-form-urlencoded' ? http_build_query(
                    $data
                ) : json_encode($data, JSON_THROW_ON_ERROR)
            )
        ;
        $request->getBody()
            ->rewind()
        ;

        $request = $request
            ->withQueryParams($queryParameters)
            ->withHeader('Content-Type', $contentType)
            ->withParsedBody($data)
        ;
        foreach ($headers as $k => $v) {
            $request = $request->withHeader($k, $v);
        }

        return $request;
    }

    protected function getAuthorizationCodeRepository(): AuthorizationCodeRepository
    {
        if ($this->authorizationCodeRepository === null) {
            $this->authorizationCodeRepository = new AuthorizationCodeRepository();

            $authorizationCode = AuthorizationCode::create(
                AuthorizationCodeId::create('AUTHORIZATION_CODE_ID'),
                ClientId::create('CLIENT_ID'),
                UserAccountId::create('USER_ACCOUNT_ID'),
                [
                    'code_challenge' => 'ABCDEFGH',
                    'code_challenge_method' => 'plain',
                ],
                'http://localhost:8000/',
                new DateTimeImmutable('now +1 day'),
                DataBag::create([
                    'scope' => 'scope1 scope2',
                ]),
                DataBag::create(),
                ResourceServerId::create('RESOURCE_SERVER_ID')
            );
            $this->authorizationCodeRepository->save($authorizationCode);
        }

        return $this->authorizationCodeRepository;
    }

    protected function getTokenEndpoint(): TokenEndpoint
    {
        if ($this->tokenEndpoint === null) {
            $this->tokenEndpoint = new TokenEndpoint(
                $this->getClientRepository(),
                $this->getUserAccountRepository(),
                new TokenEndpointExtensionManager(),
                new Psr17Factory(),
                $this->getAccessTokenRepository(),
                1800
            );
        }

        return $this->tokenEndpoint;
    }

    protected function getRefreshTokenRepository(): RefreshTokenRepository
    {
        if ($this->refreshTokenRepository === null) {
            $this->refreshTokenRepository = new RefreshTokenRepository();
            $this->refreshTokenRepository->save(
                RefreshToken::create(
                    RefreshTokenId::create('REFRESH_TOKEN_ID'),
                    ClientId::create('CLIENT_ID'),
                    ClientId::create('CLIENT_ID'),
                    new DateTimeImmutable('now +1 day'),
                    DataBag::create([
                        'metadata' => 'foo',
                        'scope' => 'scope1 scope2',
                    ]),
                    DataBag::create([
                        'parameter1' => 'bar',
                    ]),
                    ResourceServerId::create('RESOURCE_SERVER_ID')
                )
            );

            $this->refreshTokenRepository->save(
                RefreshToken::create(
                    RefreshTokenId::create('REVOKED_REFRESH_TOKEN_ID'),
                    ClientId::create('CLIENT_ID'),
                    ClientId::create('CLIENT_ID'),
                    new DateTimeImmutable('now +1 day'),
                    DataBag::create([
                        'metadata' => 'foo',
                        'scope' => 'scope1 scope2',
                    ]),
                    DataBag::create([
                        'parameter1' => 'bar',
                    ]),
                    ResourceServerId::create('RESOURCE_SERVER_ID')
                )->markAsRevoked()
            );

            $this->refreshTokenRepository->save(
                RefreshToken::create(
                    RefreshTokenId::create('EXPIRED_REFRESH_TOKEN_ID'),
                    ClientId::create('CLIENT_ID'),
                    ClientId::create('CLIENT_ID'),
                    new DateTimeImmutable('now -1 day'),
                    DataBag::create([
                        'metadata' => 'foo',
                        'scope' => 'scope1 scope2',
                    ]),
                    DataBag::create([
                        'parameter1' => 'bar',
                    ]),
                    ResourceServerId::create('RESOURCE_SERVER_ID')
                )
            );
        }

        return $this->refreshTokenRepository;
    }

    protected function getClientRepository(): ClientRepository
    {
        if ($this->clientRepository === null) {
            $this->clientRepository = new ClientRepository();
            $this->clientRepository->save(Client::create(
                ClientId::create('PRIVATE_KEY_JWT_CLIENT_ID'),
                DataBag::create([
                    'token_endpoint_auth_method' => 'private_key_jwt',
                    'request_object_signing_alg' => 'RS256',
                    'jwks' => json_encode($this->getPublicSignatureKeyset(), JSON_THROW_ON_ERROR),
                    'request_uris' => ['https://www.foo.bar/'],
                ]),
                null
            ));
            $this->clientRepository->save(Client::create(
                ClientId::create('PUBLIC_CLIENT_ID'),
                DataBag::create([
                    'token_endpoint_auth_method' => 'none',
                    'grant_types' => ['foo'],
                ]),
                UserAccountId::create('john.1')
            ));
            $this->clientRepository->save(Client::create(
                ClientId::create('SECRET_JWT_CLIENT_ID'),
                DataBag::create([
                    'token_endpoint_auth_method' => 'client_secret_jwt',
                    'client_secret' => 'SECRET',
                    'request_object_signing_alg' => 'RS256',
                    'request_uris' => ['https://www.foo.bar/'],
                ]),
                UserAccountId::create('john.1')
            ));
            $this->clientRepository->save(Client::create(
                ClientId::create('DELETED_CLIENT_ID'),
                DataBag::create([
                    'token_endpoint_auth_method' => 'client_secret_jwt',
                    'client_secret' => 'SECRET',
                    'request_object_signing_alg' => 'RS256',
                    'request_uris' => ['https://www.foo.bar/'],
                ]),
                UserAccountId::create('john.1')
            )->markAsDeleted());
        }

        return $this->clientRepository;
    }

    protected function getUserAccountRepository(): UserAccountRepository
    {
        if ($this->userAccountRepository === null) {
            $this->userAccountRepository = new UserAccountRepository();
        }

        return $this->userAccountRepository;
    }

    protected function getAccessTokenRepository(): AccessTokenRepository
    {
        if ($this->accessTokenRepository === null) {
            $this->accessTokenRepository = new AccessTokenRepository();
        }

        return $this->accessTokenRepository;
    }

    protected function createAssertionFromUnknownIssuer(): string
    {
        $claims = [
            'iss' => 'Unknown Issuer',
            'sub' => 'USER_ACCOUNT_ID',
            'aud' => 'My OAuth2 Server',
            'exp' => time() + 1000,
        ];
        $header = [
            'alg' => 'ES256',
        ];

        $jws = $this->getJwsBuilder()
            ->create()
            ->withPayload(JsonConverter::encode($claims))
            ->addSignature($this->getPrivateEcKey(), $header)
            ->build()
        ;
        $serializer = new JwsCompactSerializer();

        return $serializer->serialize($jws, 0);
    }

    protected function createValidAssertionFromIssuer(): string
    {
        $claims = [
            'iss' => 'Trusted Issuer #1',
            'sub' => 'john.1',
            'aud' => 'My OAuth2 Server',
            'exp' => time() + 1000,
        ];
        $header = [
            'alg' => 'ES256',
        ];

        $jws = $this->getJwsBuilder()
            ->create()
            ->withPayload(JsonConverter::encode($claims))
            ->addSignature($this->getPrivateEcKey(), $header)
            ->build()
        ;
        $serializer = new JwsCompactSerializer();

        return $serializer->serialize($jws, 0);
    }

    protected function createValidEncryptedAssertionFromClient(): string
    {
        $claims = [
            'iss' => 'PRIVATE_KEY_JWT_CLIENT_ID',
            'sub' => 'PRIVATE_KEY_JWT_CLIENT_ID',
            'aud' => 'My OAuth2 Server',
            'exp' => time() + 1000,
        ];
        $header = [
            'alg' => 'RS256',
        ];

        $jws = $this->getJwsBuilder()
            ->create()
            ->withPayload(JsonConverter::encode($claims))
            ->addSignature($this->getPrivateRsaKey(), $header)
            ->build()
        ;
        $serializer = new JwsCompactSerializer();
        $jwt = $serializer->serialize($jws, 0);

        $jwe = $this->getJweBuilder()
            ->create()
            ->withPayload($jwt)
            ->withSharedProtectedHeader([
                'alg' => 'A256KW',
                'enc' => 'A256GCM',
            ])
            ->addRecipient($this->getEncryptionKey())
            ->build()
        ;
        $serializer = new JweCompactSerializer();

        return $serializer->serialize($jwe, 0);
    }

    protected function getTokenTypeMiddleware(): TokenTypeMiddleware
    {
        if ($this->tokenTypeMiddleware === null) {
            $this->tokenTypeMiddleware = new TokenTypeMiddleware($this->getTokenTypeManager(), true);
        }

        return $this->tokenTypeMiddleware;
    }

    protected function getTokenTypeManager(): TokenTypeManager
    {
        if ($this->tokenTypeManager === null) {
            $this->tokenTypeManager = TokenTypeManager::create()
                ->add(
                    BearerToken::create('Realm')
                        ->addTokenFinder(AuthorizationHeaderTokenFinder::create())
                        ->addTokenFinder(QueryStringTokenFinder::create())
                        ->addTokenFinder(RequestBodyTokenFinder::create())
                )
            ;
        }

        return $this->tokenTypeManager;
    }

    protected function getInitialAccessTokenRepository(): InitialAccessTokenRepository
    {
        if ($this->initialAccessTokenRepository === null) {
            $this->initialAccessTokenRepository = new InitialAccessTokenRepository();
        }

        return $this->initialAccessTokenRepository;
    }

    protected function getInitialAccessTokenMiddleware(): InitialAccessTokenMiddleware
    {
        if ($this->middleware === null) {
            $bearerToken = BearerToken::create('Realm')
                ->addTokenFinder(AuthorizationHeaderTokenFinder::create())
            ;
            $this->middleware = new InitialAccessTokenMiddleware(
                $bearerToken,
                $this->getInitialAccessTokenRepository(),
                false
            );
        }

        return $this->middleware;
    }

    protected function getClientRegistrationEndpoint(): ClientRegistrationEndpoint
    {
        if ($this->clientRegistrationEndpoint === null) {
            $this->clientRegistrationEndpoint = new ClientRegistrationEndpoint(
                $this->getClientRepository(),
                new Psr17Factory(),
                new RuleManager()
            );
        }

        return $this->clientRegistrationEndpoint;
    }

    protected function getClientConfigurationEndpoint(): ClientConfigurationEndpoint
    {
        if ($this->clientConfigurationEndpoint === null) {
            $bearerToken = BearerToken::create('Client Manager')
                ->addTokenFinder(AuthorizationHeaderTokenFinder::create())
            ;
            $this->clientConfigurationEndpoint = new ClientConfigurationEndpoint(
                $this->getClientRepository(),
                $bearerToken,
                new Psr17Factory(),
                new RuleManager()
            );
        }

        return $this->clientConfigurationEndpoint;
    }

    protected function getClientAuthenticationMiddleware(): ClientAuthenticationMiddleware
    {
        $authenticationMethodManager = $this->getAuthenticationMethodManager();
        $authenticationMethodManager->add(ClientSecretBasic::create('Realm'));

        return new ClientAuthenticationMiddleware($this->getClientRepository(), $authenticationMethodManager);
    }

    protected function getAccessTokenRevocationTypeHint(): AccessTokenRevocationTypeHint
    {
        if ($this->accessTokenRevocationTypeHint === null) {
            $this->accessTokenRevocationTypeHint = AccessTokenRevocationTypeHint::create(
                $this->getAccessTokenRepository()
            );
        }

        return $this->accessTokenRevocationTypeHint;
    }

    protected function getRefreshTokenRevocationTypeHint(): RefreshTokenRevocationTypeHint
    {
        if ($this->refreshTokenRevocationTypeHint === null) {
            $this->refreshTokenRevocationTypeHint = RefreshTokenRevocationTypeHint::create(
                $this->getRefreshTokenRepository()
            );
        }

        return $this->refreshTokenRevocationTypeHint;
    }

    protected function getTokenTypeHintManager(): TokenRevocationTypeHintManager
    {
        if ($this->tokenRevocationTypeHintManager === null) {
            $this->tokenRevocationTypeHintManager = TokenRevocationTypeHintManager::create()
                ->add($this->getAccessTokenRevocationTypeHint())
                ->add($this->getRefreshTokenRevocationTypeHint())
            ;
        }

        return $this->tokenRevocationTypeHintManager;
    }

    protected function getTokenRevocationPostEndpoint(): TokenRevocationPostEndpoint
    {
        if ($this->tokenRevocationPostEndpoint === null) {
            $this->tokenRevocationPostEndpoint = new TokenRevocationPostEndpoint(
                $this->getTokenTypeHintManager(),
                new Psr17Factory()
            );
        }

        return $this->tokenRevocationPostEndpoint;
    }

    protected function getTokenRevocationGetEndpoint(): TokenRevocationGetEndpoint
    {
        if ($this->tokenRevocationGetEndpoint === null) {
            $this->tokenRevocationGetEndpoint = TokenRevocationGetEndpoint::create(
                $this->getTokenTypeHintManager(),
                new Psr17Factory(),
                true
            );
        }

        return $this->tokenRevocationGetEndpoint;
    }

    protected function getTokenIntrospectionTypeHintManager(): TokenIntrospectionTypeHintManager
    {
        if ($this->tokenIntrospectionTypeHintManager === null) {
            $this->tokenIntrospectionTypeHintManager = TokenIntrospectionTypeHintManager::create()
                ->add($this->getAccessTokenIntrospectionTypeHint())
                ->add($this->getRefreshTokenIntrospectionTypeHint())
            ;
        }

        return $this->tokenIntrospectionTypeHintManager;
    }

    protected function getScopeParameterChecker(): ScopeParameterChecker
    {
        if ($this->scopeParameterChecker === null) {
            $this->scopeParameterChecker = ScopeParameterChecker::create(
                $this->getScopeRepository(),
                $this->getScopePolicyManager()
            );
        }

        return $this->scopeParameterChecker;
    }

    protected function getScopePolicyManager(): ScopePolicyManager
    {
        if ($this->scopePolicyManager === null) {
            $this->scopePolicyManager = ScopePolicyManager::create()
                ->add(NoScopePolicy::create())
                ->add(DefaultScopePolicy::create('scope1 scope2'))
                ->add(ErrorScopePolicy::create())
            ;
        }

        return $this->scopePolicyManager;
    }

    protected function getScopeRepository(): ScopeRepository
    {
        if ($this->scopeRepository === null) {
            $this->scopeRepository = new ScopeRepository();
        }

        return $this->scopeRepository;
    }

    protected function getResponseTypeManager(): ResponseTypeManager
    {
        if ($this->responseTypeManager === null) {
            $this->responseTypeManager = ResponseTypeManager::create()
                ->add($this->getTokenResponseType())
                //->add(IdTokenResponseType::create())
                //->add(IdTokenTokenResponseType::create())
                //->add(CodeTokenResponseType::create())
                //->add(CodeIdTokenResponseType::create())
                //->add(CodeIdTokenTokenResponseType::create())
                ->add(NoneResponseType::create($this->getAuthorizationStorage()))
                ->add($this->getAuthorizationCodeResponseType())
            ;
        }

        return $this->responseTypeManager;
    }

    protected function getTokenResponseType(): TokenResponseType
    {
        return TokenResponseType::create($this->getAccessTokenRepository(), 1800);
    }

    protected function getAuthorizationCodeResponseType(): AuthorizationCodeResponseType
    {
        return AuthorizationCodeResponseType::create(
            $this->getAuthorizationCodeRepository(),
            300,
            $this->getPkceMethodManager(),
            false
        );
    }

    protected function getResponseModeManager(): ResponseModeManager
    {
        if ($this->responseModeManager === null) {
            $this->responseModeManager = ResponseModeManager::create()
                ->add(QueryResponseMode::create())
                ->add(FragmentResponseMode::create())
                ->add(FormPostResponseMode::create(FakeFormPostRenderer::create()))
            ;
        }

        return $this->responseModeManager;
    }

    protected function getExtensionManager(): ExtensionManager
    {
        if ($this->extensionManager === null) {
            $this->extensionManager = ExtensionManager::create();
        }

        return $this->extensionManager;
    }

    protected function getAuthorizationRequestStorage(): AuthorizationRequestStorage
    {
        if ($this->authorizationRequestStorage === null) {
            $this->authorizationRequestStorage = FakeAuthorizationRequestStorage::create();
        }

        return $this->authorizationRequestStorage;
    }

    protected function getLoginHandler(): LoginHandler
    {
        if ($this->loginHandler === null) {
            $this->loginHandler = FakeLoginHandler::create();
        }

        return $this->loginHandler;
    }

    protected function getConsentHandler(): ConsentHandler
    {
        if ($this->consentHandler === null) {
            $this->consentHandler = FakeConsentHandler::create();
        }

        return $this->consentHandler;
    }

    protected function getSelectAccountHandler(): SelectAccountHandler
    {
        if ($this->selectAccountHandler === null) {
            $this->selectAccountHandler = FakeSelectAccountHandler::create();
        }

        return $this->selectAccountHandler;
    }

    protected function getUserAuthenticationCheckerManager(): UserAuthenticationCheckerManager
    {
        if ($this->userAuthenticationCheckerManager === null) {
            $this->userAuthenticationCheckerManager = UserAuthenticationCheckerManager::create()
                ->add(MaxAgeParameterAuthenticationChecker::create())
            ;
        }

        return $this->userAuthenticationCheckerManager;
    }

    protected function getAuthorizationCodeGrantType(): AuthorizationCodeGrantType
    {
        if ($this->authorizationCodeGrantType === null) {
            $this->authorizationCodeGrantType = AuthorizationCodeGrantType::create(
                $this->getAuthorizationCodeRepository(),
                $this->getPkceMethodManager()
            );
        }

        return $this->authorizationCodeGrantType;
    }

    protected function getJwtBearerGrantType(): JwtBearerGrantType
    {
        if ($this->jwtBearerGrantType === null) {
            $this->jwtBearerGrantType = new JwtBearerGrantType(
                $this->getJwsVerifier(),
                $this->getHeaderCheckerManager(),
                $this->getClaimCheckerManager(),
                $this->getClientRepository(),
                $this->getUserAccountRepository()
            );

            $this->jwtBearerGrantType->enableTrustedIssuerSupport($this->getTrustedIssuerRepository());

            if (class_exists(JWEBuilder::class)) {
                $this->jwtBearerGrantType->enableEncryptedAssertions(
                    $this->getJweDecrypter(),
                    $this->getEncryptionKeySet(),
                    false
                );
            }
        }

        return $this->jwtBearerGrantType;
    }

    protected function getTrustedIssuerRepository(): TrustedIssuerRepository
    {
        $issuer = TrustedIssuer::create(
            'Trusted Issuer #1',
            ['urn:ietf:params:oauth:client-assertion-type:jwt-bearer'],
            ['ES256', 'RS256'],
            $this->getPublicSignatureKeyset()
        );

        $manager = new TrustedIssuerRepository();
        $manager->save($issuer);

        return $manager;
    }

    protected function getResourceOwnerPasswordCredentialManager(): ResourceOwnerPasswordCredentialRepository
    {
        return new ResourceOwnerPasswordCredentialRepository();
    }

    protected function getParameterCheckerManager(): ParameterCheckerManager
    {
        if ($this->parameterCheckerManager === null) {
            $this->parameterCheckerManager = ParameterCheckerManager::create()
                ->add(DisplayParameterChecker::create())
                ->add(PromptParameterChecker::create())
                ->add(RedirectUriParameterChecker::create())
                ->add(ResponseTypeParameterChecker::create($this->getResponseTypeManager()))
                ->add(StateParameterChecker::create())
            ;
        }

        return $this->parameterCheckerManager;
    }

    protected function getTokenEndpointScopeExtension(): TokenEndpointScopeExtension
    {
        if ($this->tokenEndpointScopeExtension === null) {
            $this->tokenEndpointScopeExtension = TokenEndpointScopeExtension::create(
                $this->getScopeRepository(),
                $this->getScopePolicyManager()
            );
        }

        return $this->tokenEndpointScopeExtension;
    }

    protected function getAuthorizationStorage(): AuthorizationStorage
    {
        if ($this->authorizationStorage === null) {
            $this->authorizationStorage = AuthorizationRepository::create();
        }

        return $this->authorizationStorage;
    }

    protected function getJwsBuilder(): JWSBuilder
    {
        return new JWSBuilder(new AlgorithmManager([new RS256(), new ES256()]));
    }

    protected function getJweBuilder(): JWEBuilder
    {
        return new JWEBuilder(
            new AlgorithmManager([new A256KW()]),
            new AlgorithmManager([new A256GCM()]),
            new CompressionMethodManager([new Deflate()])
        );
    }

    protected function getPrivateRsaKey(): JWK
    {
        return JWK::createFromJson(
            '{"kty":"RSA","n":"sLjaCStJYRr_y7_3GLlDb4bnGJ8XirSdFboYmvA38NXJ6PhIIjr-sFzfwlcpxZxz6zzjXkDFs3AcUOvC3_KRT5tn4XBOHcR6ABrT65dZTe_qalEpYeQG4oxevc01vmD_dD6Ho2O69amT4gscus2pvszFPdraMYybH24aQFztVtc","e":"AQAB","d":"By-tJhxNgpZfeoCW4rl95YYd1aF6iphnnt-PapWEINYAvOmDvWiavL86FiQHPdLr38_9CvMlVvOjIyNDLGonwHynPxAzUsT7M891N9D0cSCv9DlV3uqRVtdqF4MtWtpU5JWJ9q6auL1UPx2tJhOygu9tJ7w0bTGFwrUdb8PSnlE","p":"3p-6HWbX9YcSkeksJXW3_Y2cfZgRCUXH2or1dIidmscb4VVtTUwb-8gGzUDEq4iS_5pgLARl3O4lOHK0n6Qbrw","q":"yzdrGWwgaWqK6e9VFv3NXGeq1TEKHLkXjF7J24XWKm9lSmlssPRv0NwMPVp_CJ39BrLfFtpFr_fh0oG1sVZ5WQ","dp":"UQ6rP0VQ4G77zfCuSD1ibol_LyONIGkt6V6rHHEZoV9ZwWPPVlOd5MDh6R3p_eLOUw6scZpwVE7JcpIhPfcMtQ","dq":"Jg8g_cfkYhnUHm_2bbHm7jF0Ky1eCXcY0-9Eutpb--KVA9SuyI1fC6zKlgsG06RTKRgC9BK5DnXMU1J7ptTdMQ","qi":"17kC87NLUV6z-c-wtmbNqAkDbKmwpb2RMsGUQmhEPJwnWuwEKZpSQz776SUVwoc0xiQ8DpvU_FypflIlm6fq9w"}'
        );
    }

    private function getJwsVerifier(): JWSVerifier
    {
        return new JWSVerifier(new AlgorithmManager([new RS256(), new ES256()]));
    }

    private function getJweDecrypter(): JWEDecrypter
    {
        return new JWEDecrypter(
            new AlgorithmManager([new A256KW()]),
            new AlgorithmManager([new A256GCM()]),
            new CompressionMethodManager([new Deflate()])
        );
    }

    private function getHeaderCheckerManager(): HeaderCheckerManager
    {
        return new HeaderCheckerManager([], [new JWSTokenSupport()]);
    }

    private function getClaimCheckerManager(): ClaimCheckerManager
    {
        return new ClaimCheckerManager([
            new AudienceChecker('My OAuth2 Server', true),
            new IssuedAtChecker(),
            new NotBeforeChecker(),
            new ExpirationTimeChecker(),
        ]);
    }

    private function getPublicSignatureKeyset(): JWKSet
    {
        return JWKSet::createFromJson(
            '{"keys":[
                {"kty":"EC","crv":"P-256","x":"VlZO9X_B43HFSUK8aeQn88UO2_VfeBtVU1Usl3rYq90","y":"oAHPRNZEUpe-T2-Q_rThJ4lGsNYLXomSYW69RZ9jzNQ"},
                {"kty":"EC","crv":"P-256","x":"w0qQe7oa_aI3G6irjTbdtMqc4e0vNveQgRoRCyvpIBE","y":"7DyqhillL89iM6fMK216ov1EixmJGda76ugNuE-fsic"},
                {"kty":"RSA","n":"sLjaCStJYRr_y7_3GLlDb4bnGJ8XirSdFboYmvA38NXJ6PhIIjr-sFzfwlcpxZxz6zzjXkDFs3AcUOvC3_KRT5tn4XBOHcR6ABrT65dZTe_qalEpYeQG4oxevc01vmD_dD6Ho2O69amT4gscus2pvszFPdraMYybH24aQFztVtc","e":"AQAB"},
                {"kty":"RSA","n":"um8f5neOmoGMsQ-BJMOgehsSOzQiYOk4W7AJL97q-V_8VojXJKHUqvTqiDeVfcgxPz1kNseIkm4PivKYQ1_Yh1j5RxL30V8Pc3VR7ReLMvEsQUbedkJKqhXy7gOYyc4IrYTux1I2dI5I8r_lvtDtTgWB5UrWfwj9ddVhk22z6jc","e":"AQAB"}
            ]}'
        );
    }

    private function getEncryptionKey(): JWK
    {
        return JWK::createFromJson('{"kty":"oct","k":"bJzb8RaN7TzPz001PeF0lw0ZoUJqbazGxMvBd_xzfms"}');
    }

    private function getEncryptionKeySet(): JWKSet
    {
        return JWKSet::createFromJson(
            '{"keys":[{"kty":"oct","k":"bJzb8RaN7TzPz001PeF0lw0ZoUJqbazGxMvBd_xzfms"},{"kty":"oct","k":"dIx5cdLn-dAgNkvfZSiroJuy5oykHO4hDnYpmwlMq6A"}]}'
        );
    }

    private function getPrivateEcKey(): JWK
    {
        return JWK::createFromJson(
            '{"kty":"EC","crv":"P-256","d":"zudFvuFy_HbN4cZO5kEdN33Zz-VR48YrVV23mCzAwqA","x":"VlZO9X_B43HFSUK8aeQn88UO2_VfeBtVU1Usl3rYq90","y":"oAHPRNZEUpe-T2-Q_rThJ4lGsNYLXomSYW69RZ9jzNQ"}'
        );
    }
}
