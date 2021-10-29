<?php

declare(strict_types=1);

use OAuth2Framework\ServerBundle\TokenType\MacToken;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
        ->autowire()
    ;

    $container->set(MacToken::class)
        ->args([
            '%oauth2_server.token_type.mac_token.algorithm%',
            '%oauth2_server.token_type.mac_token.timestamp_lifetime%',
            '%oauth2_server.token_type.mac_token.min_length%',
            '%oauth2_server.token_type.mac_token.max_length%',
        ])
        ->tag('oauth2_server_token_type', [
            'scheme' => 'MAC',
        ])
    ;
};
