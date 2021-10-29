<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequestLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RequestObjectEncryptionCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(
            'jose.jwe_loader.oauth2_server.endpoint.authorization.request_object'
        ) || ! $container->hasDefinition(AuthorizationRequestLoader::class)) {
            return;
        }

        $metadata = $container->getDefinition(AuthorizationRequestLoader::class);
        $required = $container->getParameter('oauth2_server.endpoint.authorization.request_object.encryption.required');
        $metadata->addMethodCall(
            'enableEncryptedRequestObjectSupport',
            [new Reference('jose.jwe_loader.oauth2_server.endpoint.authorization.request_object'), new Reference(
                'jose.key_set.oauth2_server.endpoint.authorization.request_object'
            ), $required]
        );
    }
}
