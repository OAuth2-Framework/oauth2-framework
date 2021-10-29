<?php

declare(strict_types=1);

use OAuth2Framework\Component\BearerTokenType\BearerToken;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
        ->autowire()
    ;

    $container->set(BearerToken::class)
        ->args(['Unused', false, false, false])
        ->tag('oauth2_server_token_type', [
            'scheme' => 'Bearer',
        ])
    ;
};
