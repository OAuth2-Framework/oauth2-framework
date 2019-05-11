<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

use OAuth2Framework\Component\AuthorizationEndpoint;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    // Response Mode
    $container->set(AuthorizationEndpoint\ResponseMode\ResponseModeManager::class);

    $container->set(AuthorizationEndpoint\ResponseMode\QueryResponseMode::class)
        ->args([
            ref(\Psr\Http\Message\ResponseFactoryInterface::class),
        ]);
    $container->set(AuthorizationEndpoint\ResponseMode\FragmentResponseMode::class)
        ->args([
            ref(\Psr\Http\Message\ResponseFactoryInterface::class),
        ]);

    $container->set(ParameterChecker\ResponseTypeAndResponseModeParameterChecker::class)
        ->args([
            ref(AuthorizationEndpoint\ResponseType\ResponseTypeManager::class),
            ref(AuthorizationEndpoint\ResponseMode\ResponseModeManager::class),
            '%oauth2_server.endpoint.authorization.response_mode.allow_response_mode_parameter%',
        ]);
};
