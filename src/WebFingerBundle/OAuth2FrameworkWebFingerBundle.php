<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\WebFingerBundle;

use OAuth2Framework\WebFingerBundle\DependencyInjection\Compiler\IdentifierResolverCompilerPass;
use OAuth2Framework\WebFingerBundle\DependencyInjection\OAuth2FrameworkWebFingerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OAuth2FrameworkWebFingerBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new OAuth2FrameworkWebFingerExtension('webfinger');
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new IdentifierResolverCompilerPass());
    }
}
