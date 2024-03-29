<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\OpenIdConnect\Compiler;

use OAuth2Framework\Component\OpenIdConnect\UserInfo\UserInfo;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class UserInfoPairwiseSubjectCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasAlias('oauth2_server.openid_connect.pairwise.service')) {
            return;
        }

        $definition = $container->getDefinition(UserInfo::class);
        $definition->addMethodCall(
            'enablePairwiseSubject',
            [new Reference('oauth2_server.openid_connect.pairwise.service')]
        );
    }
}
