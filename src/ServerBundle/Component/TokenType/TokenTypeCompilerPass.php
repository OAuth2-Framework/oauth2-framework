<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\TokenType;

use function array_key_exists;
use InvalidArgumentException;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TokenTypeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(TokenTypeManager::class)) {
            return;
        }

        $definition = $container->getDefinition(TokenTypeManager::class);
        $default = $container->getParameter('oauth2_server.token_type.default');
        $taggedServices = $container->findTaggedServiceIds('oauth2_server_token_type');
        $default_found = false;
        $token_type_names = [];
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (! array_key_exists('scheme', $attributes)) {
                    throw new InvalidArgumentException(sprintf(
                        'The token type "%s" does not have any "scheme" attribute.',
                        $id
                    ));
                }
                $is_default = $default === $attributes['scheme'];
                $token_type_names[] = $attributes['scheme'];
                if ($is_default === true) {
                    $default_found = true;
                }
                $definition->addMethodCall('add', [new Reference($id), $is_default]);
            }
        }

        if (! $default_found) {
            throw new InvalidArgumentException(sprintf(
                'Unable to find the token type "%s". Available token types are: %s.',
                $default,
                implode(', ', $token_type_names)
            ));
        }
    }
}
