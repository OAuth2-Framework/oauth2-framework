<?php

declare(strict_types=1);

use OAuth2Framework\Component\Core\ResourceServer\ResourceServerRepository;
/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\ResourceServerAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\ResourceServerAuthentication\AuthenticationMiddleware;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(AuthenticationMiddleware::class)
        ->args([service(ResourceServerRepository::class), service(AuthenticationMethodManager::class)])
    ;
};
