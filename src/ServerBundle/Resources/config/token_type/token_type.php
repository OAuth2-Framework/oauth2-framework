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

use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use OAuth2Framework\Component\Core\TokenType\TokenTypeMiddleware;
use OAuth2Framework\Component\Core\TokenType\TokenTypeParameterChecker;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
        ->autowire();

    $container->set(\OAuth2Framework\Component\Core\TokenType\TokenTypeParameterChecker::class)
        ->args([
            ref(TokenTypeManager::class),
            '%oauth2_server.token_type.allow_token_type_parameter%',
        ])
    ;

    $container->set(TokenTypeMiddleware::class)
        ->args([
            ref(TokenTypeManager::class),
            '%oauth2_server.token_type.allow_token_type_parameter%',
        ]);

    $container->set(TokenTypeParameterChecker::class)
        ->args([
            ref(TokenTypeManager::class),
            '%oauth2_server.token_type.allow_token_type_parameter%',
        ]);

    $container->set(TokenTypeManager::class);
};
