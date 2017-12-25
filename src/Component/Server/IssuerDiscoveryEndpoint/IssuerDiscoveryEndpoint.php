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

namespace OAuth2Framework\Component\Server\IssuerDiscoveryEndpoint;

use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\Factory\UriFactoryInterface;
use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

final class IssuerDiscoveryEndpoint implements MiddlewareInterface
{
    private const REL_NAME = 'http://openid.net/specs/connect/1.0/issuer';

    /**
     * @var ResourceRepository
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
     * @param ResourceRepository       $resourceManager The Resource Manager
     * @param ResponseFactoryInterface $responseFactory The Response Factory
     * @param UriFactoryInterface      $uriFactory      The Uri Factory
     * @param string                   $server          The host of this discovery service
     */
    public function __construct(ResourceRepository $resourceManager, ResponseFactoryInterface $responseFactory, UriFactoryInterface $uriFactory, string $server)
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
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $this->checkRel($request);
            $resourceName = $this->getResourceName($request);
            $resource = $this->resourceManager->find($resourceName);
            if (null === $resource) {
                throw new \InvalidArgumentException(sprintf('The resource with name "%s" does not exist or is not supported by this server.', $resourceName), 400);
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
            $response->getBody()->write(json_encode(['error' => OAuth2Exception::ERROR_INVALID_REQUEST, 'error_description' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        $headers['Cache-Control'] = 'no-cache, no-store, max-age=0, must-revalidate, private';
        $headers['Pragma'] = 'no-cache';
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }

    /**
     * @param ResourceId $resourceName
     * @param resource   $resource
     *
     * @return array
     */
    private function getResourceData(ResourceId $resourceName, Resource $resource): array
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
        $query_params = $request->getQueryParams() ?? [];
        if (!array_key_exists('rel', $query_params)) {
            throw new \InvalidArgumentException('The parameter "rel" is mandatory.', 400);
        }
        if (self::REL_NAME !== $query_params['rel']) {
            throw new \InvalidArgumentException('Unsupported "rel" parameter value.', 400);
        }
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
        $query_params = $request->getQueryParams() ?? [];
        if (!array_key_exists('resource', $query_params)) {
            throw new \InvalidArgumentException('The parameter "resource" is mandatory.', 400);
        }
        $resourceName = $query_params['resource'];
        $domain = $this->findResourceNameDomain($resourceName);
        if ($domain !== $this->host) {
            throw new \InvalidArgumentException('Unsupported domain.', 400);
        }

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
        if (0 === $at) {
            throw new \InvalidArgumentException('Unsupported Extensible Resource Identifier (XRI) resource value.', 400);
        }
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
