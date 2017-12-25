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

namespace OAuth2Framework\Bundle\Server\Tests\Context;

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */


use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class ResponseTypeContext implements Context
{
    use KernelDictionary;

    /**
     * @var MinkContext
     */
    private $minkContext;

    /**
     * @BeforeScenario
     *
     * @param BeforeScenarioScope $scope
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        $this->minkContext = $environment->getContext(MinkContext::class);
    }

    /**
     * @Given The user :user is logged in and fully authenticated
     */
    public function theUserIsLoggedInAndFullyAuthenticated($user)
    {
        $session = $this->getContainer()->get('session');
        $user = $this->getContainer()->get('oauth2_server.user_account.repository')->findOneByUsername($user);
        Assertion::notNull($user, 'Unknown user');
        $token = new UsernamePasswordToken($user, 'secret', 'main', $user->getRoles());
        $session->set('_security_main', serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->minkContext->getSession()->getDriver()->getClient()->getCookieJar()->set($cookie);
    }

    /**
     * @Given The user :user is logged in and but not fully authenticated
     */
    public function theUserIsLoggedInAndButNotFullyAuthenticated($user)
    {
        $session = $this->getContainer()->get('session');
        $user = $this->getContainer()->get('oauth2_server.user_account.repository')->findOneByUsername($user);
        Assertion::notNull($user, 'Unknown user');
        $token = new RememberMeToken($user, 'main', 'secret');
        $session->set('_security_main', serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->minkContext->getSession()->getDriver()->getClient()->getCookieJar()->set($cookie);
    }

    /**
     * @Given no user is logged in
     */
    public function noUserIsLoggedIn()
    {
        //Nothing to do
    }

    /**
     * @When the Resource Owner accepts the authorization request
     */
    public function theResourceOwnerAcceptsTheAuthorizationRequest()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->followRedirects(false);
        $this->minkContext->pressButton('oauth2_server_authorization_form_accept');
    }

    /**
     * @When the Resource Owner rejects the authorization request
     */
    public function theResourceOwnerRejectsTheAuthorizationRequest()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->followRedirects(false);
        $this->minkContext->pressButton('oauth2_server_authorization_form_reject');
    }
}
