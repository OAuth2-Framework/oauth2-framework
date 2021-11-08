<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\TokenType;

use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class TokenTypeSource implements Component
{
    public function name(): string
    {
        return 'token_type';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../Resources/config/token_type'));
        $loader->load('token_type.php');

        $container->setParameter('oauth2_server.token_type.default', $configs['token_type']['default']);
        $container->setParameter(
            'oauth2_server.token_type.allow_token_type_parameter',
            $configs['token_type']['allow_token_type_parameter']
        );

        if (class_exists(BearerToken::class) && $configs['token_type']['bearer_token']['enabled']) {
            $loader->load('bearer_token.php');
        }
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        $child = $node->children()
            ->arrayNode($this->name())
            ->isRequired()
            ->children()
            ->scalarNode('default')
            ->defaultValue('bearer')
            ->info('The default token type used for access token issuance.')
            ->end()
            ->booleanNode('allow_token_type_parameter')
            ->defaultFalse()
            ->info('If true, the "token_type" parameter will be allowed in requests.')
            ->end()
            ->end()
        ;

        if (class_exists(BearerToken::class)) {
            $child->children()
                ->arrayNode('bearer_token')
                ->addDefaultsIfNotSet()
                ->canBeDisabled()
                ->end()
                ->end()
            ;
        }
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new TokenTypeCompilerPass());
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        //Nothing to do
        return [];
    }
}
