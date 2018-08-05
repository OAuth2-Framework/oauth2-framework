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
use OAuth2Framework\SecurityBundle\DependencyInjection\Compiler\TokenTypeCompilerPass;
use OAuth2Framework\SecurityBundle\DependencyInjection\OAuth2FrameworkSecurityExtension;
use OAuth2Framework\SecurityBundle\Security\Factory\OAuth2SecurityFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class OAuth2FrameworkSecurityBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new OAuth2FrameworkSecurityExtension('oauth2_security');
    }

    public function build(ContainerBuilder $container)
    {
        if (!$container->hasExtension('security')) {
            throw new \RuntimeException('The security extension is not available');
        }
        $extension = $container->getExtension('security');
        if (!$extension instanceof SecurityExtension) {
            throw new \RuntimeException('Unsupported security extension');
        }
        $extension->addSecurityListenerFactory(new OAuth2SecurityFactory());

        $container->addCompilerPass(new SecurityAnnotationCheckerCompilerPass());
        $container->addCompilerPass(new AccessTokenHandlerCompilerPass());
        $container->addCompilerPass(new TokenTypeCompilerPass());
    }
}
