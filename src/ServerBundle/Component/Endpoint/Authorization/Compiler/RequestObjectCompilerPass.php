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

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler;

use Jose\Component\KeyManagement\JKUFactory;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequestLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RequestObjectCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('jose.jws_verifier.oauth2_server.endpoint.authorization.request_object') || !$container->hasDefinition(AuthorizationRequestLoader::class)) {
            return;
        }

        $metadata = $container->getDefinition(AuthorizationRequestLoader::class);
        $metadata->addMethodCall('enableSignedRequestObjectSupport', [new Reference('jose.jws_verifier.oauth2_server.endpoint.authorization.request_object'), new Reference('jose.claim_checker.oauth2_server.endpoint.authorization.request_object')]);

        if ($container->hasDefinition(JKUFactory::class)) {
            $metadata->addMethodCall('enableJkuSupport', [new Reference(JKUFactory::class)]);
        }
    }
}
