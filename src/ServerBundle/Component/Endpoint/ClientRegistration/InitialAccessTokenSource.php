<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\ClientRegistration;

use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenRepository;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Endpoint\ClientRegistration\Compiler\InitialAccessTokenCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class InitialAccessTokenSource implements Component
{
    public function name(): string
    {
        return 'initial_access_token';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $configs['endpoint']['client_registration']['initial_access_token'];
        if (! $config['enabled']) {
            return;
        }
        $container->setParameter(
            'oauth2_server.endpoint.client_registration.initial_access_token.required',
            $config['required']
        );
        $container->setParameter(
            'oauth2_server.endpoint.client_registration.initial_access_token.realm',
            $config['realm']
        );
        $container->setParameter(
            'oauth2_server.endpoint.client_registration.initial_access_token.min_length',
            $config['min_length']
        );
        $container->setParameter(
            'oauth2_server.endpoint.client_registration.initial_access_token.max_length',
            $config['max_length']
        );
        $container->setAlias(InitialAccessTokenRepository::class, $config['repository']);

        $loader = new PhpFileLoader($container, new FileLocator(
            __DIR__ . '/../../../Resources/config/endpoint/client_registration'
        ));
        $loader->load('initial_access_token.php');
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        $node->children()
            ->arrayNode($this->name())
            ->canBeEnabled()
            ->validate()
            ->ifTrue(static function ($config): bool {
                return $config['enabled'] === true && $config['realm'] === null;
            })
            ->thenInvalid('The option "realm" must be set.')
            ->end()
            ->validate()
            ->ifTrue(static function ($config): bool {
                return $config['enabled'] === true && $config['repository'] === null;
            })
            ->thenInvalid('The option "repository" must be set.')
            ->end()
            ->validate()
            ->ifTrue(static function ($config): bool {
                return $config['enabled'] === true && $config['max_length'] < $config['min_length'];
            })
            ->thenInvalid('The option "max_length" must be greater than "min_length".')
            ->end()
            ->children()
            ->booleanNode('required')
            ->defaultFalse()
            ->end()
            ->scalarNode('realm')
            ->defaultNull()
            ->end()
            ->integerNode('min_length')
            ->defaultValue(50)
            ->min(0)
            ->end()
            ->integerNode('max_length')
            ->defaultValue(100)
            ->min(1)
            ->end()
            ->scalarNode('repository')
            ->defaultNull()
            ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new InitialAccessTokenCompilerPass());
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
