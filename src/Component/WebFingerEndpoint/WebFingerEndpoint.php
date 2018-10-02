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

namespace OAuth2Framework\Component\WebFingerEndpoint;

use Http\Message\ResponseFactory;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\Identifier;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\IdentifierResolverManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class WebFingerEndpoint implements MiddlewareInterface
{
    private $responseFactory;

    private $identifierResolverManager;

    private $resourceRepository;

    public function __construct(ResponseFactory $responseFactory, ResourceRepository $resourceRepository, IdentifierResolverManager $identifierResolverManager)
    {
        $this->resourceRepository = $resourceRepository;
        $this->responseFactory = $responseFactory;
        $this->identifierResolverManager = $identifierResolverManager;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $resource = $this->getResource($request);
            $identifier = $this->getIdentifier($resource);
            $resourceDescriptor = $this->resourceRepository->find($resource, $identifier);
            if (null === $resourceDescriptor) {
                throw new \InvalidArgumentException(\Safe\sprintf('The resource identified with "%s" does not exist or is not supported by this server.', $resource), 400);
            }

            $filteredResourceDescriptor = $this->filterLinks($request, $resourceDescriptor);
            $response = $this->responseFactory->createResponse(200);
            $headers = [
                'Content-Type' => 'application/jrd+json; charset=UTF-8',
            ];
            $response->getBody()->write(\Safe\json_encode($filteredResourceDescriptor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\InvalidArgumentException $e) {
            $response = $this->responseFactory->createResponse($e->getCode());
            $headers = [
                'Content-Type' => 'application/json; charset=UTF-8',
            ];
            $response->getBody()->write(\Safe\json_encode(['error' => 'invalid_request', 'error_description' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }

    private function getIdentifier(string $resource): Identifier
    {
        try {
            return $this->identifierResolverManager->resolve($resource);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(\Safe\sprintf('The resource identified with "%s" does not exist or is not supported by this server.', $resource), 400, $e);
        }
    }

    private function getResource(ServerRequestInterface $request): string
    {
        $query_params = $request->getQueryParams() ?? [];
        if (!\array_key_exists('resource', $query_params)) {
            throw new \InvalidArgumentException('The parameter "resource" is mandatory.', 400);
        }

        return $query_params['resource'];
    }

    private function filterLinks(ServerRequestInterface $request, ResourceDescriptor $resourceDescriptor): array
    {
        $data = $resourceDescriptor->jsonSerialize();

        $rels = $this->getRels($request);
        if (empty($rels) || !array_key_exists('links', $data) || empty($data['links'])) {
            return $data;
        }

        $data['links'] = array_filter($data['links'], function (Link $link) use ($rels): ?Link {
            if (\in_array($link->getRel(), $rels, true)) {
                return $link;
            }

            return null;
        });
        if (empty($data['links'])) {
            unset($data['links']);
        }

        return $data;
    }

    /**
     * @return string[]
     */
    private function getRels(ServerRequestInterface $request): array
    {
        $queryParams = $request->getQueryParams();
        if (!array_key_exists('rel', $queryParams)) {
            return [];
        }

        switch (true) {
            case \is_string($queryParams['rel']):
                return [$queryParams['rel']];
            case \is_array($queryParams['rel']):
                return $queryParams['rel'];
            default:
                return [];
        }
    }
}
