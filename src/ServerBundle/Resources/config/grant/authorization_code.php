<?php

declare(strict_types=1);

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeGrantType;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeResponseType;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\Plain;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\S256;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(AuthorizationCodeGrantType::class)
        ->args([service(AuthorizationCodeRepository::class), service(PKCEMethodManager::class)])
    ;

    $container->set(AuthorizationCodeResponseType::class)
        ->args([
            service(AuthorizationCodeRepository::class),
            '%oauth2_server.grant.authorization_code.lifetime%',
            service(PKCEMethodManager::class),
            '%oauth2_server.grant.authorization_code.enforce_pkce%',
        ])
    ;

    $container->set(PKCEMethodManager::class);
    $container->set(Plain::class)
        ->tag('oauth2_server_pkce_method', [
            'alias' => 'plain',
        ])
    ;
    $container->set(S256::class)
        ->tag('oauth2_server_pkce_method', [
            'alias' => 'S256',
        ])
    ;
};
