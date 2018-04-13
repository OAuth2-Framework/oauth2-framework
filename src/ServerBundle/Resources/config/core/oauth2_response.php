<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use OAuth2Framework\ServerBundle\Response\AuthenticateResponseFactory;
use OAuth2Framework\Component\Middleware;
use OAuth2Framework\Component\Core\Response\OAuth2ResponseFactoryManager;
use OAuth2Framework\Component\Core\Response\Factory;
use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(Middleware\OAuth2ResponseMiddleware::class)
        ->args([
            ref(OAuth2ResponseFactoryManager::class),
        ]);

    $container->set(OAuth2ResponseFactoryManager::class)
        ->args([
            ref('httplug.message_factory'),
        ]);

    $container->set(Factory\AccessDeniedResponseFactory::class);
    $container->set(Factory\BadRequestResponseFactory::class);
    $container->set(Factory\MethodNotAllowedResponseFactory::class);
    $container->set(Factory\NotImplementedResponseFactory::class);
    $container->set(AuthenticateResponseFactory::class)
        ->args([
            ref(AuthenticationMethodManager::class),
        ]);
};
