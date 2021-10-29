<?php

declare(strict_types=1);

use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenEndpointExtension;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenGrantType;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRepository;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRevocationTypeHint;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(RefreshTokenGrantType::class)
        ->args([service(RefreshTokenRepository::class)])
    ;

    $container->set(RefreshTokenRevocationTypeHint::class)
        ->args([service(RefreshTokenRepository::class)])
    ;

    $container->set(RefreshTokenEndpointExtension::class)
        ->args(['%oauth2_server.grant.refresh_token.lifetime%', service(RefreshTokenRepository::class)])
    ;
};
