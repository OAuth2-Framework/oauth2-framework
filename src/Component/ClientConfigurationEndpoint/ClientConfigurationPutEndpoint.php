<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientConfigurationEndpoint;

use InvalidArgumentException;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use OAuth2Framework\Component\ClientRule\RuleManager;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ClientConfigurationPutEndpoint implements MiddlewareInterface
{
    public function __construct(
        private ClientRepository $clientRepository,
        private RuleManager $ruleManager
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        /** @var Client $client */
        $client = $request->getAttribute('client');
        $parameters = RequestBodyParser::parseJson($request);

        $command_parameters = new DataBag($parameters);
        $validated_parameters = $this->ruleManager->handle($client->getClientId(), $command_parameters);
        $client->setParameter($validated_parameters);

        try {
            $this->clientRepository->save($client);
        } catch (InvalidArgumentException $e) {
            throw OAuth2Error::invalidRequest($e->getMessage(), [], $e);
        }

        $response = $next->handle($request);
        $response->getBody()
            ->write(json_encode($client->all(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
        ;
        $headers = [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private',
            'Pragma' => 'no-cache',
        ];
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }
}
