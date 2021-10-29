<?php

declare(strict_types=1);

use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\TokenType\TokenTypeGuesser;
use OAuth2Framework\Component\ImplicitGrant\ImplicitGrantType;
use OAuth2Framework\Component\ImplicitGrant\TokenResponseType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(ImplicitGrantType::class);

    $container->set(TokenResponseType::class)
        ->args([
            service(AccessTokenRepository::class),
            '%oauth2_server.access_token_lifetime%',
            service(TokenTypeGuesser::class),
        ])
    ;
};
