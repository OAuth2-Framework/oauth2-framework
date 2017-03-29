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

namespace OAuth2Framework\Component\Server\Tests\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

final class TokenResponseTypeContext implements Context
{
    /**
     * @var ResponseContext
     */
    private $responseContext;

    /**
     * @var ResponseTypeContext
     */
    private $responseTypeContext;

    /**
     * @var ApplicationContext
     */
    private $applicationContext;

    /**
     * @BeforeScenario
     *
     * @param BeforeScenarioScope $scope
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        $this->responseContext = $environment->getContext(ResponseContext::class);
        $this->applicationContext = $environment->getContext(ApplicationContext::class);
        $this->responseTypeContext = $environment->getContext(ResponseTypeContext::class);
    }

    /**
     * @Given A client sends a authorization requests with the Token Response Type
     */
    public function aClientSendsAAuthorizationRequestsWithTheTokenResponseType()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'client_id' => 'client1',
            'redirect_uri' => 'https://example.com/redirection/callback',
            'response_type' => 'token',
            'state' => '0123456789',
        ]);

        $this->responseTypeContext->setAuthorizationRequest($request);
        $this->applicationContext->getApplication()->getAuthorizationEndpointPipe()->dispatch($request);
    }
}
