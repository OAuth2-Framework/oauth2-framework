<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization;

use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class FormPostResponseModeSource implements Component
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $configs['endpoint']['authorization']['response_mode']['form_post'];
        $container->setParameter(
            'oauth2_server.endpoint.authorization.response_mode.form_post.enabled',
            $config['enabled']
        );
        if (! $config['enabled']) {
            return;
        }
        $container->setParameter(
            'oauth2_server.endpoint.authorization.response_mode.form_post.template',
            $config['template']
        );
        $loader = new PhpFileLoader($container, new FileLocator(
            __DIR__ . '/../../../Resources/config/endpoint/authorization'
        ));
        $loader->load('form_post_response_mode.php');
    }

    public function name(): string
    {
        return 'form_post';
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        $node->children()
            ->arrayNode($this->name())
            ->canBeEnabled()
            ->children()
            ->scalarNode('template')
            ->info('The template used to render the form.')
            ->defaultValue('@OAuth2FrameworkServerBundle/form_post/response.html.twig')
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }

    public function build(ContainerBuilder $container): void
    {
    }
}
