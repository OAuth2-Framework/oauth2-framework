<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Grant\AuthorizationCode;

use function array_key_exists;
use InvalidArgumentException;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PKCEMethodCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(PKCEMethodManager::class)) {
            return;
        }

        $definition = $container->getDefinition(PKCEMethodManager::class);

        $taggedServices = $container->findTaggedServiceIds('oauth2_server_pkce_method');
        $loaded = [];
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (! array_key_exists('alias', $attributes)) {
                    throw new InvalidArgumentException(sprintf(
                        'The PKCE method  "%s" does not have any "alias" attribute.',
                        $id
                    ));
                }
                $loaded[] = $attributes['alias'];
                $definition->addMethodCall('add', [new Reference($id)]);
            }
        }

        if (! $container->hasDefinition(MetadataBuilder::class)) {
            return;
        }

        $definition = $container->getDefinition(MetadataBuilder::class);
        $definition->addMethodCall('setCodeChallengeMethodsSupported', [new Reference(PKCEMethodManager::class)]);
    }
}
