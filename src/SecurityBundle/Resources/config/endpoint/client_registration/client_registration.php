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
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\Core\Message;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use OAuth2Framework\Component\ClientRegistrationEndpoint\ClientRegistrationEndpoint;
use OAuth2Framework\SecurityBundle\Middleware;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set('client_registration_endpoint_pipe')
        ->class(Middleware\Pipe::class)
        ->args([[
            ref('oauth2_server.client_registration.message_middleware'),
            ref('oauth2_server.client_registration.endpoint'),
        ]])
        ->tag('controller.service_arguments');

    $container->set('oauth2_server.client_registration.endpoint')
        ->class(ClientRegistrationEndpoint::class)
        ->args([
            ref('oauth2_server.client.id_generator'),
            ref('oauth2_server.client.repository'),
            ref(\Http\Message\ResponseFactory::class), //TODO
            ref('oauth2_server.client_rule.manager'),
        ]);

};
