<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequestLoader;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RequestObjectReferenceCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasAlias('oauth2_server.http_client') || $container->getParameter(
            'oauth2_server.endpoint.authorization.request_object.reference.enabled'
        ) !== true || ! $container->hasDefinition(AuthorizationRequestLoader::class)) {
            return;
        }

        $metadata = $container->getDefinition(AuthorizationRequestLoader::class);
        $uriRegistrationRequired = $container->getParameter(
            'oauth2_server.endpoint.authorization.request_object.reference.uris_registration_required'
        );
        $metadata->addMethodCall(
            'enableRequestObjectReferenceSupport',
            [new Reference('oauth2_server.http_client'), new Reference(
                RequestFactoryInterface::class
            ), $uriRegistrationRequired]
        );
    }
}
