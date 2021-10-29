<?php

declare(strict_types=1);

use OAuth2Framework\Component\Core\AccessToken\AccessTokenIntrospectionTypeHint;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\Core\Middleware\Pipe;
use OAuth2Framework\Component\ResourceServerAuthentication\AuthenticationMiddleware;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenIntrospectionEndpoint;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHintManager;
use OAuth2Framework\ServerBundle\Controller\PipeController;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set('token_introspection_pipe')
        ->class(Pipe::class)
        ->args([
            service('oauth2_server.message_middleware.for_client_authentication'),
            service(AuthenticationMiddleware::class),
            service(TokenIntrospectionEndpoint::class),
        ])
    ;

    $container->set('token_introspection_endpoint_pipe')
        ->class(PipeController::class)
        ->args([service('token_introspection_pipe')])
        ->tag('controller.service_arguments')
    ;

    $container->set(TokenTypeHintManager::class);

    $container->set(TokenIntrospectionEndpoint::class)
        ->args([service(TokenTypeHintManager::class), service(ResponseFactoryInterface::class)])
    ;

    $container->set(AccessTokenIntrospectionTypeHint::class)
        ->args([service(AccessTokenRepository::class)])
    ;
};
