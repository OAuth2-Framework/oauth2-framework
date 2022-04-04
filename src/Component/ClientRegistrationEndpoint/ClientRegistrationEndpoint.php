<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientRegistrationEndpoint;

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
use Throwable;

final class ClientRegistrationEndpoint implements MiddlewareInterface
{
    public function __construct(
        private ClientRepository $clientRepository,
        private RuleManager $ruleManager
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->checkRequest($request);
        $initialAccessToken = $request->getAttribute('initial_access_token');

        try {
            $userAccountId = $initialAccessToken instanceof InitialAccessToken ? $initialAccessToken->getUserAccountId() : null;
            $parameters = RequestBodyParser::parseJson($request);
            $commandParameters = new DataBag($parameters);
            $clientId = $this->clientRepository->createClientId();
            $validatedParameters = $this->ruleManager->handle($clientId, $commandParameters);

            $response = $handler->handle($request);

            $client = $this->clientRepository->create($clientId, $validatedParameters, $userAccountId);
            $this->clientRepository->save($client);

            return $this->createResponse($response, $client);
        } catch (Throwable $e) {
            throw OAuth2Error::invalidRequest($e->getMessage(), [], $e);
        }
    }

    private function checkRequest(ServerRequestInterface $request): void
    {
        if ($request->getMethod() !== 'POST') {
            throw new OAuth2Error(405, OAuth2Error::ERROR_INVALID_REQUEST, 'Unsupported method.');
        }
    }

    private function createResponse(ResponseInterface $response, Client $client): ResponseInterface
    {
        $response = $response->withStatus(201);
        foreach ([
            'Content-Type' => 'application/json; charset=UTF-8',
            'Cache-Control' => 'no-store',
            'Pragma' => 'no-cache',
        ] as $k => $v) {
            $response = $response->withHeader($k, $v);
        }
        $response->getBody()
            ->write(json_encode($client->all(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
        ;

        return $response;
    }
}
