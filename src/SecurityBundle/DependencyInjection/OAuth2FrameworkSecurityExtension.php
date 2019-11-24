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

namespace OAuth2Framework\SecurityBundle\DependencyInjection;

use OAuth2Framework\Component\Core\AccessToken\AccessTokenHandler;
use OAuth2Framework\SecurityBundle\Annotation\Checker\Checker;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class OAuth2FrameworkSecurityExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(Checker::class)->addTag('oauth2_security_annotation_checker');
        $container->registerForAutoconfiguration(AccessTokenHandler::class)->addTag('oauth2_security_token_handler');

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/'));
        $loader->load('security.php');
    }
}
