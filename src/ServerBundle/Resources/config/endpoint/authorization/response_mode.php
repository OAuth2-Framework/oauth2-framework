<?php

declare(strict_types=1);

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FragmentResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\QueryResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseModeGuesser;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    // Response Mode
    $container->set(ResponseModeManager::class);
    $container->set(QueryResponseMode::class);
    $container->set(FragmentResponseMode::class);

    $container->set(ResponseModeGuesser::class)
        ->args([
            service(ResponseModeManager::class),
            '%oauth2_server.endpoint.authorization.response_mode.allow_response_mode_parameter%',
        ])
    ;
};
