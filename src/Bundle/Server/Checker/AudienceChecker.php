<?php

declare(strict_types = 1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\Checker;

use Jose\Checker\AudienceChecker as BaseAudienceChecker;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class AudienceChecker extends BaseAudienceChecker
{
    /**
     * AudienceChecker constructor.
     *
     * @param RouterInterface $router
     * @param string          $routeName
     * @param array           $routeParameters
     */
    public function __construct(RouterInterface $router, string $routeName, array $routeParameters)
    {
        $audience = $router->generate($routeName, $routeParameters, UrlGeneratorInterface::ABSOLUTE_URL);
        parent::__construct($audience);
    }
}
