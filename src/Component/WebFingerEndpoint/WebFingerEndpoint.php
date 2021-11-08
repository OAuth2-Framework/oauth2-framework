<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\WebFingerEndpoint;

use function array_key_exists;
use function count;
use function in_array;
use InvalidArgumentException;
use function is_array;
use function is_string;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\Identifier;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\IdentifierResolverManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class WebFingerEndpoint implements MiddlewareInterface
{
    public function __construct(
        private ResourceRepository $resourceRepository,
        private IdentifierResolverManager $identifierResolverManager
    ) {
    }

    public static function create(
        ResourceRepository $resourceRepository,
        IdentifierResolverManager $identifierResolverManager
    ): self {
        return new self($resourceRepository, $identifierResolverManager);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        try {
            $resource = $this->getResource($request);
            $identifier = $this->getIdentifier($resource);
            $resourceDescriptor = $this->resourceRepository->find($resource, $identifier);
            if ($resourceDescriptor === null) {
                throw new InvalidArgumentException(sprintf(
                    'The resource identified with "%s" does not exist or is not supported by this server.',
                    $resource
                ), 404)
                ;
            }

            $filteredResourceDescriptor = $this->filterLinks($request, $resourceDescriptor);
            $headers = [
                'Content-Type' => 'application/jrd+json; charset=UTF-8',
            ];
            $response = $response->withStatus(200);
            $response->getBody()
                ->write(
                    json_encode(
                        $filteredResourceDescriptor,
                        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    )
                )
            ;
        } catch (InvalidArgumentException $e) {
            $response = $response->withStatus($e->getCode() === 0 ? 400 : $e->getCode());
            $headers = [
                'Content-Type' => 'application/json; charset=UTF-8',
            ];
            $response->getBody()
                ->write(json_encode([
                    'error' => 'invalid_request',
                    'error_description' => $e->getMessage(),
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ;
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
        } catch (Throwable $e) {
            throw new InvalidArgumentException(sprintf(
                'The resource identified with "%s" does not exist or is not supported by this server.',
                $resource
            ), 404, $e);
        }
    }

    private function getResource(ServerRequestInterface $request): string
    {
        $query_params = $request->getQueryParams() ?? [];
        if (! array_key_exists('resource', $query_params)) {
            throw new InvalidArgumentException('The parameter "resource" is mandatory.', 400)
            ;
        }

        return $query_params['resource'];
    }

    private function filterLinks(ServerRequestInterface $request, ResourceDescriptor $resourceDescriptor): array
    {
        $data = $resourceDescriptor->jsonSerialize();

        $rels = $this->getRels($request);
        if (! array_key_exists('links', $data) || count($rels) === 0 || count($data['links']) === 0) {
            return $data;
        }

        $data['links'] = array_filter($data['links'], static function (Link $link) use ($rels): bool {
            if (in_array($link->getRel(), $rels, true)) {
                return true;
            }

            return false;
        });

        if (count($data['links']) === 0) {
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
        if (! array_key_exists('rel', $queryParams)) {
            return [];
        }

        return match (true) {
            is_string($queryParams['rel']) => [$queryParams['rel']],
            is_array($queryParams['rel']) => $queryParams['rel'],
            default => [],
        };
    }
}
