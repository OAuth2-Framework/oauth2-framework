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

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequestLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RequestObjectReferenceCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasAlias('oauth2_server.http_client') || !$container->getParameter('oauth2_server.endpoint.authorization.request_object.reference.enabled') || !$container->hasDefinition(AuthorizationRequestLoader::class)) {
            return;
        }

        $metadata = $container->getDefinition(AuthorizationRequestLoader::class);
        $uriRegistrationRequired = $container->getParameter('oauth2_server.endpoint.authorization.request_object.reference.uris_registration_required');
        $metadata->addMethodCall('enableRequestObjectReferenceSupport', [new Reference('oauth2_server.http_client'), $uriRegistrationRequired]);
    }
}
