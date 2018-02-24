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

class RequestObjectCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('jose.jws_loader.request_object') || !$container->hasDefinition(AuthorizationRequestLoader::class)) {
            return;
        }

        $metadata = $container->getDefinition(AuthorizationRequestLoader::class);
        $metadata->addMethodCall('enableRequestObjectSupport', [new Reference('jose.jws_loader.request_object'), new Reference('jose.claim_checker.request_object'), []]); //FIXME
        if (true === $container->getParameter('oauth2_server.endpoint.authorization.request_object.encryption.enabled')) {
            $required = $container->getParameter('oauth2_server.endpoint.authorization.request_object.encryption.required');
            $metadata->addMethodCall('enableEncryptedRequestObjectSupport', [new Reference('jose.jwe_loader.request_object'), new Reference('jose.key_set.oauth2_server.key_set.encryption'), $required]);
        }

        if (true === $container->getParameter('oauth2_server.endpoint.authorization.request_object.reference.enabled')) {
            $uriRegistration = $container->getParameter('oauth2_server.endpoint.authorization.request_object.reference.uris_registration_required');
            $metadata->addMethodCall('enableRequestObjectReferenceSupport', [new Reference('oauth2_server.http.client'), $uriRegistration]);
        }
    }
}
