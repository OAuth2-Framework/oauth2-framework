<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Grant\AuthorizationCode;

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository;
use OAuth2Framework\Component\OpenIdConnect\IdTokenBuilderFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AuthorizationCodeSupportForIdTokenBuilderCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(IdTokenBuilderFactory::class) || $container->hasAlias(
            AuthorizationCodeRepository::class
        )) {
            return;
        }

        $definition = $container->getDefinition(IdTokenBuilderFactory::class);
        $definition->addMethodCall(
            'enableAuthorizationCodeSupport',
            [new Reference(AuthorizationCodeRepository::class)]
        );
    }
}
