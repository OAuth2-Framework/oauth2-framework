<?php

declare(strict_types=1);

use OAuth2Framework\Component\ClientAuthentication\ClientSecretPost;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(ClientSecretPost::class)
        ->args(['%oauth2_server.client_authentication.client_secret_post.secret_lifetime%'])
    ;
};
