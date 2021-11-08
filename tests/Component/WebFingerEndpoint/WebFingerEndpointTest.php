<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\WebFingerEndpoint;

use Nyholm\Psr7\ServerRequest;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\AccountResolver;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\EmailResolver;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\Identifier;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\IdentifierResolverManager;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\UriResolver;
use OAuth2Framework\Component\WebFingerEndpoint\Link;
use OAuth2Framework\Component\WebFingerEndpoint\ResourceDescriptor;
use OAuth2Framework\Component\WebFingerEndpoint\WebFingerEndpoint;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Service\UriPathResolver;
use OAuth2Framework\Tests\WebFingerBundle\Functional\ResourceRepository;
use OAuth2Framework\WebFingerBundle\Middleware\TerminalRequestHandler;

/**
 * @internal
 */
final class WebFingerEndpointTest extends OAuth2TestCase
{
    /**
     * @test
     * @dataProvider generateRequests
     */
    public function theEndpointCanHandleRequests(
        array $queryParams,
        string $expectedResponseBody,
        int $expectedCode
    ): void {
        $request = new ServerRequest('GET', '/');
        $request = $request->withQueryParams($queryParams);
        $endpoint = $this->createEndpoint();

        $response = $endpoint->process($request, new TerminalRequestHandler());

        $response->getBody()
            ->rewind()
        ;
        static::assertSame($expectedResponseBody, $response->getBody()->getContents());
        static::assertSame($expectedCode, $response->getStatusCode());
    }

    public function generateRequests(): array
    {
        return [
            [
                [
                    'resource' => '=Foo.Bar',
                ],
                '{"error":"invalid_request","error_description":"The resource identified with \"=Foo.Bar\" does not exist or is not supported by this server."}',
                404,
            ],
            [
                [
                    'rel' => 'http://openid.net/specs/connect/1.0/issuer',
                ],
                '{"error":"invalid_request","error_description":"The parameter \"resource\" is mandatory."}',
                400,
            ],
            [
                [
                    'rel' => 'http://openid.net/specs/connect/1.0/issuer',
                    'resource' => '=Foo.Bar',
                ],
                '{"error":"invalid_request","error_description":"The resource identified with \"=Foo.Bar\" does not exist or is not supported by this server."}',
                404,
            ],
            [
                [
                    'rel' => 'http://openid.net/specs/connect/1.0/issuer',
                    'resource' => '@foo',
                ],
                '{"error":"invalid_request","error_description":"The resource identified with \"@foo\" does not exist or is not supported by this server."}',
                404,
            ],
            [
                [
                    'rel' => 'http://openid.net/specs/connect/1.0/issuer',
                    'resource' => 'hello@me.com',
                ],
                '{"error":"invalid_request","error_description":"The resource identified with \"hello@me.com\" does not exist or is not supported by this server."}',
                404,
            ],
            [
                [
                    'rel' => 'http://openid.net/specs/connect/1.0/issuer',
                    'resource' => 'bad@www.foo.bar:8000',
                ],
                '{"error":"invalid_request","error_description":"The resource identified with \"bad@www.foo.bar:8000\" does not exist or is not supported by this server."}',
                404,
            ],
            [
                [
                    'rel' => 'http://openid.net/specs/connect/1.0/issuer',
                    'resource' => 'hello@www.foo.bar:8000',
                ],
                '{"subject":"hello@www.foo.bar:8000","links":[{"rel":"http://openid.net/specs/connect/1.0/issuer","href":"https://my.server.com/hello"}]}',
                200,
            ],
            [
                [
                    'rel' => 'http://openid.net/specs/connect/1.0/issuer',
                    'resource' => 'acct:hello%40you@www.foo.bar:8000',
                ],
                '{"subject":"acct:hello%40you@www.foo.bar:8000","links":[{"rel":"http://openid.net/specs/connect/1.0/issuer","href":"https://my.server.com/hello"}]}',
                200,
            ],
            [
                [
                    'rel' => 'http://openid.net/specs/connect/1.0/issuer',
                    'resource' => 'https://www.foo.bar:8000/+hello',
                ],
                '{"subject":"https://www.foo.bar:8000/+hello","links":[{"rel":"http://openid.net/specs/connect/1.0/issuer","href":"https://my.server.com/hello"}]}',
                200,
            ],
            [
                [
                    'rel' => 'http://openid.net/specs/connect/1.0/issuer',
                    'resource' => 'https://hello@www.foo.bar:8000',
                ],
                '{"subject":"https://hello@www.foo.bar:8000","links":[{"rel":"http://openid.net/specs/connect/1.0/issuer","href":"https://my.server.com/hello"}]}',
                200,
            ],
        ];
    }

    private function createEndpoint(): WebFingerEndpoint
    {
        $repository = ResourceRepository::create()
            ->set(
                'hello@www.foo.bar:8000',
                Identifier::create('hello', 'www.foo.bar', 8000),
                ResourceDescriptor::create(
                    'hello@www.foo.bar:8000',
                    [],
                    [],
                    [
                        new Link(
                            'http://openid.net/specs/connect/1.0/issuer',
                            null,
                            'https://my.server.com/hello',
                            [],
                            []
                        ),
                    ]
                ),
            )
            ->set(
                'acct:hello%40you@www.foo.bar:8000',
                Identifier::create('hello', 'www.foo.bar', 8000),
                ResourceDescriptor::create(
                    'acct:hello%40you@www.foo.bar:8000',
                    [],
                    [],
                    [
                        new Link(
                            'http://openid.net/specs/connect/1.0/issuer',
                            null,
                            'https://my.server.com/hello',
                            [],
                            []
                        ),
                    ]
                )
            )
            ->set(
                'https://www.foo.bar:8000/+hello',
                Identifier::create('hello', 'www.foo.bar', 8000),
                ResourceDescriptor::create(
                    'https://www.foo.bar:8000/+hello',
                    [],
                    [],
                    [
                        new Link(
                            'http://openid.net/specs/connect/1.0/issuer',
                            null,
                            'https://my.server.com/hello',
                            [],
                            []
                        ),
                    ]
                )
            )
            ->set(
                'https://hello@www.foo.bar:8000',
                Identifier::create('hello', 'www.foo.bar', 8000),
                ResourceDescriptor::create(
                    'https://hello@www.foo.bar:8000',
                    [],
                    [],
                    [
                        new Link(
                            'http://openid.net/specs/connect/1.0/issuer',
                            null,
                            'https://my.server.com/hello',
                            [],
                            []
                        ),
                    ]
                )
            )
        ;

        $identifierResolverManager = IdentifierResolverManager::create()
            ->add(EmailResolver::create())
            ->add(UriResolver::create())
            ->add(AccountResolver::create())
            ->add(UriPathResolver::create())
        ;

        return WebFingerEndpoint::create($repository, $identifierResolverManager);
    }
}
