<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\ClientRegistration;

use function count;
use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class SoftwareStatementSource implements Component
{
    public function name(): string
    {
        return 'software_statement';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $configs['endpoint']['client_registration']['software_statement'];
        $container->setParameter(
            'oauth2_server.endpoint.client_registration.software_statement.enabled',
            $config['enabled']
        );
        if (! $config['enabled']) {
            return;
        }
        $container->setParameter(
            'oauth2_server.endpoint.client_registration.software_statement.required',
            $config['required']
        );
        $container->setParameter(
            'oauth2_server.endpoint.client_registration.software_statement.allowed_signature_algorithms',
            $config['allowed_signature_algorithms']
        );
        $container->setParameter(
            'oauth2_server.endpoint.client_registration.software_statement.key_set',
            $config['key_set']
        );

        $loader = new PhpFileLoader($container, new FileLocator(
            __DIR__ . '/../../../Resources/config/endpoint/client_registration'
        ));
        $loader->load('software_statement.php');
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        $node->children()
            ->arrayNode($this->name())
            ->canBeEnabled()
            ->validate()
            ->ifTrue(static function ($config): bool {
                return $config['enabled'] === true && $config['key_set'] === null;
            })
            ->thenInvalid('The option "key_set" must be set.')
            ->end()
            ->validate()
            ->ifTrue(static function ($config): bool {
                return $config['enabled'] === true && count($config['allowed_signature_algorithms']) === 0;
            })
            ->thenInvalid('At least one signature algorithm must be set.')
            ->end()
            ->children()
            ->booleanNode('required')
            ->info('If true, the software statement is mandatory, otherwise optional.')
            ->defaultFalse()
            ->end()
            ->scalarNode('key_set')
            ->info('The public keys used to verify the software statement.')
            ->end()
            ->arrayNode('allowed_signature_algorithms')
            ->info('Signature algorithms allowed for the software statements. The algorithm "none" should not be used.')
            ->useAttributeAsKey('name')
            ->scalarPrototype()
            ->end()
            ->treatNullLike([])
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
        $sourceConfig = $config['endpoint']['client_registration']['software_statement'];
        if (! $sourceConfig['enabled']) {
            return [];
        }

        ConfigurationHelper::addJWSLoader(
            $container,
            'oauth2_server.endpoint.client_registration.software_statement',
            ['jws_compact'],
            $sourceConfig['allowed_signature_algorithms'],
            [],
            false
        );
        ConfigurationHelper::addKeyset(
            $container,
            'oauth2_server.endpoint.client_registration.software_statement',
            'jwkset',
            [
                'value' => $sourceConfig['key_set'],
            ]
        );

        return [];
    }
}
