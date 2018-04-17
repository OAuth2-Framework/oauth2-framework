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

namespace OAuth2Framework\SecurityBundle;

use OAuth2Framework\SecurityBundle\DependencyInjection\Compiler\AccessTokenHandlerCompilerPass;
use OAuth2Framework\SecurityBundle\DependencyInjection\Compiler\SecurityAnnotationCheckerCompilerPass;
use OAuth2Framework\SecurityBundle\DependencyInjection\OAuth2Extension;
use OAuth2Framework\SecurityBundle\Security\Factory\OAuth2SecurityFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OAuth2FrameworkSecurityBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new OAuth2Extension('oauth2_security');
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        /** @var \Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension $extension */
        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new OAuth2SecurityFactory());

        $container->addCompilerPass(new SecurityAnnotationCheckerCompilerPass());
        $container->addCompilerPass(new AccessTokenHandlerCompilerPass());
    }
}
