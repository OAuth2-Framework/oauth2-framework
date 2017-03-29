<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Compiler;

use OAuth2Framework\Component\Server\Endpoint\Authorization\UserAccountDiscovery\IdTokenHintDiscovery;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\UserInfo;
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
        if (!$container->hasDefinition(UserInfo::class) && true === $container->getParameter('oauth2_server.endpoint.userinfo.enabled') && true === $container->getParameter('oauth2_server.endpoint.userinfo.pairwise_subject.enabled')) {
            return;
        }

        $definition = $container->getDefinition(UserInfo::class);
        $service = $container->getAlias('oauth2_server.grant.id_token.pairwise_subject.service');
        $isDefault = $container->getParameter('oauth2_server.grant.id_token.pairwise_subject.is_default');
        $definition->addMethodCall('enablePairwiseSubject', [new Reference($service), $isDefault]);

        // Enabled the pairwise support for the Id Token Hint Discovery service if available
        if ($container->hasDefinition(IdTokenHintDiscovery::class)) {
            $definition = $container->getDefinition(IdTokenHintDiscovery::class);
            $definition->addMethodCall('enablePairwiseSubject', [new Reference($service)]);
        }
    }
}
