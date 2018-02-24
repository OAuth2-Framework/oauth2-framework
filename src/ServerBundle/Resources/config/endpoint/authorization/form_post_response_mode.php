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
use OAuth2Framework\ServerBundle\Service\TwigFormPostResponseRenderer;
use OAuth2Framework\Component\AuthorizationEndpoint;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(TwigFormPostResponseRenderer::class)
        ->args([
            ref('templating'),
            '%oauth2_server.endpoint.authorization.response_mode.form_post.template%',
        ]);

    $container->set(AuthorizationEndpoint\ResponseMode\FormPostResponseMode::class)
        ->args([
            ref(TwigFormPostResponseRenderer::class),
            ref('httplug.message_factory'),
        ]);
};
