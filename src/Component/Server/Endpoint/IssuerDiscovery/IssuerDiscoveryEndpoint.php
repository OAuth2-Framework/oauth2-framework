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

namespace OAuth2Framework\Component\Server\Endpoint\IssuerDiscovery;

use Assert\Assertion;
use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\Factory\UriFactoryInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use OAuth2Framework\Component\Server\Model\Resource\ResourceId;
use OAuth2Framework\Component\Server\Model\Resource\ResourceInterface;
use OAuth2Framework\Component\Server\Model\Resource\ResourceRepositoryInterface;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

final class IssuerDiscoveryEndpoint implements MiddlewareInterface
{
    const REL_NAME = 'http://openid.net/specs/connect/1.0/issuer';

    /**
     * @var ResourceRepositoryInterface
     */
    private $resourceManager;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var UriFactoryInterface
     */
    private $uriFactory;

    /**
     * @var string
     */
    private $host;

    /**
     * IssuerDiscoveryEndpoint constructor.
     *
     * @param ResourceRepositoryInterface $resourceManager The Resource Manager
     * @param ResponseFactoryInterface    $responseFactory The Response Factory
     * @param UriFactoryInterface         $uriFactory      The Uri Factory
     * @param string                      $server          The server URI of this discovery service
     */
    public function __construct(ResourceRepositoryInterface $resourceManager, ResponseFactoryInterface $responseFactory, UriFactoryInterface $uriFactory, string $server)
    {
        $this->resourceManager = $resourceManager;
        $this->responseFactory = $responseFactory;
        $this->uriFactory = $uriFactory;
        $server = $uriFactory->createUri($server);
        $this->host = $this->getDomain($server);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        try {
            $this->checkRel($request);
            $resourceName = $this->getResourceName($request);
            $resource = $this->resourceManager->findResource($resourceName);
            Assertion::notNull($resource, 'The resource is not supported by this server.');
            $data = $this->getResourceData($resourceName, $resource);
            $response = $this->responseFactory->createResponse();
            $response->getBody()->write(json_encode($data));
            $headers = ['Content-Type' => 'application/jrd+json; charset=UTF-8', 'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private', 'Pragma' => 'no-cache'];
            foreach ($headers as $k => $v) {
                $response = $response->withHeader($k, $v);
            }

            return $response;
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(
                400,
                [
                    'error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST,
                    'error_description' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * @param ResourceId        $resourceName
     * @param ResourceInterface $resource
     *
     * @return array
     */
    private function getResourceData(ResourceId $resourceName, ResourceInterface $resource): array
    {
        return [
            'subject' => $resourceName->getValue(),
            'links' => [
                [
                    'rel' => self::REL_NAME,
                    'href' => $resource->getIssuer(),
                ],
            ],
        ];
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @throws \InvalidArgumentException
     */
    private function checkRel(ServerRequestInterface $request)
    {
        $query_params = $request->getQueryParams();
        Assertion::keyExists($query_params, 'rel', 'The parameter \'rel\' is mandatory.');
        Assertion::eq($query_params['rel'], self::REL_NAME, 'Unsupported \'rel\' parameter value.');
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @throws \InvalidArgumentException
     *
     * @return ResourceId
     */
    private function getResourceName(ServerRequestInterface $request): ResourceId
    {
        $query_params = $request->getQueryParams();
        Assertion::keyExists($query_params, 'resource', 'The parameter \'resource\' is mandatory.');
        $resourceName = $query_params['resource'];
        $domain = $this->findResourceNameDomain($resourceName);
        Assertion::eq($domain, $this->host, 'Unsupported domain.');

        return ResourceId::create($resourceName);
    }

    /**
     * @param string $resourceName
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    private function findResourceNameDomain(string $resourceName): string
    {
        if ('acct:' === mb_substr($resourceName, 0, 5, 'utf-8')) {
            $resourceName = mb_substr($resourceName, 5, null, 'utf-8');
        }

        $at = mb_strpos($resourceName, '@', 0, 'utf-8');

        if (false === $at) {
            $uri = $this->uriFactory->createUri($resourceName);

            return $this->getDomain($uri);
        }

        return $this->getDomainFromEmailResource($resourceName, $at);
    }

    /**
     * @param string $resourceName
     * @param int    $at
     *
     * @return string
     */
    private function getDomainFromEmailResource(string $resourceName, int $at): string
    {
        Assertion::notSame(0, $at, 'Unsupported Extensible Resource Identifier (XRI) resource value.');
        list(, $domain) = explode('@', $resourceName);

        return $domain;
    }

    /**
     * @param UriInterface $uri
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    private function getDomain(UriInterface $uri): string
    {
        $host = $uri->getHost();
        if (null !== $uri->getPort()) {
            $host = sprintf('%s:%d', $host, $uri->getPort());
        }

        return $host;
    }
}
