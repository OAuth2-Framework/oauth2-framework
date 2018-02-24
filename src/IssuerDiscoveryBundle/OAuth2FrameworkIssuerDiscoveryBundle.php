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

namespace OAuth2Framework\IssuerDiscoveryBundle;

use OAuth2Framework\IssuerDiscoveryBundle\DependencyInjection\Compiler\IdentifierResolverCompilerPass;
use OAuth2Framework\IssuerDiscoveryBundle\DependencyInjection\Compiler\IssuerDiscoveryCompilerPass;
use OAuth2Framework\IssuerDiscoveryBundle\DependencyInjection\OAuth2FrameworkIssuerDiscoveryExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OAuth2FrameworkIssuerDiscoveryBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new OAuth2FrameworkIssuerDiscoveryExtension('issuer_discovery');
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new IssuerDiscoveryCompilerPass());
        $container->addCompilerPass(new IdentifierResolverCompilerPass());
    }
}
