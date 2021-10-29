<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\SessionManagement;

use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Endpoint\SessionManagement\Compiler\SessionManagementRouteCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class SessionManagementEndpointSource implements Component
{
    public function name(): string
    {
        return 'session_management';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $configs['endpoint']['session_management'];
        $container->setParameter('oauth2_server.endpoint.session_management.enabled', $config['enabled']);
        if (! $config['enabled']) {
            return;
        }
        $container->setParameter('oauth2_server.endpoint.session_management.path', $config['path']);
        $container->setParameter('oauth2_server.endpoint.session_management.host', $config['host']);
        $container->setParameter('oauth2_server.endpoint.session_management.storage_name', $config['storage_name']);
        $container->setParameter('oauth2_server.endpoint.session_management.template', $config['template']);

        $loader = new PhpFileLoader($container, new FileLocator(
            __DIR__ . '/../../../Resources/config/endpoint/session_management'
        ));
        $loader->load('session_management.php');
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        $node->children()
            ->arrayNode($this->name())
            ->canBeEnabled()
            ->validate()
            ->ifTrue(static function ($config): bool {
                return $config['enabled'] === true && $config['path'] === null;
            })
            ->thenInvalid('The route name must be set.')
            ->end()
            ->validate()
            ->ifTrue(static function ($config): bool {
                return $config['enabled'] === true && $config['storage_name'] === null;
            })->thenInvalid('The option "storage_name" must be set.')
            ->end()
            ->validate()
            ->ifTrue(static function ($config): bool {
                return $config['enabled'] === true && $config['template'] === null;
            })->thenInvalid('The option "template" must be set.')
            ->end()
            ->children()
            ->scalarNode('path')
            ->info('The session management endpoint')
            ->defaultValue('/session')
            ->end()
            ->scalarNode('host')
            ->info('If set, the route will be limited to that host')
            ->defaultValue('')
            ->treatNullLike('')
            ->treatFalseLike('')
            ->end()
            ->scalarNode('storage_name')
            ->info('The name used for the cookie storage')
            ->defaultValue('oidc_browser_state')
            ->end()
            ->scalarNode('template')
            ->info('The template of the OP iframe.')
            ->defaultValue('@OAuth2FrameworkServerBundle/iframe/iframe.html.twig')
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new SessionManagementRouteCompilerPass());
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
