<?php

declare(strict_types=1);
use OAuth2Framework\Component\Core\Middleware\Pipe;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeMiddleware;
use OAuth2Framework\Component\TokenEndpoint\TokenEndpoint;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeManager;
use OAuth2Framework\Component\TokenEndpoint\Extension\TokenEndpointExtensionManager;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository;
use Psr\Http\Message\ResponseFactoryInterface;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\Core\Middleware;
use OAuth2Framework\Component\Core\TokenType\TokenTypeMiddleware;
use OAuth2Framework\Component\TokenEndpoint;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set('token_endpoint_pipe')
        ->class(Pipe::class)
        ->args([[
            ref('oauth2_server.message_middleware.for_client_authentication'),
            ref('oauth2_server.client_authentication.middleware'),
            ref(GrantTypeMiddleware::class),
            ref(TokenTypeMiddleware::class),
            ref(TokenEndpoint::class),
        ]])
        ->tag('controller.service_arguments')
    ;

    $container->set(GrantTypeMiddleware::class)
        ->args([
            ref(GrantTypeManager::class),
        ])
    ;

    $container->set(TokenEndpointExtensionManager::class);

    $container->set(TokenEndpoint::class)
        ->args([
            ref(ClientRepository::class),
            ref(UserAccountRepository::class)->nullOnInvalid(),
            ref(TokenEndpointExtensionManager::class),
            ref(ResponseFactoryInterface::class),
            ref(AccessTokenRepository::class),
            '%oauth2_server.access_token_lifetime%',
        ])
    ;
};
