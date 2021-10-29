<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\OpenIdConnect\Compiler;

use OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport\UserInfoScopeSupportManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class UserInfoScopeSupportCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(UserInfoScopeSupportManager::class)) {
            return;
        }

        $definition = $container->getDefinition(UserInfoScopeSupportManager::class);

        $taggedServices = $container->findTaggedServiceIds('oauth2_server_userinfo_scope_support');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
