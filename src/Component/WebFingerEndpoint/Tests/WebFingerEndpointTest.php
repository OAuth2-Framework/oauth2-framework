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

namespace OAuth2Framework\Component\WebFingerEndpoint\Tests;

use Http\Message\ResponseFactory;
use Nyholm\Psr7\Factory\HttplugFactory;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\Identifier;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\IdentifierResolver;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\IdentifierResolverManager;
use OAuth2Framework\Component\WebFingerEndpoint\Link;
use OAuth2Framework\Component\WebFingerEndpoint\ResourceDescriptor;
use OAuth2Framework\Component\WebFingerEndpoint\ResourceRepository;
use OAuth2Framework\Component\WebFingerEndpoint\WebFingerEndpoint;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @group WebFingerEndpoint
 */
final class WebFingerEndpointTest extends TestCase
{
    /**
     * @test
     */
    public function theEndpointCannotFindTheResourceParameter()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->willReturn([
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
            'resource' => '=Foo.Bar',
        ]);
        $repository = $this->prophesize(ResourceRepository::class);
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $identifierResolverManager = new IdentifierResolverManager();
        $endpoint = new WebFingerEndpoint(
            $this->getResponseFactory(),
            $repository->reveal(),
            $identifierResolverManager
        );

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        $response->getBody()->rewind();
        static::assertEquals('{"error":"invalid_request","error_description":"The resource identified with \"=Foo.Bar\" does not exist or is not supported by this server."}', $response->getBody()->getContents());
        static::assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function theEndpointDoesNotSupportXri()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->willReturn([
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
            'resource' => '@foo',
        ]);
        $repository = $this->prophesize(ResourceRepository::class);
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $identifierResolverManager = new IdentifierResolverManager();
        $endpoint = new WebFingerEndpoint(
            $this->getResponseFactory(),
            $repository->reveal(),
            $identifierResolverManager
        );

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        $response->getBody()->rewind();
        static::assertEquals('{"error":"invalid_request","error_description":"The resource identified with \"@foo\" does not exist or is not supported by this server."}', $response->getBody()->getContents());
        static::assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function theEndpointDoesNotSupportResourceFromOtherHosts()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->willReturn([
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
            'resource' => 'hello@me.com',
        ]);
        $repository = $this->prophesize(ResourceRepository::class);
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $identifierResolverManager = new IdentifierResolverManager();
        $endpoint = new WebFingerEndpoint(
            $this->getResponseFactory(),
            $repository->reveal(),
            $identifierResolverManager
        );

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        $response->getBody()->rewind();
        static::assertEquals('{"error":"invalid_request","error_description":"The resource identified with \"hello@me.com\" does not exist or is not supported by this server."}', $response->getBody()->getContents());
        static::assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function theResourceIsNotKnown()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->willReturn([
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
            'resource' => 'bad@www.foo.bar:8000',
        ]);
        $repository = $this->prophesize(ResourceRepository::class);
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $identifierResolverManager = new IdentifierResolverManager();
        $endpoint = new WebFingerEndpoint(
            $this->getResponseFactory(),
            $repository->reveal(),
            $identifierResolverManager
        );

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        $response->getBody()->rewind();
        static::assertEquals('{"error":"invalid_request","error_description":"The resource identified with \"bad@www.foo.bar:8000\" does not exist or is not supported by this server."}', $response->getBody()->getContents());
        static::assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function theResourceIsAValidResourceFromEmail()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->willReturn([
            'resource' => 'hello@www.foo.bar:8000',
        ]);
        $repository = $this->prophesize(ResourceRepository::class);
        $repository->find(Argument::type('string'), Argument::type(Identifier::class))->willReturn(new ResourceDescriptor(
            'hello@www.foo.bar:8000',
            [],
            [],
            [new Link('http://openid.net/specs/connect/1.0/issuer', null, 'https://my.server.com/hello', [], [])]
        ));
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $resolver = $this->prophesize(IdentifierResolver::class);
        $resolver->supports('hello@www.foo.bar:8000')->willReturn(true);
        $resolver->resolve('hello@www.foo.bar:8000')->willReturn(new Identifier('hello', 'www.foo.bar', 8000));
        $identifierResolverManager = new IdentifierResolverManager();
        $identifierResolverManager->add($resolver->reveal());
        $endpoint = new WebFingerEndpoint(
            $this->getResponseFactory(),
            $repository->reveal(),
            $identifierResolverManager
        );

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        $response->getBody()->rewind();
        static::assertEquals('{"subject":"hello@www.foo.bar:8000","links":[{"rel":"http://openid.net/specs/connect/1.0/issuer","href":"https://my.server.com/hello"}]}', $response->getBody()->getContents());
        static::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function theResourceIsAValidResourceFromAccount()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->willReturn([
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
            'resource' => 'acct:hello%40you@www.foo.bar:8000',
        ]);
        $repository = $this->prophesize(ResourceRepository::class);
        $repository->find(Argument::type('string'), Argument::type(Identifier::class))->willReturn(new ResourceDescriptor(
            'acct:hello%40you@www.foo.bar:8000',
            [],
            [],
            [new Link('http://openid.net/specs/connect/1.0/issuer', null, 'https://my.server.com/hello', [], [])]
        ));
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $resolver = $this->prophesize(IdentifierResolver::class);
        $resolver->supports('acct:hello%40you@www.foo.bar:8000')->willReturn(true);
        $resolver->resolve('acct:hello%40you@www.foo.bar:8000')->willReturn(new Identifier('hello', 'www.foo.bar', 8000));
        $identifierResolverManager = new IdentifierResolverManager();
        $identifierResolverManager->add($resolver->reveal());
        $endpoint = new WebFingerEndpoint(
            $this->getResponseFactory(),
            $repository->reveal(),
            $identifierResolverManager
        );

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        $response->getBody()->rewind();
        static::assertEquals('{"subject":"acct:hello%40you@www.foo.bar:8000","links":[{"rel":"http://openid.net/specs/connect/1.0/issuer","href":"https://my.server.com/hello"}]}', $response->getBody()->getContents());
        static::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function theResourceIsAValidResourceFromUri()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->willReturn([
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
            'resource' => 'https://www.foo.bar:8000/+hello',
        ]);
        $repository = $this->prophesize(ResourceRepository::class);
        $repository->find(Argument::type('string'), Argument::type(Identifier::class))->willReturn(new ResourceDescriptor(
            'https://www.foo.bar:8000/+hello',
            [],
            [],
            [new Link('http://openid.net/specs/connect/1.0/issuer', null, 'https://my.server.com/hello', [], [])]
        ));
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $resolver = $this->prophesize(IdentifierResolver::class);
        $resolver->supports('https://www.foo.bar:8000/+hello')->willReturn(true);
        $resolver->resolve('https://www.foo.bar:8000/+hello')->willReturn(new Identifier('hello', 'www.foo.bar', 8000));
        $identifierResolverManager = new IdentifierResolverManager();
        $identifierResolverManager->add($resolver->reveal());
        $endpoint = new WebFingerEndpoint(
            $this->getResponseFactory(),
            $repository->reveal(),
            $identifierResolverManager
        );

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        $response->getBody()->rewind();
        static::assertEquals('{"subject":"https://www.foo.bar:8000/+hello","links":[{"rel":"http://openid.net/specs/connect/1.0/issuer","href":"https://my.server.com/hello"}]}', $response->getBody()->getContents());
        static::assertEquals(200, $response->getStatusCode());
    }

    private function getResponseFactory(): ResponseFactory
    {
        return new HttplugFactory();
    }
}
