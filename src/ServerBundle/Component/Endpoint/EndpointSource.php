<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint;

use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\AuthorizationEndpointSource;
use OAuth2Framework\ServerBundle\Component\Endpoint\ClientConfiguration\ClientConfigurationSource;
use OAuth2Framework\ServerBundle\Component\Endpoint\ClientRegistration\ClientRegistrationSource;
use OAuth2Framework\ServerBundle\Component\Endpoint\JwksUri\JwksUriEndpointSource;
use OAuth2Framework\ServerBundle\Component\Endpoint\Metadata\MetadataEndpointSource;
use OAuth2Framework\ServerBundle\Component\Endpoint\SessionManagement\SessionManagementEndpointSource;
use OAuth2Framework\ServerBundle\Component\Endpoint\Token\TokenEndpointSource;
use OAuth2Framework\ServerBundle\Component\Endpoint\TokenIntrospection\TokenIntrospectionEndpointSource;
use OAuth2Framework\ServerBundle\Component\Endpoint\TokenRevocation\TokenRevocationEndpointSource;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EndpointSource implements Component
{
    /**
     * @var Component[]
     */
    private array $subComponents = [];

    public function __construct()
    {
        $this->subComponents = [
            new ClientRegistrationSource(),
            new ClientConfigurationSource(),
            new AuthorizationEndpointSource(),
            new TokenEndpointSource(),
            new TokenIntrospectionEndpointSource(),
            new TokenRevocationEndpointSource(),
            new JwksUriEndpointSource(),
            new MetadataEndpointSource(),
            new SessionManagementEndpointSource(),
        ];
    }

    public function name(): string
    {
        return 'endpoint';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        foreach ($this->subComponents as $subComponent) {
            $subComponent->load($configs, $container);
        }
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        $childNode = $node->children()
            ->arrayNode($this->name())
            ->addDefaultsIfNotSet()
        ;

        foreach ($this->subComponents as $subComponent) {
            $subComponent->getNodeDefinition($childNode, $node);
        }
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        $updatedConfig = [];
        foreach ($this->subComponents as $subComponent) {
            $updatedConfig = array_merge($updatedConfig, $subComponent->prepend($container, $config));
        }

        return $updatedConfig;
    }

    public function build(ContainerBuilder $container): void
    {
        foreach ($this->subComponents as $component) {
            $component->build($container);
        }
    }
}
