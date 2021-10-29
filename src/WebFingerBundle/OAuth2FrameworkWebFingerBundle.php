<?php

declare(strict_types=1);

namespace OAuth2Framework\WebFingerBundle;

use OAuth2Framework\WebFingerBundle\DependencyInjection\Compiler\IdentifierResolverCompilerPass;
use OAuth2Framework\WebFingerBundle\DependencyInjection\OAuth2FrameworkWebFingerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OAuth2FrameworkWebFingerBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new OAuth2FrameworkWebFingerExtension('webfinger');
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new IdentifierResolverCompilerPass());
    }
}
