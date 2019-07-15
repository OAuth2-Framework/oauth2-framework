<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FragmentResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\QueryResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseModeGuesser;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
    ;

    // Response Mode
    $container->set(ResponseModeManager::class);

    $container->set(QueryResponseMode::class)
        ->args([
            ref(ResponseFactoryInterface::class),
        ])
    ;
    $container->set(FragmentResponseMode::class)
        ->args([
            ref(ResponseFactoryInterface::class),
        ])
    ;

    $container->set(ResponseModeGuesser::class)
        ->args([
            ref(ResponseModeManager::class),
            '%oauth2_server.endpoint.authorization.response_mode.allow_response_mode_parameter%',
        ])
    ;
};
