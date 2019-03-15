<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Controller;

use Http\Message\ResponseFactory;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationEndpoint;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequestLoader;
use OAuth2Framework\Component\AuthorizationEndpoint\Consent\ConsentRepository;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterCheckerManager;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAccountDiscovery;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAuthenticationCheckerManager;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

final class AuthorizationEndpointController extends AuthorizationEndpoint
{
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(ResponseFactory $responseFactory, AuthorizationRequestLoader $authorizationRequestLoader, ParameterCheckerManager $parameterCheckerManager, UserAccountDiscovery $userManager, UserAuthenticationCheckerManager $userCheckerManager, SessionInterface $session, ?ConsentRepository $consentRepository, RouterInterface $router)
    {
        parent::__construct($responseFactory, $authorizationRequestLoader, $parameterCheckerManager, $userManager, $userCheckerManager, $session, $consentRepository);
        $this->router = $router;
    }

    protected function getRouteFor(string $action, string $authorizationId): string
    {
        return $this->router->generate($action, ['authorization_id' => $authorizationId]);
    }
}
