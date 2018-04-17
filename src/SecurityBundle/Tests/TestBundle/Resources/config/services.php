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
use OAuth2Framework\SecurityBundle\Tests\TestBundle\Entity\AccessTokenRepository;
use OAuth2Framework\SecurityBundle\Tests\TestBundle\Service\AccessTokenHandler;
use OAuth2Framework\SecurityBundle\Tests\TestBundle\Service\UserProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->public()
        ->autowire()
        ->autoconfigure();

    $container->set(UserProvider::class)
        ->args([
            ref(\OAuth2Framework\SecurityBundle\Tests\TestBundle\Entity\UserRepository::class),
        ]);

    $container->set(\OAuth2Framework\SecurityBundle\Tests\TestBundle\Entity\UserRepository::class);
    $container->set(AccessTokenRepository::class);
    $container->set(AccessTokenHandler::class)
        ->args([
            ref(AccessTokenRepository::class)
        ]);
};
