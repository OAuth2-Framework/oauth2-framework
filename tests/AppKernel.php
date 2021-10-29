<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Jose\Bundle\JoseFramework\JoseFrameworkBundle;
use OAuth2Framework\SecurityBundle\OAuth2FrameworkSecurityBundle;
use OAuth2Framework\ServerBundle\OAuth2FrameworkServerBundle;
use OAuth2Framework\Tests\TestBundle\TestBundle;
use OAuth2Framework\WebFingerBundle\OAuth2FrameworkWebFingerBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

final class AppKernel extends Kernel
{
    public function __construct(string $environment, bool $debug)
    {
        $debug = false;
        parent::__construct($environment, $debug);
    }

    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
            new SensioFrameworkExtraBundle(),
            new DoctrineBundle(),
            new JoseFrameworkBundle(),

            new OAuth2FrameworkSecurityBundle(),
            new OAuth2FrameworkServerBundle(),
            new OAuth2FrameworkWebFingerBundle(),
            new TestBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/config/config_test.yml');
    }
}
