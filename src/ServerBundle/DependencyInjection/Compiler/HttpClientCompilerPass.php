<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\DependencyInjection\Compiler;

use Psr\Http\Client\ClientInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpClient\Psr18Client;

class HttpClientCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->has(ClientInterface::class)) {
            return;
        }
        if (! $container->has(Psr18Client::class)) {
            $container->setDefinition(Psr18Client::class, new Definition(Psr18Client::class));
        }

        $container->setAlias(ClientInterface::class, Psr18Client::class);
    }
}
