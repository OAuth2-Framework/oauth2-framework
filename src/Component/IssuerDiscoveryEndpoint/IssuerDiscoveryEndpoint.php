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

namespace OAuth2Framework\Component\IssuerDiscoveryEndpoint;

use Http\Message\ResponseFactory;
use OAuth2Framework\Component\IssuerDiscoveryEndpoint\IdentifierResolver\IdentifierResolverManager;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class IssuerDiscoveryEndpoint implements MiddlewareInterface
{
    private const REL_NAME = 'http://openid.net/specs/connect/1.0/issuer';

    /**
     * @var ResourceRepository
     */
    private $resourceManager;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var IdentifierResolverManager
     */
    private $identifierResolverManager;

    /**
     * @var string
     */
    private $domain;

    /**
     * @var int
     */
    private $port;

    /**
     * IssuerDiscoveryEndpoint constructor.
     *
     * @param ResourceRepository        $resourceManager
     * @param ResponseFactory           $responseFactory
     * @param IdentifierResolverManager $identifierResolverManager
     * @param string                    $domain
     * @param int                       $port
     */
    public function __construct(ResourceRepository $resourceManager, ResponseFactory $responseFactory, IdentifierResolverManager $identifierResolverManager, string $domain, int $port)
    {
        $this->resourceManager = $resourceManager;
        $this->responseFactory = $responseFactory;
        $this->identifierResolverManager = $identifierResolverManager;
        $this->domain = $domain;
        $this->port = $port;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $this->checkRel($request);
            $resourceName = $this->getResourceName($request);
            $resourceId = $this->getResourceId($resourceName);
            $resource = $this->resourceManager->find($resourceId);
            if (null === $resource) {
                throw new \InvalidArgumentException(sprintf('The resource identified with "%s" does not exist or is not supported by this server.', $resourceName), 400);
            }
            $data = $this->getResourceData($resourceName, $resource);
            $response = $this->responseFactory->createResponse(200);
            $headers = [
                'Content-Type' => 'application/jrd+json; charset=UTF-8',
            ];
            $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\InvalidArgumentException $e) {
            $response = $this->responseFactory->createResponse($e->getCode());
            $headers = [
                'Content-Type' => 'application/json; charset=UTF-8',
            ];
            $response->getBody()->write(json_encode(['error' => 'invalid_request', 'error_description' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        $headers['Cache-Control'] = 'no-cache, no-store, max-age=0, must-revalidate, private';
        $headers['Pragma'] = 'no-cache';
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }

    /**
     * @param string         $resourceName
     * @param ResourceObject $resource
     *
     * @return array
     */
    private function getResourceData(string $resourceName, ResourceObject $resource): array
    {
        return [
            'subject' => $resourceName,
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
        $query_params = $request->getQueryParams() ?? [];
        if (!array_key_exists('rel', $query_params)) {
            throw new \InvalidArgumentException('The parameter "rel" is mandatory.', 400);
        }
        if (self::REL_NAME !== $query_params['rel']) {
            throw new \InvalidArgumentException('Unsupported "rel" parameter value.', 400);
        }
    }

    /**
     * @param string $resourceName
     *
     * @throws \InvalidArgumentException
     *
     * @return ResourceId
     */
    private function getResourceId(string $resourceName): ResourceId
    {
        try {
            $identifier = $this->identifierResolverManager->resolve($resourceName);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(sprintf('The resource identified with "%s" does not exist or is not supported by this server.', $resourceName), 400, $e);
        }
        if ($this->domain !== $identifier->getDomain()) {
            throw new \InvalidArgumentException(sprintf('The resource identified with "%s" does not exist or is not supported by this server.', $resourceName), 400);
        }
        if (null !== $identifier->getPort() && $this->port !== $identifier->getPort()) {
            throw new \InvalidArgumentException(sprintf('The resource identified with "%s" does not exist or is not supported by this server.', $resourceName), 400);
        }

        return ResourceId::create($identifier->getUsername());
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    private function getResourceName(ServerRequestInterface $request): string
    {
        $query_params = $request->getQueryParams() ?? [];
        if (!array_key_exists('resource', $query_params)) {
            throw new \InvalidArgumentException('The parameter "resource" is mandatory.', 400);
        }

        return $query_params['resource'];
    }
}
