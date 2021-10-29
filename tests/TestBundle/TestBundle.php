<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle;

use OAuth2Framework\Tests\TestBundle\DependencyInjection\TestExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class TestBundle extends Bundle
{
    public function getContainerExtension(): ExtensionInterface
    {
        return new TestExtension();
    }
}
