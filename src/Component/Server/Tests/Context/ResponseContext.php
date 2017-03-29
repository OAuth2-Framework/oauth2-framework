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
use Behat\Gherkin\Node\PyStringNode;
use Psr\Http\Message\ResponseInterface;

final class ResponseContext implements Context
{
    /**
     * @var null|ResponseInterface
     */
    private $response = null;

    /**
     * @var null|array
     */
    private $error = null;

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

        $this->applicationContext = $environment->getContext(ApplicationContext::class);
    }

    /**
     * @param ResponseInterface $response
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
        if ($this->response->getBody()->isSeekable()) {
            $this->response->getBody()->rewind();
        }
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * @Then the response code is :code
     */
    public function theResponseCodeIs($code)
    {
        Assertion::eq((int) $code, $this->getResponse()->getStatusCode());
    }

    /**
     * @Then the response contains
     */
    public function theResponseContains(PyStringNode $response)
    {
        $this->rewind();
        Assertion::eq($response->getRaw(), (string) $this->getResponse()->getBody()->getContents());
    }

    /**
     * @Then the response contains an Id Token with the following claims for the client :client_id
     */
    public function theResponseContainsAnIdTokenWithTheFollowingClaims($client_id, PyStringNode $response)
    {
        $client = $this->applicationContext->getApplication()->getClientRepository()->find(\OAuth2Framework\Component\Server\Model\Client\ClientId::create($client_id));
        Assertion::isInstanceOf($client, \OAuth2Framework\Component\Server\Model\Client\Client::class);
        $claims = json_decode($response->getRaw(), true);
        $response = (string) $this->getResponse()->getBody()->getContents();
        $jwt = $this->applicationContext->getApplication()->getJwtLoader()->load($response);
        Assertion::isInstanceOf($jwt, \Jose\Object\JWSInterface::class);
        Assertion::true(empty(array_diff($claims, $jwt->getClaims())));
    }

    /**
     * @Then the response contains an error with code :code
     */
    public function theResponseContainsAnError($code)
    {
        Assertion::eq((int) $code, $this->getResponse()->getStatusCode());
        Assertion::greaterOrEqualThan($this->getResponse()->getStatusCode(), 400);
        if (401 === $this->getResponse()->getStatusCode()) {
            $headers = $this->getResponse()->getHeader('WWW-Authenticate');
            Assertion::greaterThan(count($headers), 0);
            $header = $headers[0];
            preg_match_all('/(\w+\*?)="((?:[^"\\\\]|\\\\.)+)"|([^\s,$]+)/', substr($header, strpos($header, ' ')), $matches, PREG_SET_ORDER);
            if (!is_array($matches)) {
                throw new \InvalidArgumentException('Unable to parse header');
            }
            foreach ($matches as $match) {
                $this->error[$match[1]] = $match[2];
            }
        } else {
            $this->rewind();
            $response = (string) $this->getResponse()->getBody()->getContents();
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
        Assertion::eq(302, $this->getResponse()->getStatusCode());
        $header = $this->getResponse()->getHeaders();
        Assertion::keyExists($header, 'Location');
        $location = $header['Location'];
        Assertion::true(!empty($location));
    }

    /**
     * @Then no access token creation event is thrown
     */
    public function noAccessTokenCreationEventIsThrown()
    {
        $events = $this->applicationContext->getApplication()->getAccessTokenCreatedEventHandler()->getEvents();
        Assertion::eq(0, count($events));
    }

    /**
     * @Then the response contains an access token
     */
    public function theResponseContainsAnAccessToken()
    {
        $this->rewind();
        $content = (string) $this->getResponse()->getBody()->getContents();
        $data = json_decode($content, true);
        Assertion::isArray($data);
        Assertion::keyExists($data, 'access_token');
    }

    /**
     * @Then an access token creation event is thrown
     */
    public function anAccessTokenCreationEventIsThrown()
    {
        $events = $this->applicationContext->getApplication()->getAccessTokenCreatedEventHandler()->getEvents();
        Assertion::greaterThan(count($events), 0);
    }

    /**
     * @Then a refresh token creation event is thrown
     */
    public function aRefreshTokenCreationEventIsThrown()
    {
        $events = $this->applicationContext->getApplication()->getRefreshTokenCreatedEventHandler()->getEvents();
        Assertion::greaterThan(count($events), 0);
    }

    /**
     * @Then the response contains something like :pattern
     */
    public function theResponseContainsSomethingLike($pattern)
    {
        $this->rewind();
        $content = (string) $this->getResponse()->getBody()->getContents();
        Assertion::regex($content, $pattern);
    }

    /**
     * @Then the content type of the response is :content_type
     */
    public function theContentTypeOfTheResponseIs($content_type)
    {
        Assertion::eq($content_type, implode('', $this->getResponse()->getHeader('Content-Type')));
    }

    private function rewind()
    {
        if (true === $this->getResponse()->getBody()->isSeekable()) {
            $this->getResponse()->getBody()->rewind();
        }
    }

    /**
     * @Then the redirection Uri starts with :pattern
     */
    public function theRedirectionUriStartsWith($pattern)
    {
        $locations = $this->getResponse()->getHeader('Location');
        foreach ($locations as $location) {
            if (mb_substr($location, 0, mb_strlen($pattern, '8bit'), '8bit') === $pattern) {
                return;
            }
        }
        throw new \InvalidArgumentException(sprintf('The location header is "%s".', implode(', ', $locations)));
    }

    /**
     * @Then the redirection Uri query should contain a parameter :parameter
     */
    public function theRedirectionUriQueryShouldContainAParameter($parameter)
    {
        $uriFactory = $this->applicationContext->getApplication()->getUriFactory();
        $locations = $this->getResponse()->getHeader('Location');
        foreach ($locations as $location) {
            $uri = $uriFactory->createUri($location);
            $query = $uri->getQuery();
            parse_str($query, $data);
            if (array_key_exists($parameter, $data)) {
                return;
            }
        }
        throw new \InvalidArgumentException(sprintf('The location header is "%s".', implode(', ', $locations)));
    }

    /**
     * @Then the redirection Uri query should contain a parameter :parameter with value :value
     */
    public function theRedirectionUriQueryShouldContainAParameterWithValue($parameter, $value)
    {
        $uriFactory = $this->applicationContext->getApplication()->getUriFactory();
        $locations = $this->getResponse()->getHeader('Location');
        foreach ($locations as $location) {
            $uri = $uriFactory->createUri($location);
            $query = $uri->getQuery();
            parse_str($query, $data);
            if (array_key_exists($parameter, $data)) {
                Assertion::eq($data[$parameter], $value, sprintf('The parameter \'%s\' value is \'%s\'.', $parameter, $data[$parameter]));

                return;
            }
        }
        throw new \InvalidArgumentException(sprintf('The location header is "%s".', implode(', ', $locations)));
    }

    /**
     * @Then the redirection Uri fragment should contain a parameter :parameter
     */
    public function theRedirectionUriFragmentShouldContainAParameter($parameter)
    {
        $uriFactory = $this->applicationContext->getApplication()->getUriFactory();
        $locations = $this->getResponse()->getHeader('Location');
        foreach ($locations as $location) {
            $uri = $uriFactory->createUri($location);
            $fragment = $uri->getFragment();
            parse_str($fragment, $data);
            if (array_key_exists($parameter, $data)) {
                return;
            }
        }
        throw new \InvalidArgumentException(sprintf('The location header is "%s".', implode(', ', $locations)));
    }

    /**
     * @Then the redirection Uri fragment should contain a parameter :parameter with value :value
     */
    public function theRedirectionUriFragmentShouldContainAParameterWithValue($parameter, $value)
    {
        $uriFactory = $this->applicationContext->getApplication()->getUriFactory();
        $locations = $this->getResponse()->getHeader('Location');
        foreach ($locations as $location) {
            $uri = $uriFactory->createUri($location);
            $fragment = $uri->getFragment();
            parse_str($fragment, $data);
            if (array_key_exists($parameter, $data)) {
                Assertion::eq($data[$parameter], $value, sprintf('The parameter \'%s\' value is \'%s\'.', $parameter, $data[$parameter]));

                return;
            }
        }
        throw new \InvalidArgumentException(sprintf('The location header is "%s".', implode(', ', $locations)));
    }

    /**
     * @Then the redirection ends with :pattern
     */
    public function theRedirectionEndsWith($pattern)
    {
        $locations = $this->getResponse()->getHeader('Location');
        foreach ($locations as $location) {
            if (mb_substr($location, -mb_strlen($pattern, '8bit'), null, '8bit') === $pattern) {
                return;
            }
        }
        throw new \InvalidArgumentException(sprintf('The location header is "%s".', implode(', ', $locations)));
    }

    /**
     * @Then the redirect query should contain parameter :parameter with value :value
     */
    public function theRedirectQueryShouldContainParameterWithValue($parameter, $value)
    {
        $uriFactory = $this->applicationContext->getApplication()->getUriFactory();
        $locations = $this->getResponse()->getHeader('Location');
        foreach ($locations as $location) {
            $uri = $uriFactory->createUri($location);
            $query = $uri->getQuery();
            parse_str($query, $data);
            if (array_key_exists($parameter, $data)) {
                Assertion::eq($data[$parameter], $value);

                return;
            }
        }
        throw new \InvalidArgumentException(sprintf('The location header is "%s".', implode(', ', $locations)));
    }

    /**
     * @Then I should be on the login screen
     */
    public function iShouldBeOnTheLoginScreen()
    {
        $this->rewind();
        $content = (string) $this->getResponse()->getBody()->getContents();

        Assertion::eq($content, 'You are redirected to the login page');
    }

    /**
     * @Then I should be on the consent screen
     */
    public function iShouldBeOnTheConsentScreen()
    {
        $this->rewind();
        $content = (string) $this->getResponse()->getBody()->getContents();

        Assertion::eq($content, 'You are on the consent screen');
    }
}
