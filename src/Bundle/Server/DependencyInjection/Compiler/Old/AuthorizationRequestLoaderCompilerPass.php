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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AuthorizationRequestLoaderCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oauth2_server.authorization_request_loader') && $container->has('jose.jwt_loader.authorization_endpoint_authorization_request')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_server.authorization_request_loader');
        $definition->addMethodCall('enableRequestObjectSupport', [new Reference('jose.jwt_loader.authorization_endpoint_authorization_request')]);

        $this->setupUnsecuredConnectionsOption($container, $definition);
        $this->setupRequestObjectReferenceSupport($container, $definition);
        if (true === $container->hasAlias('oauth2_server.authorization_request_loader.request_object.encryption.key_set')) {
            $this->setupEncryptedAuthorizationRequestSupport($container, $definition);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param Definition       $definition
     */
    private function setupRequestObjectReferenceSupport(ContainerBuilder $container, Definition $definition)
    {
        if (false === $container->getParameter('oauth2_server.authorization_request_loader.request_object.reference_enabled')) {
            return;
        }

        $definition->addMethodCall('enableRequestObjectReferenceSupport');

        $reference_uris_registration_required = $container->getParameter('oauth2_server.authorization_request_loader.request_object.reference_uris_registration_required');
        $method = sprintf('%sableRequestUriRegistrationRequirement', $reference_uris_registration_required ? 'en' : 'dis');
        $definition->addMethodCall($method);
    }

    /**
     * @param ContainerBuilder $container
     * @param Definition       $definition
     */
    private function setupUnsecuredConnectionsOption(ContainerBuilder $container, Definition $definition)
    {
        $are_unsecured_connections_allowed = $container->getParameter('oauth2_server.authorization_request_loader.request_object.allow_unsecured_connections');
        $method = sprintf('%sallowUnsecuredConnections', $are_unsecured_connections_allowed ? 'dis' : '');
        $definition->addMethodCall($method);
    }

    /**
     * @param ContainerBuilder $container
     * @param Definition       $definition
     */
    private function setupEncryptedAuthorizationRequestSupport(ContainerBuilder $container, Definition $definition)
    {
        $definition->addMethodCall('enableEncryptedRequestObjectSupport', [
            new Reference($container->getAlias('oauth2_server.authorization_request_loader.request_object.encryption.key_set')),
            $container->getParameter('oauth2_server.authorization_request_loader.request_object.encryption.required'),
        ]);
    }
}
