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

namespace OAuth2Framework\Component\ClientRule\Tests;

use OAuth2Framework\Component\ClientRule;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;

/**
 * @group Tests
 */
final class RedirectionUriRuleTest extends TestCase
{
    /**
     * @test
     */
    public function noResponseTypeIsUsed()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => ['http://foo.com/callback'],
        ]);
        $rule = new ClientRule\RedirectionUriRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
        static::assertTrue($validatedParameters->has('redirect_uris'));
        static::assertEquals([], $validatedParameters->get('redirect_uris'));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Non-confidential clients must register at least one redirect URI.
     */
    public function aLeastOneRedirectUriMustBeSetForNonConfidentialClients()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new ClientRule\RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Confidential clients must register at least one redirect URI when using the "token" response type.
     */
    public function confidentialClientsUsingTokenResponseTypeMustRegisterAtLeastOneRedirectUri()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['token', 'code'],
            'token_endpoint_auth_method' => 'private_key_jwt',
        ]);
        $rule = new ClientRule\RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "redirect_uris" must be a list of URI or URN.
     */
    public function theRedirectUrisParameterMustBeAnArray()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => 'hello',
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new ClientRule\RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "redirect_uris" must be a list of URI or URN.
     */
    public function theRedirectUrisParameterMustBeAnArrayOfString()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => [123],
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new ClientRule\RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "redirect_uris" must be a list of URI or URN.
     */
    public function theRedirectUrisParameterMustBeAnArrayOfUris()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => ['hello'],
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new ClientRule\RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "redirect_uris" must only contain URIs without fragment.
     */
    public function theRedirectUrisMustNotContainAnyFragmentParameter()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => ['http://foo.com/#test=bad'],
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new ClientRule\RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The host "localhost" is not allowed for web applications that use the Implicit Grant Type.
     */
    public function theLocalhostHostIsNotAllowedWhenTheImplicitGrantTypeIsUsed()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => ['http://localhost/'],
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new ClientRule\RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "redirect_uris" must only contain URIs with the HTTPS scheme for web applications that use the Implicit Grant Type.
     */
    public function theSchemeMustBeHttpsWhenTheImplicitGrantTypeIsUsed()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => ['http://foo.com/'],
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new ClientRule\RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The URI listed in the "redirect_uris" parameter must not contain any path traversal.
     */
    public function theRedirectUrisMustNotHavePathTraversal()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => ['https://foo.com/bar/../bad'],
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new ClientRule\RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     */
    public function theUrisAreValidatedWithTheImplicitGrantType()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => ['https://foo.com/'],
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new ClientRule\RedirectionUriRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
        static::assertTrue($validatedParameters->has('redirect_uris'));
        static::assertEquals(['https://foo.com/'], $validatedParameters->get('redirect_uris'));
    }

    /**
     * @test
     */
    public function theUrisAreValidatedWithOtherGrantTypes()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => ['http://localhost/'],
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['id_token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new ClientRule\RedirectionUriRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
        static::assertTrue($validatedParameters->has('redirect_uris'));
        static::assertEquals(['http://localhost/'], $validatedParameters->get('redirect_uris'));
    }

    /**
     * @test
     */
    public function theUrnsAreAllowed()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => ['urn:ietf:wg:oauth:2.0:oob', 'urn:ietf:wg:oauth:2.0:oob:auto'],
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['id_token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new ClientRule\RedirectionUriRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
        static::assertTrue($validatedParameters->has('redirect_uris'));
        static::assertEquals(['urn:ietf:wg:oauth:2.0:oob', 'urn:ietf:wg:oauth:2.0:oob:auto'], $validatedParameters->get('redirect_uris'));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "redirect_uris" must be a list of URI or URN.
     */
    public function theUrnsAreNotValid()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => ['urn:---------------'],
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['id_token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new ClientRule\RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    private function getCallable(): callable
    {
        return function (ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters): DataBag {
            return $validatedParameters;
        };
    }
}
