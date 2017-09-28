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

use OAuth2Framework\Component\Server\Endpoint\Authorization\AuthorizationRequestLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RequestObjectCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('jose.jwt_loader.request_object') || !$container->hasDefinition(AuthorizationRequestLoader::class)) {
            return;
        }

        $metadata = $container->getDefinition(AuthorizationRequestLoader::class);
        $metadata->addMethodCall('enableRequestObjectSupport', [new Reference('jose.jwt_loader.request_object'), []]); //FIXME
        if (true === $container->getParameter('oauth2_server.endpoint.authorization.request_object.encryption.enabled')) {
            $required = $container->getParameter('oauth2_server.endpoint.authorization.request_object.encryption.required');
            $metadata->addMethodCall('enableEncryptedRequestObjectSupport', [new Reference('oauth2_server.endpoint.authorization.request_object.encryption.key_set'), $required]);
        }

        if (true === $container->getParameter('oauth2_server.endpoint.authorization.request_object.reference.enabled')) {
            $uriRegistration = $container->getParameter('oauth2_server.endpoint.authorization.request_object.reference.uris_registration_required');
            $metadata->addMethodCall('enableRequestObjectReferenceSupport', [new Reference('oauth2_server.http.client'), $uriRegistration]);
        }
    }
}
