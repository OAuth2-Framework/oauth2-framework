<?php

declare(strict_types=1);

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FormPostResponseMode;
use OAuth2Framework\ServerBundle\Service\TwigFormPostResponseRenderer;
/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(TwigFormPostResponseRenderer::class)
        ->args([service('twig'), '%oauth2_server.endpoint.authorization.response_mode.form_post.template%'])
    ;

    $container->set(FormPostResponseMode::class)
        ->args([service(TwigFormPostResponseRenderer::class), service(ResponseFactoryInterface::class)])
    ;
};
