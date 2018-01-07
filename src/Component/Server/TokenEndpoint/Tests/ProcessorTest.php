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

namespace OAuth2Framework\Component\Server\TokenEndpoint\Tests;

use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantType;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Component\Server\TokenEndpoint\Processor\ProcessorManager;
use OAuth2Framework\Component\Server\TokenEndpoint\Processor\ScopeProcessor;
use OAuth2Framework\Component\Server\TokenEndpoint\Processor\TokenTypeProcessor;
use OAuth2Framework\Component\Server\TokenType\TokenType;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group TokenEndpoint
 * @group Processor
 */
final class ProcessorTest extends TestCase
{
    /**
     * @test
     */
    public function theProcessorManagerCanHandleCalls()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            null
        );
        $client->eraseMessages();
        $tokenType = $this->prophesize(TokenType::class);
        $tokenType->name()->willReturn('foo');
        $tokenType->getInformation()->willReturn([
            'token_type' => 'foo',
            'foo' => 'bar',
        ]);
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('token_type')->willReturn($tokenType->reveal());

        $grantTypeData = GrantTypeData::create($client);
        $grantType = $this->prophesize(GrantType::class);
        $grantType->grant(Argument::type(ServerRequestInterface::class), Argument::type(GrantTypeData::class))->will(function ($args) {
            return $args[1];
        });

        $result = $this->getProcessorManager()->handle($request->reveal(), $grantTypeData, $grantType->reveal());

        self::assertNotSame($grantTypeData, $result);
        self::assertEquals(['token_type' => 'foo', 'foo' => 'bar'], $result->getParameters()->all());
    }

    /**
     * @var null|ProcessorManager
     */
    private $processorManager;

    /**
     * @return ProcessorManager
     */
    private function getProcessorManager(): ProcessorManager
    {
        if (null === $this->processorManager) {
            $this->processorManager = new ProcessorManager();
            $this->processorManager
                ->add(new TokenTypeProcessor())
                //->add(new ScopeProcessor())
            ;
        }

        return $this->processorManager;
    }
}
