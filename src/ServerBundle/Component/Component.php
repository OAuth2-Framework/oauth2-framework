<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

interface Component
{
    public function name(): string;

    public function load(array $configs, ContainerBuilder $container): void;

    public function build(ContainerBuilder $container): void;

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void;

    public function prepend(ContainerBuilder $container, array $config): array;
}
