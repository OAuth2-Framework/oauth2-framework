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

namespace OAuth2Framework\ServerBundle\Component\OpenIdConnect\Compiler;

use OAuth2Framework\Component\OpenIdConnect\UserInfo\UserInfo;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class UserInfoPairwiseSubjectCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasAlias('oauth2_server.openid_connect.pairwise.service')) {
            return;
        }

        $definition = $container->getDefinition(UserInfo::class);
        $isDefault = $container->getParameter('oauth2_server.openid_connect.pairwise.is_default');
        $definition->addMethodCall('enablePairwiseSubject', [new Reference('oauth2_server.openid_connect.pairwise.service'), $isDefault]);
    }
}
