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

namespace OAuth2Framework\Component\IssuerDiscoveryEndpoint\Tests;

use Http\Factory\Diactoros\ResponseFactory;
use Http\Factory\Diactoros\UriFactory;
use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\Factory\UriFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use OAuth2Framework\Component\IssuerDiscoveryEndpoint\IssuerDiscoveryEndpoint;
use OAuth2Framework\Component\IssuerDiscoveryEndpoint\Resource;
use OAuth2Framework\Component\IssuerDiscoveryEndpoint\ResourceId;
use OAuth2Framework\Component\IssuerDiscoveryEndpoint\ResourceRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group IssuerDiscoveryEndpoint
 */
class IssuerDiscoveryEndpointTest extends TestCase
{
    /**
     * @test
     */
    public function theEndpointCannotFindTheRelParameter()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $repository = $this->prophesize(ResourceRepository::class);
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $endpoint = new IssuerDiscoveryEndpoint(
            $repository->reveal(),
            $this->getResponseFactory(),
            $this->getUriFactory(),
            'www.foo.bar:8000'
        );

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        $response->getBody()->rewind();
        self::assertEquals('{"error":"invalid_request","error_description":"The parameter \"rel\" is mandatory."}', $response->getBody()->getContents());
        self::assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function theEndpointDoesNotSupportTheRelParameter()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->willReturn([
            'rel' => 'http://foo.bar/specs/test/1.0/go',
        ]);
        $repository = $this->prophesize(ResourceRepository::class);
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $endpoint = new IssuerDiscoveryEndpoint(
            $repository->reveal(),
            $this->getResponseFactory(),
            $this->getUriFactory(),
            'www.foo.bar:8000'
        );

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        $response->getBody()->rewind();
        self::assertEquals('{"error":"invalid_request","error_description":"Unsupported \"rel\" parameter value."}', $response->getBody()->getContents());
        self::assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function theEndpointCannotFindTheResourceParameter()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->willReturn([
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
        ]);
        $repository = $this->prophesize(ResourceRepository::class);
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $endpoint = new IssuerDiscoveryEndpoint(
            $repository->reveal(),
            $this->getResponseFactory(),
            $this->getUriFactory(),
            'www.foo.bar:8000'
        );

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        $response->getBody()->rewind();
        self::assertEquals('{"error":"invalid_request","error_description":"The parameter \"resource\" is mandatory."}', $response->getBody()->getContents());
        self::assertEquals(400, $response->getStatusCode());
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
        $endpoint = new IssuerDiscoveryEndpoint(
            $repository->reveal(),
            $this->getResponseFactory(),
            $this->getUriFactory(),
            'www.foo.bar:8000'
        );

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        $response->getBody()->rewind();
        self::assertEquals('{"error":"invalid_request","error_description":"Unsupported Extensible Resource Identifier (XRI) resource value."}', $response->getBody()->getContents());
        self::assertEquals(400, $response->getStatusCode());
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
        $endpoint = new IssuerDiscoveryEndpoint(
            $repository->reveal(),
            $this->getResponseFactory(),
            $this->getUriFactory(),
            'www.foo.bar:8000'
        );

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        $response->getBody()->rewind();
        self::assertEquals('{"error":"invalid_request","error_description":"Unsupported domain."}', $response->getBody()->getContents());
        self::assertEquals(400, $response->getStatusCode());
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
        $endpoint = new IssuerDiscoveryEndpoint(
            $repository->reveal(),
            $this->getResponseFactory(),
            $this->getUriFactory(),
            'www.foo.bar:8000'
        );

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        $response->getBody()->rewind();
        self::assertEquals('{"error":"invalid_request","error_description":"The resource with name \"bad@www.foo.bar:8000\" does not exist or is not supported by this server."}', $response->getBody()->getContents());
        self::assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function theResourceIsAValidResourceFromEmail()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->willReturn([
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
            'resource' => 'hello@www.foo.bar:8000',
        ]);
        $resource = $this->prophesize(Resource::class);
        $resource->getIssuer()->willReturn('https://my.server.com/hello');
        $repository = $this->prophesize(ResourceRepository::class);
        $repository->find(Argument::type(ResourceId::class))->willReturn($resource->reveal());
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $endpoint = new IssuerDiscoveryEndpoint(
            $repository->reveal(),
            $this->getResponseFactory(),
            $this->getUriFactory(),
            'www.foo.bar:8000'
        );

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        $response->getBody()->rewind();
        self::assertEquals('{"subject":"hello@www.foo.bar:8000","links":[{"rel":"http://openid.net/specs/connect/1.0/issuer","href":"https://my.server.com/hello"}]}', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
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
        $resource = $this->prophesize(Resource::class);
        $resource->getIssuer()->willReturn('https://my.server.com/hello');
        $repository = $this->prophesize(ResourceRepository::class);
        $repository->find(Argument::type(ResourceId::class))->willReturn($resource->reveal());
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $endpoint = new IssuerDiscoveryEndpoint(
            $repository->reveal(),
            $this->getResponseFactory(),
            $this->getUriFactory(),
            'www.foo.bar:8000'
        );

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        $response->getBody()->rewind();
        self::assertEquals('{"subject":"acct:hello%40you@www.foo.bar:8000","links":[{"rel":"http://openid.net/specs/connect/1.0/issuer","href":"https://my.server.com/hello"}]}', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
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
        $resource = $this->prophesize(Resource::class);
        $resource->getIssuer()->willReturn('https://my.server.com/hello');
        $repository = $this->prophesize(ResourceRepository::class);
        $repository->find(Argument::type(ResourceId::class))->willReturn($resource->reveal());
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $endpoint = new IssuerDiscoveryEndpoint(
            $repository->reveal(),
            $this->getResponseFactory(),
            $this->getUriFactory(),
            'www.foo.bar:8000'
        );

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        $response->getBody()->rewind();
        self::assertEquals('{"subject":"https://www.foo.bar:8000/+hello","links":[{"rel":"http://openid.net/specs/connect/1.0/issuer","href":"https://my.server.com/hello"}]}', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @return ResponseFactoryInterface
     */
    private function getResponseFactory(): ResponseFactoryInterface
    {
        return new ResponseFactory();
    }

    private function getUriFactory(): UriFactoryInterface
    {
        return new UriFactory();
    }
}
