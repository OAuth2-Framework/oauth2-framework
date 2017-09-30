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

use Assert\Assertion;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Http\Factory\Diactoros\UriFactory;
use OAuth2Framework\Bundle\Server\Tests\TestBundle\Listener;
use Symfony\Component\VarDumper\VarDumper;

final class ResponseContext implements Context
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
     * @var null|array
     */
    private $error = null;

    /**
     * @Then the response code is :code
     */
    public function theResponseCodeIs($code)
    {
        if (500 === $this->minkContext->getSession()->getStatusCode()) {
            VarDumper::dump(substr($this->minkContext->getSession()->getPage()->getContent(), 0, 5000));
        }
        Assertion::eq((int) $code, $this->minkContext->getSession()->getStatusCode());
    }

    /**
     * @Then the response contains
     */
    public function theResponseContains(PyStringNode $response)
    {
        Assertion::eq($response->getRaw(), $this->minkContext->getSession()->getPage()->getContent());
    }

    /**
     * @Then the response contains an error with code :code
     */
    public function theResponseContainsAnError($code)
    {
        Assertion::eq((int) $code, $this->minkContext->getSession()->getStatusCode());
        Assertion::greaterOrEqualThan($this->minkContext->getSession()->getStatusCode(), 400);
        if (401 === $this->minkContext->getSession()->getStatusCode()) {
            $header = $this->minkContext->getSession()->getResponseHeader('WWW-Authenticate');
            Assertion::notNull($header);
            preg_match_all('/(\w+\*?)="((?:[^"\\\\]|\\\\.)+)"|([^\s,$]+)/', substr($header, strpos($header, ' ')), $matches, PREG_SET_ORDER);
            if (!is_array($matches)) {
                throw new \InvalidArgumentException('Unable to parse header');
            }
            foreach ($matches as $match) {
                $this->error[$match[1]] = $match[2];
            }
        } else {
            $response = $this->minkContext->getSession()->getPage()->getContent();
            $json = json_decode($response, true);
            Assertion::isArray($json);
            Assertion::keyExists($json, 'error');
            $this->error = $json;
        }
    }

    /**
     * @Then the error is :error
     *
     * @param string $error
     */
    public function theErrorIs($error)
    {
        Assertion::notNull($this->error);
        Assertion::keyExists($this->error, 'error');
        Assertion::eq($error, $this->error['error']);
    }

    /**
     * @Then the error description is :errorDescription
     *
     * @param string $errorDescription
     */
    public function theErrorDescriptionIs($errorDescription)
    {
        Assertion::notNull($this->error);
        Assertion::keyExists($this->error, 'error_description');
        Assertion::eq($errorDescription, $this->error['error_description']);
    }

    /**
     * @Then the client should be redirected
     */
    public function theClientShouldBeRedirected()
    {
        Assertion::eq(302, $this->minkContext->getSession()->getStatusCode());
        $location = $this->minkContext->getSession()->getResponseHeader('Location');
        Assertion::notNull($location);
        Assertion::true(!empty($location));
    }

    /**
     * @Then no access token creation event is thrown
     */
    public function noAccessTokenCreationEventIsThrown()
    {
        $events = $this->getContainer()->get(Listener\AccessTokenCreatedListener::class)->getEvents();
        Assertion::eq(0, count($events));
    }

    /**
     * @Then the response contains an access token
     */
    public function theResponseContainsAnAccessToken()
    {
        $content = $this->minkContext->getSession()->getPage()->getContent();
        $data = json_decode($content, true);
        Assertion::isArray($data);
        Assertion::keyExists($data, 'access_token');
    }

    /**
     * @Then an access token creation event is thrown
     */
    public function anAccessTokenCreationEventIsThrown()
    {
        $events = $this->getContainer()->get(Listener\AccessTokenCreatedListener::class)->getEvents();
        Assertion::greaterThan(count($events), 0);
    }

    /**
     * @Then a refresh token creation event is thrown
     */
    public function aRefreshTokenCreationEventIsThrown()
    {
        $events = $this->getContainer()->get(Listener\RefreshTokenCreatedListener::class)->getEvents();
        Assertion::greaterThan(count($events), 0);
    }

    /**
     * @Then the response contains something like :pattern
     */
    public function theResponseContainsSomethingLike($pattern)
    {
        $content = $this->minkContext->getSession()->getPage()->getContent();
        Assertion::regex($content, $pattern);
    }

    /**
     * @Then the content type of the response is :content_type
     */
    public function theContentTypeOfTheResponseIs($content_type)
    {
        Assertion::eq($content_type, $this->minkContext->getSession()->getResponseHeader('Content-Type'));
    }

    /**
     * @Then the redirection Uri starts with :pattern
     */
    public function theRedirectionUriStartsWith($pattern)
    {
        $location = $this->minkContext->getSession()->getResponseHeader('Location');
        if (mb_substr($location, 0, mb_strlen($pattern, '8bit'), '8bit') === $pattern) {
            return;
        }

        throw new \InvalidArgumentException(sprintf('The location header is "%s".', $location));
    }

    /**
     * @Then the redirection Uri query should contain a parameter :parameter
     */
    public function theRedirectionUriQueryShouldContainAParameter($parameter)
    {
        $uriFactory = new UriFactory();
        $location = $this->minkContext->getSession()->getResponseHeader('Location');
        $uri = $uriFactory->createUri($location);
        $query = $uri->getQuery();
        parse_str($query, $data);
        if (array_key_exists($parameter, $data)) {
            return;
        }

        throw new \InvalidArgumentException(sprintf('The location header is "%s".', $location));
    }

    /**
     * @Then the redirection Uri query should contain a parameter :parameter with value :value
     */
    public function theRedirectionUriQueryShouldContainAParameterWithValue($parameter, $value)
    {
        $uriFactory = new UriFactory();
        $location = $this->minkContext->getSession()->getResponseHeader('Location');
        $uri = $uriFactory->createUri($location);
        $query = $uri->getQuery();
        parse_str($query, $data);
        if (array_key_exists($parameter, $data)) {
            Assertion::eq($data[$parameter], $value, sprintf('The parameter \'%s\' value is \'%s\'.', $parameter, $data[$parameter]));

            return;
        }

        throw new \InvalidArgumentException(sprintf('The location header is "%s".', $location));
    }

    /**
     * @Then the redirection Uri fragment should contain a parameter :parameter
     */
    public function theRedirectionUriFragmentShouldContainAParameter($parameter)
    {
        $uriFactory = new UriFactory();
        $location = $this->minkContext->getSession()->getResponseHeader('Location');
        $uri = $uriFactory->createUri($location);
        $fragment = $uri->getFragment();
        parse_str($fragment, $data);
        if (array_key_exists($parameter, $data)) {
            return;
        }

        throw new \InvalidArgumentException(sprintf('The location header is "%s".', $location));
    }

    /**
     * @Then the redirection Uri fragment should contain a parameter :parameter with value :value
     */
    public function theRedirectionUriFragmentShouldContainAParameterWithValue($parameter, $value)
    {
        $uriFactory = new UriFactory();
        $location = $this->minkContext->getSession()->getResponseHeader('Location');
        $uri = $uriFactory->createUri($location);
        $fragment = $uri->getFragment();
        parse_str($fragment, $data);
        if (array_key_exists($parameter, $data)) {
            Assertion::eq($data[$parameter], $value, sprintf('The parameter \'%s\' value is \'%s\'.', $parameter, $data[$parameter]));

            return;
        }

        throw new \InvalidArgumentException(sprintf('The location header is "%s".', $location));
    }

    /**
     * @Then the redirection ends with :pattern
     */
    public function theRedirectionEndsWith($pattern)
    {
        $location = $this->minkContext->getSession()->getResponseHeader('Location');
        if (mb_substr($location, -mb_strlen($pattern, '8bit'), null, '8bit') === $pattern) {
            return;
        }

        throw new \InvalidArgumentException(sprintf('The location header is "%s".', $location));
    }

    /**
     * @Then the redirect query should contain parameter :parameter with value :value
     */
    public function theRedirectQueryShouldContainParameterWithValue($parameter, $value)
    {
        $uriFactory = new UriFactory();
        $location = $this->minkContext->getSession()->getResponseHeader('Location');
        $uri = $uriFactory->createUri($location);
        $query = $uri->getQuery();
        parse_str($query, $data);
        if (array_key_exists($parameter, $data)) {
            Assertion::eq($data[$parameter], $value);

            return;
        }

        throw new \InvalidArgumentException(sprintf('The location header is "%s".', $location));
    }

    /**
     * @Then I should be on the login screen
     */
    public function iShouldBeOnTheLoginScreen()
    {
        $location = $this->minkContext->getSession()->getResponseHeader('Location');
        Assertion::eq($location, 'https://oauth2.test/login');
    }

    /**
     * @Then I should be on the consent screen
     */
    public function iShouldBeOnTheConsentScreen()
    {
        Assertion::eq($this->minkContext->getSession()->getCurrentUrl(), 'https://oauth2.test/authorize');
    }

    /**
     * @Then a cookie is in the response header
     */
    public function aCookieIsInTheResponseHeader()
    {
        $headers = $this->minkContext->getSession()->getResponseHeaders();
        Assertion::keyExists($headers, 'set-cookie');
    }
}
