<?php

declare(strict_types=1);

namespace OAuth2Framework\SecurityBundle\DependencyInjection;

use OAuth2Framework\SecurityBundle\Annotation\Checker\Checker;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class OAuth2FrameworkSecurityExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(Checker::class)->addTag('oauth2_security_annotation_checker');

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config/'));
        $loader->load('security.php');
    }
}
