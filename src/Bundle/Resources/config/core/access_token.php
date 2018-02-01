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
use OAuth2Framework\Component\Core\AccessToken\AccessTokenHandlerManager;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenIntrospectionTypeHint;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRevocationTypeHint;
use OAuth2Framework\Component\Core\AccessToken\Command;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->public()
        ->autoconfigure();

    $container->set(Command\CreateAccessTokenCommandHandler::class)
        ->args([
            ref('oauth2_server.access_token.repository'),
        ]);

    $container->set(Command\RevokeAccessTokenCommandHandler::class)
        ->args([
            ref('oauth2_server.access_token.repository'),
        ]);

    $container->set(AccessTokenHandlerManager::class);

    $container->set(AccessTokenIntrospectionTypeHint::class)
        ->tag('oauth2_server_introspection_token_type');

    $container->set(AccessTokenRevocationTypeHint::class)
        ->tag('oauth2_server_revocation_token_type');
};
