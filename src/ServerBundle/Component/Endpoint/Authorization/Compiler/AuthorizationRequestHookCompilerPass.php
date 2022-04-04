<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationEndpoint;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AuthorizationRequestHookCompilerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    final public const TAG_NAME = 'oauth2_server_authorization_endpoint_hook';

    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(AuthorizationEndpoint::class)) {
            return;
        }

        $definition = $container->getDefinition(AuthorizationEndpoint::class);

        $taggedServices = $this->findAndSortTaggedServices(self::TAG_NAME, $container);
        foreach ($taggedServices as $service) {
            $definition->addMethodCall('addHook', [$service]);
        }
    }
}
