<?php

declare(strict_types=1);

namespace OAuth2Framework\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class TokenTypeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition('oauth2_security.token_type_manager')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_security.token_type_manager');
        $taggedServices = $container->findTaggedServiceIds('oauth2_security_token_type');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
