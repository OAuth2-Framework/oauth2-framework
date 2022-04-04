<?php

declare(strict_types=1);

use OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport\AddressScopeSupport;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport\EmailScopeSupport;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport\PhoneScopeSupport;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport\ProfileScopeSupport;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(AddressScopeSupport::class);
    $container->set(EmailScopeSupport::class);
    $container->set(PhoneScopeSupport::class);
    $container->set(ProfileScopeSupport::class);
};
