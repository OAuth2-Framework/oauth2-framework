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
use OAuth2Framework\Component\OpenIdConnect\UserInfoEndpoint\UserInfoEndpoint;
use OAuth2Framework\Component\OpenIdConnect\IdTokenBuilderFactory;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\Core\Middleware\Pipe;
use OAuth2Framework\Component\OpenIdConnect\Rule\UserinfoEndpointAlgorithmsRule;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(UserInfoEndpoint::class)
        ->args([
            ref(IdTokenBuilderFactory::class),
            ref('oauth2_server.client.repository'),
            ref(UserAccountRepository::class),
            ref('httplug.message_factory'),
        ]);

    $container->set('userinfo_security_middleware')
        ->class(\OAuth2Framework\Component\Core\Middleware\AccessTokenMiddleware::class)
        ->args([
            ref(\OAuth2Framework\Component\Core\TokenType\TokenTypeManager::class),
            ref(\OAuth2Framework\Component\Core\AccessToken\AccessTokenHandlerManager::class),
        ]);

    $container->set('oauth2_server_userinfo_pipe')
        ->class(Pipe::class)
        ->args([
            ref('userinfo_security_middleware'),
            ref('userinfo_security_middleware'),
            ref(UserInfoEndpoint::class),
        ])
        ->tag('controller.service_arguments');

    $container->set(UserinfoEndpointAlgorithmsRule::class)
        ->args([
            ref('jose.jws_builder.oauth2_server.openid_connect.id_token_from_userinfo')->nullOnInvalid(),
            ref('jose.jwe_builder.oauth2_server.openid_connect.id_token_from_userinfo')->nullOnInvalid(),
        ]);
};
