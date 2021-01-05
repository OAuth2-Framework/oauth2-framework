<?php

declare(strict_types=1);
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FormPostResponseMode;
use Psr\Http\Message\ResponseFactoryInterface;

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\AuthorizationEndpoint;
use OAuth2Framework\ServerBundle\Service\TwigFormPostResponseRenderer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(TwigFormPostResponseRenderer::class)
        ->args([
            ref('twig'),
            '%oauth2_server.endpoint.authorization.response_mode.form_post.template%',
        ])
    ;

    $container->set(FormPostResponseMode::class)
        ->args([
            ref(TwigFormPostResponseRenderer::class),
            ref(ResponseFactoryInterface::class),
        ])
    ;
};
