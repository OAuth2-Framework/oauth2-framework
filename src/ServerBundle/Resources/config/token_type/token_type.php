<?php

declare(strict_types=1);

use OAuth2Framework\Component\Core\TokenType\TokenTypeGuesser;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use OAuth2Framework\Component\Core\TokenType\TokenTypeMiddleware;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
        ->autowire()
    ;

    $container->set(TokenTypeGuesser::class)
        ->args([service(TokenTypeManager::class), '%oauth2_server.token_type.allow_token_type_parameter%'])
    ;

    $container->set(TokenTypeMiddleware::class)
        ->args([service(TokenTypeManager::class), '%oauth2_server.token_type.allow_token_type_parameter%'])
    ;

    $container->set(TokenTypeManager::class);
};
