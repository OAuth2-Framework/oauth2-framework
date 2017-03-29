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

namespace OAuth2Framework\Bundle\Server\OpenIdConnectPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PairwiseSubjectIdentifierCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oauth2_server.openid_connect.userinfo')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_server.openid_connect.userinfo');
        $pairwiseSubjectIdentifier = $container->getParameter('oauth2_server.openid_connect.pairwise_subject_identifier');

        if (null !== $pairwiseSubjectIdentifier) {
            $definition->addMethodCall('enablePairwiseSubject', [new Reference($pairwiseSubjectIdentifier)]);
        }
    }
}
