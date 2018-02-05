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
use OAuth2Framework\Component\Middleware;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set('token_revocation_pipe')
        ->class(Middleware\Pipe::class)
        ->args([
            ref(Middleware\OAuth2ResponseMiddleware::class),
            ref(Middleware\FormPostBodyParserMiddleware::class),
            ref(\OAuth2Framework\Component\ClientAuthentication\ClientAuthenticationMiddleware::class),
            ref('token_revocation_method_handler'),
        ]);

    $container->set('token_revocation_method_handler')
        ->class(Middleware\HttpMethod::class)
        ->call('addMiddleware', ['POST', ref(\OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationPostEndpoint::class)])
        ->call('addMiddleware', ['GET', ref(\OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationGetEndpoint::class)]);

    $container->set(\OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationPostEndpoint::class)
        ->args([
            ref(\OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHintManager::class),
            ref('httplug.message_factory'),
        ]);

    $container->set(\OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationGetEndpoint::class)
        ->args([
            ref(\OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHintManager::class),
            ref('httplug.message_factory'),
            '%oauth2_server.endpoint.token_revocation.allow_callback%',
        ]);

};
