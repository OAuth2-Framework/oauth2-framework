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

use Assert\Assertion;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Psr\Http\Message\ServerRequestInterface;

final class ResponseTypeContext implements Context
{
    /**
     * @var ServerRequestInterface|null
     */
    private $authorizationRequest;

    /**
     * @var ResponseContext
     */
    private $responseContext;

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
    }

    /**
     * @Given The user :user is logged in and fully authenticated
     */
    public function theUserIsLoggedInAndFullyAuthenticated($user)
    {
        $userAccount = $this->applicationContext->getApplication()->getUserAccountRepository()->findOneByUsername($user);
        Assertion::notNull($userAccount, sprintf('Unable to find a user with username "%s"', $user));
        $this->applicationContext->getApplication()->getSecurityLayer()->setUserAccount($userAccount, true);
    }

    /**
     * @Given The user :user is logged in and but not fully authenticated
     */
    public function theUserIsLoggedInAndButNotFullyAuthenticated($user)
    {
        $userAccount = $this->applicationContext->getApplication()->getUserAccountRepository()->findOneByUsername($user);
        Assertion::notNull($userAccount, sprintf('Unable to find a user with username "%s"', $user));
        $this->applicationContext->getApplication()->getSecurityLayer()->setUserAccount($userAccount, false);
    }

    /**
     * @Given no user is logged in
     */
    public function noUserIsLoggedIn()
    {
        $this->applicationContext->getApplication()->getSecurityLayer()->setUserAccount(null, null);
    }

    /**
     * @When the Resource Owner accepts the authorization request
     */
    public function theResourceOwnerAcceptsTheAuthorizationRequest()
    {
        $request = $this->getAuthorizationRequest();
        $this->unsetAuthorizationRequest();
        $this->applicationContext->getApplication()->getAuthorizationEndpoint()->setAuthorized(true);
        $this->responseContext->setResponse($this->applicationContext->getApplication()->getAuthorizationEndpointPipe()->dispatch($request));
    }

    /**
     * @When the Resource Owner rejects the authorization request
     */
    public function theResourceOwnerRejectsTheAuthorizationRequest()
    {
        $request = $this->getAuthorizationRequest();
        $this->unsetAuthorizationRequest();
        $this->applicationContext->getApplication()->getAuthorizationEndpoint()->setAuthorized(false);
        $this->responseContext->setResponse($this->applicationContext->getApplication()->getAuthorizationEndpointPipe()->dispatch($request));
    }

    /**
     * @param ServerRequestInterface $authorizationRequest
     */
    public function setAuthorizationRequest(ServerRequestInterface $authorizationRequest)
    {
        $this->authorizationRequest = $authorizationRequest;
    }

    public function unsetAuthorizationRequest()
    {
        $this->authorizationRequest = null;
    }

    /**
     * @return null|ServerRequestInterface
     */
    public function getAuthorizationRequest()
    {
        return $this->authorizationRequest;
    }
}
