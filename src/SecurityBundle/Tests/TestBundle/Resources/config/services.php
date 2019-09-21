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

use OAuth2Framework\SecurityBundle\Tests\TestBundle\Controller\ApiController;
use OAuth2Framework\SecurityBundle\Tests\TestBundle\Service\AccessTokenHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->public()
        ->autowire()
        ->autoconfigure()
    ;

    $container->set(ApiController::class);
    $container->set(AccessTokenHandler::class)/*
        ->tag('oauth2_server_access_token_handler')*/;
};
