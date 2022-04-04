<?php

declare(strict_types=1);

use OAuth2Framework\Component\BearerTokenType\AuthorizationHeaderTokenFinder;
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\BearerTokenType\QueryStringTokenFinder;
use OAuth2Framework\Component\BearerTokenType\RequestBodyTokenFinder;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Pairwise\EncryptedSubjectIdentifier;
use OAuth2Framework\Tests\TestBundle\Controller\ApiController;
use OAuth2Framework\Tests\TestBundle\Entity\ResourceRepository;
use OAuth2Framework\Tests\TestBundle\Repository\AccessTokenRepository;
use OAuth2Framework\Tests\TestBundle\Repository\AuthorizationCodeRepository;
use OAuth2Framework\Tests\TestBundle\Repository\ClientRepository;
use OAuth2Framework\Tests\TestBundle\Repository\ConsentRepository;
use OAuth2Framework\Tests\TestBundle\Repository\InitialAccessTokenRepository;
use OAuth2Framework\Tests\TestBundle\Repository\RefreshTokenRepository;
use OAuth2Framework\Tests\TestBundle\Repository\ResourceOwnerPasswordCredentialRepository;
use OAuth2Framework\Tests\TestBundle\Repository\ResourceServerRepository;
use OAuth2Framework\Tests\TestBundle\Repository\ScopeRepository;
use OAuth2Framework\Tests\TestBundle\Repository\TrustedIssuerRepository;
use OAuth2Framework\Tests\TestBundle\Repository\UserAccountRepository;
use OAuth2Framework\Tests\TestBundle\Service\AccessTokenHandler;
use OAuth2Framework\Tests\TestBundle\Service\ConsentHandler;
use OAuth2Framework\Tests\TestBundle\Service\LoginHandler;
use OAuth2Framework\Tests\TestBundle\Service\SymfonyUserAccountDiscovery;
use OAuth2Framework\Tests\TestBundle\Service\UriPathResolver;
use OAuth2Framework\Tests\TestBundle\Service\UserProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $container = $container->services()
        ->defaults()
        ->public()
        ->autowire()
        ->autoconfigure()
    ;

    $container->set(ApiController::class);
    $container->set(AccessTokenHandler::class);

    $container->set('oauth2_security.bearer_token.authorization_header_token_finder')
        ->class(AuthorizationHeaderTokenFinder::class)
    ;

    $container->set('oauth2_security.bearer_token.query_string_token_finder')
        ->class(QueryStringTokenFinder::class)
    ;

    $container->set('oauth2_security.bearer_token.request_body_token_finder')
        ->class(RequestBodyTokenFinder::class)
    ;

    $container->set('oauth2_security.token_type.bearer_token')
        ->class(BearerToken::class)
        ->args(['Protected API'])
        ->tag('oauth2_security_token_type')
        ->call('addTokenFinder', [service('oauth2_security.bearer_token.authorization_header_token_finder')])
        ->call('addTokenFinder', [service('oauth2_security.bearer_token.query_string_token_finder')])
        ->call('addTokenFinder', [service('oauth2_security.bearer_token.request_body_token_finder')])
    ;

    $container->set(LoginHandler::class);
    $container->set(ConsentHandler::class);
    $container->set(ClientRepository::class);
    $container->set(RefreshTokenRepository::class);
    $container->set(ResourceOwnerPasswordCredentialRepository::class);
    $container->set(UserAccountRepository::class);
    $container->set(ResourceServerRepository::class);
    $container->set(TrustedIssuerRepository::class);
    $container->set(ConsentRepository::class);
    $container->set(UserProvider::class);
    $container->set(SymfonyUserAccountDiscovery::class);
    $container->set(AccessTokenRepository::class);
    $container->set(AuthorizationCodeRepository::class);
    $container->set(ScopeRepository::class);
    $container->set(InitialAccessTokenRepository::class);
    $container->set(ResourceRepository::class);
    $container->set(UriPathResolver::class);

    $container->set('MyPairwiseSubjectIdentifier')
        ->class(EncryptedSubjectIdentifier::class)
        ->args(['This is my secret Key !!!', 'aes-128-cbc'])
    ;

    /*$container->set(ResourceServerAuthMethodByIpAddress::class)
        ->tag('token_introspection_endpoint_auth_method');*/
};
