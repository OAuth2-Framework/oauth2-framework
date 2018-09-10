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

namespace OAuth2Framework\Component\Scope\Tests;

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;
use OAuth2Framework\Component\Scope\ScopeParameterChecker;
use OAuth2Framework\Component\Scope\ScopeRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group ScopeParameterChecker
 */
final class ScopeParameterCheckerTest extends TestCase
{
    /**
     * @inheritdoc}
     */
    protected function setUp()
    {
        if (!\class_exists(Authorization::class)) {
            static::markTestSkipped('The component "oauth2-framework/authorization-endpoint" is not installed.');
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestWithNoScopeParameterIsChecked()
    {
        $client = $this->prophesize(Client::class);
        $authorization = $this->prophesize(Authorization::class);
        $authorization->getClient()->willReturn($client->reveal());
        $authorization->hasQueryParam('scope')->willReturn(false)->shouldBeCalled();
        $authorization->setResponseParameter('scope', Argument::any())->shouldNotBeCalled();
        $this->getScopeParameterChecker()->check(
            $authorization->reveal()
        );
    }

    /**
     * @test
     */
    public function anAuthorizationRequestWithScopeParameterIsChecked()
    {
        $client = $this->prophesize(Client::class);
        $authorization = $this->prophesize(Authorization::class);
        $authorization->getClient()->willReturn($client->reveal());
        $authorization->hasQueryParam('scope')->willReturn(true)->shouldBeCalled();
        $authorization->getQueryParam('scope')->willReturn('scope1')->shouldBeCalled();
        $authorization->getMetadata()->willReturn(new DataBag([]))->shouldBeCalled();
        $authorization
            ->setResponseParameter('scope', Argument::any())
            ->shouldBeCalled()
            ->will(function(){});
        $this->getScopeParameterChecker()->check(
            $authorization->reveal()
        );
    }

    /**
     * @test
     */
    public function anAuthorizationRequestWithAnUnsupportedScopeParameterIsChecked()
    {
        $client = $this->prophesize(Client::class);
        $authorization = $this->prophesize(Authorization::class);
        $authorization->getClient()->willReturn($client->reveal());
        $authorization->hasQueryParam('scope')->willReturn(true)->shouldBeCalled();
        $authorization->getQueryParam('scope')->willReturn('invalid_scope')->shouldBeCalled();
        $authorization->setResponseParameter('scope', Argument::any())->shouldNotBeCalled();

        try {
            $this->getScopeParameterChecker()->check(
                $authorization->reveal()
            );
            static::fail('Expected exception nt thrown.');
        } catch (OAuth2AuthorizationException $e) {
            static::assertEquals('invalid_scope', $e->getMessage());
            static::assertEquals('An unsupported scope was requested. Available scopes for the client are scope1, scope2.', $e->getErrorDescription());
            static::assertEquals(400, $e->getCode());
        }
    }

    /**
     * @var ScopeParameterChecker|null
     */
    private $scopeParameterChecker = null;

    private function getScopeParameterChecker(): ScopeParameterChecker
    {
        if (null === $this->scopeParameterChecker) {
            $scopeRepository = $this->prophesize(ScopeRepository::class);
            $scopeRepository->all()->willReturn([
                'scope1',
                'scope2',
            ]);
            $scopePolicyManager = $this->prophesize(ScopePolicyManager::class);
            $scopePolicyManager->apply(Argument::any(), Argument::type(Client::class))->willReturnArgument(0);

            $this->scopeParameterChecker = new ScopeParameterChecker(
                $scopeRepository->reveal(),
                $scopePolicyManager->reveal()
            );
        }

        return $this->scopeParameterChecker;
    }
}
