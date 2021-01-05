<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\WebFingerEndpoint;

use Assert\Assertion;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\Identifier;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\IdentifierResolverManager;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function Safe\json_encode;
use function Safe\sprintf;

final class WebFingerEndpoint implements MiddlewareInterface
{
    private ResponseFactoryInterface $responseFactory;

    private IdentifierResolverManager $identifierResolverManager;

    private ResourceRepository $resourceRepository;

    public function __construct(ResponseFactoryInterface $responseFactory, ResourceRepository $resourceRepository, IdentifierResolverManager $identifierResolverManager)
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
            Assertion::notNull($resourceDescriptor, sprintf('The resource identified with "%s" does not exist or is not supported by this server.', $resource));

            $filteredResourceDescriptor = $this->filterLinks($request, $resourceDescriptor);
            $response = $this->responseFactory->createResponse(200);
            $headers = [
                'Content-Type' => 'application/jrd+json; charset=UTF-8',
            ];
            $response->getBody()->write(json_encode($filteredResourceDescriptor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\InvalidArgumentException $e) {
            $response = $this->responseFactory->createResponse(400);
            $headers = [
                'Content-Type' => 'application/json; charset=UTF-8',
            ];
            $response->getBody()->write(json_encode(['error' => 'invalid_request', 'error_description' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException(sprintf('The resource identified with "%s" does not exist or is not supported by this server.', $resource), 400, $e);
        }
    }

    private function getResource(ServerRequestInterface $request): string
    {
        $query_params = $request->getQueryParams() ?? [];
        Assertion::keyExists($query_params, 'resource', 'The parameter "resource" is mandatory.');

        return $query_params['resource'];
    }

    private function filterLinks(ServerRequestInterface $request, ResourceDescriptor $resourceDescriptor): array
    {
        $data = $resourceDescriptor->jsonSerialize();

        $rels = $this->getRels($request);
        if (!\array_key_exists('links', $data) || 0 === \count($rels) || 0 === \count($data['links'])) {
            return $data;
        }

        $data['links'] = array_filter($data['links'], static function (Link $link) use ($rels): bool {
            if (\in_array($link->getRel(), $rels, true)) {
                return true;
            }

            return false;
        });

        if (0 === \count($data['links'])) {
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
        if (!\array_key_exists('rel', $queryParams)) {
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
