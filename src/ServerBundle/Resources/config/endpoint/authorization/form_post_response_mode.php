<?php

declare(strict_types=1);

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FormPostResponseMode;
use OAuth2Framework\ServerBundle\Service\TwigFormPostResponseRenderer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(TwigFormPostResponseRenderer::class)
        ->args([service('twig'), '%oauth2_server.endpoint.authorization.response_mode.form_post.template%'])
    ;

    $container->set(FormPostResponseMode::class)
        ->args([service(TwigFormPostResponseRenderer::class)])
    ;
};
