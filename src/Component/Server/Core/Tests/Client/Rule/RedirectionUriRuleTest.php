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

namespace OAuth2Framework\Component\Server\Core\Tests\Client\Rule;

use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\Client\Rule;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;

/**
 * @group Rule
 */
final class RedirectionUriRuleTest extends TestCase
{
    /**
     * @test
     */
    public function noResponseTypeIsUsed()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'redirect_uris' => ['http://foo.com/callback'],
        ]);
        $rule = new Rule\RedirectionUriRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
        self::assertFalse($validatedParameters->has('redirect_uris'));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "redirect_uris" is mandatory when response types are used.
     */
    public function noRedirectUrisParameterFound()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
        ]);
        $validatedParameters = DataBag::create(['response_types' => ['token', 'code']]);
        $rule = new Rule\RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "redirect_uris" must be a list of URI or URN.
     */
    public function theRedirectUrisParameterMustBeAnArray()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'redirect_uris' => 'hello',
        ]);
        $validatedParameters = DataBag::create(['response_types' => ['token', 'code']]);
        $rule = new Rule\RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "redirect_uris" must be a list of URI or URN.
     */
    public function theRedirectUrisParameterMustBeAnArrayOfString()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'redirect_uris' => [123],
        ]);
        $validatedParameters = DataBag::create(['response_types' => ['token', 'code']]);
        $rule = new Rule\RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "redirect_uris" must be a list of URI or URN.
     */
    public function theRedirectUrisParameterMustBeAnArrayOfUris()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'redirect_uris' => ['hello'],
        ]);
        $validatedParameters = DataBag::create(['response_types' => ['token', 'code']]);
        $rule = new Rule\RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "redirect_uris" must only contain URIs without fragment.
     */
    public function theRedirectUrisMustNotContainAnyFragmentParameter()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'redirect_uris' => ['http://foo.com/#test=bad'],
        ]);
        $validatedParameters = DataBag::create(['response_types' => ['token', 'code']]);
        $rule = new Rule\RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The host "localhost" is not allowed for web applications that use the Implicit Grant Type.
     */
    public function theLocalhostHostIsNotAllowedWhenTheImplicitGrantTypeIsUsed()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'redirect_uris' => ['http://localhost/'],
        ]);
        $validatedParameters = DataBag::create(['response_types' => ['token', 'code']]);
        $rule = new Rule\RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "redirect_uris" must only contain URIs with the HTTPS scheme for web applications that use the Implicit Grant Type.
     */
    public function theSchemeMustBeHttpsWhenTheImplicitGrantTypeIsUsed()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'redirect_uris' => ['http://foo.com/'],
        ]);
        $validatedParameters = DataBag::create(['response_types' => ['token', 'code']]);
        $rule = new Rule\RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The URI listed in the "redirect_uris" parameter must not contain any path traversal.
     */
    public function theRedirectUrisMustNotHavePathTraversal()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'redirect_uris' => ['https://foo.com/bar/../bad'],
        ]);
        $validatedParameters = DataBag::create(['response_types' => ['token', 'code']]);
        $rule = new Rule\RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     */
    public function theUrisAreValidatedWithTheImplicitGrantType()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'redirect_uris' => ['https://foo.com/'],
        ]);
        $validatedParameters = DataBag::create(['response_types' => ['token', 'code']]);
        $rule = new Rule\RedirectionUriRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
        self::assertTrue($validatedParameters->has('redirect_uris'));
        self::assertEquals(['https://foo.com/'], $validatedParameters->get('redirect_uris'));
    }

    /**
     * @test
     */
    public function theUrisAreValidatedWithOtherGrantTypes()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'redirect_uris' => ['http://localhost/'],
        ]);
        $validatedParameters = DataBag::create(['response_types' => ['id_token', 'code']]);
        $rule = new Rule\RedirectionUriRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
        self::assertTrue($validatedParameters->has('redirect_uris'));
        self::assertEquals(['http://localhost/'], $validatedParameters->get('redirect_uris'));
    }

    /**
     * @test
     */
    public function theUrnsAreAllowed()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'redirect_uris' => ['urn:ietf:wg:oauth:2.0:oob', 'urn:ietf:wg:oauth:2.0:oob:auto'],
        ]);
        $validatedParameters = DataBag::create(['response_types' => ['id_token', 'code']]);
        $rule = new Rule\RedirectionUriRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
        self::assertTrue($validatedParameters->has('redirect_uris'));
        self::assertEquals(['urn:ietf:wg:oauth:2.0:oob', 'urn:ietf:wg:oauth:2.0:oob:auto'], $validatedParameters->get('redirect_uris'));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "redirect_uris" must be a list of URI or URN.
     */
    public function theUrnsAreNotValid()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'redirect_uris' => ['urn:---------------'],
        ]);
        $validatedParameters = DataBag::create(['response_types' => ['id_token', 'code']]);
        $rule = new Rule\RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @return callable
     */
    private function getCallable(): callable
    {
        return function (ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters): DataBag {
            return $validatedParameters;
        };
    }
}
