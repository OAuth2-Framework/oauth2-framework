<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\ClientAuthentication;

use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class ClientSecretBasicSource implements Component
{
    public function name(): string
    {
        return 'client_secret_basic';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->setParameter(
            'oauth2_server.client_authentication.client_secret_basic.realm',
            $configs['client_authentication']['client_secret_basic']['realm']
        );
        $container->setParameter(
            'oauth2_server.client_authentication.client_secret_basic.secret_lifetime',
            $configs['client_authentication']['client_secret_basic']['secret_lifetime']
        );
        $loader = new PhpFileLoader($container, new FileLocator(
            __DIR__ . '/../../Resources/config/client_authentication'
        ));
        $loader->load('client_secret_basic.php');
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        $node->children()
            ->arrayNode($this->name())
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('realm')
            ->isRequired()
            ->info('The realm displayed in the authentication header')
            ->end()
            ->integerNode('secret_lifetime')
            ->defaultValue(60 * 60 * 24 * 14) // 14 days
            ->min(0)
            ->info('Secret lifetime (in seconds; 0 = unlimited)')
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
