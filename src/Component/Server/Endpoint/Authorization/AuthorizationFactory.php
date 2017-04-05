<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Endpoint\Authorization;

use OAuth2Framework\Component\Server\Endpoint\Authorization\ParameterChecker\ParameterCheckerManager;
use Psr\Http\Message\ServerRequestInterface;

final class AuthorizationFactory
{
    /**
     * @var AuthorizationRequestLoader
     */
    private $authorizationRequestLoader;

    /**
     * @var ParameterCheckerManager
     */
    private $parameterCheckerManager;

    /**
     * AuthorizationFactory constructor.
     *
     * @param AuthorizationRequestLoader $authorizationRequestLoader
     * @param ParameterCheckerManager    $parameterCheckerManager
     */
    public function __construct(AuthorizationRequestLoader $authorizationRequestLoader, ParameterCheckerManager $parameterCheckerManager)
    {
        $this->authorizationRequestLoader = $authorizationRequestLoader;
        $this->parameterCheckerManager = $parameterCheckerManager;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return Authorization
     */
    public function createAuthorizationFromRequest(ServerRequestInterface $request): Authorization
    {
        list($client, $queryParameters) = $this->authorizationRequestLoader->loadParametersFromRequest($request);
        $authorization = Authorization::create($client, $queryParameters);
        $authorization = $this->parameterCheckerManager->process($authorization);

        return $authorization;
    }
}
