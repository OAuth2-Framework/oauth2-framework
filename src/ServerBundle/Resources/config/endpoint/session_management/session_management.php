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

use OAuth2Framework\Component\Core\Middleware\Pipe;
use OAuth2Framework\ServerBundle\Service\IFrameEndpoint;
use OAuth2Framework\ServerBundle\Service\SessionStateParameterExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set('oauth2_server.endpoint.session_management_pipe')
        ->class(Pipe::class)
        ->args([[
            ref(IFrameEndpoint::class),
        ]])
        ->tag('controller.service_arguments')
    ;

    $container->set(IFrameEndpoint::class)
        ->args([
            ref('templating'),
            ref('httplug.message_factory'),
            '%oauth2_server.endpoint.session_management.template%',
            '%oauth2_server.endpoint.session_management.storage_name%',
        ])
    ;

    $container->set(SessionStateParameterExtension::class)
        ->args([
            ref('session'),
            '%oauth2_server.endpoint.session_management.storage_name%',
        ])
        ->tag('oauth2_server_after_consent_screen')
    ;
};
