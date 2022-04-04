<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientConfigurationEndpoint;

use InvalidArgumentException;
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\ClientRule\RuleManager;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class ClientConfigurationEndpoint implements MiddlewareInterface
{
    public function __construct(
        private ClientRepository $clientRepository,
        private BearerToken $bearerToken,
        private RuleManager $ruleManager
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $this->checkClient($request);

        switch ($request->getMethod()) {
            case 'GET':
                $get = new ClientConfigurationGetEndpoint();

                return $get->process($request, $next);

            case 'PUT':
                $put = new ClientConfigurationPutEndpoint($this->clientRepository, $this->ruleManager);

                return $put->process($request, $next);

            case 'DELETE':
                $delete = new ClientConfigurationDeleteEndpoint($this->clientRepository);

                return $delete->process($request, $next);

            default:
                throw new OAuth2Error(405, OAuth2Error::ERROR_INVALID_REQUEST, 'Unsupported method.');
        }
    }

    private function checkClient(ServerRequestInterface $request): void
    {
        try {
            $client = $request->getAttribute('client');
            if (! $client instanceof Client) {
                throw new RuntimeException('Invalid client or invalid registration access token.');
            }
            if (! $client->has('registration_access_token')) {
                throw new RuntimeException('Invalid client or invalid registration access token.');
            }
            $values = [];
            $token = $this->bearerToken->find($request, $values);
            if ($token === null) {
                throw new RuntimeException('Invalid client or invalid registration access token.');
            }
            if (! hash_equals($client->get('registration_access_token'), $token)) {
                throw new InvalidArgumentException('Invalid client or invalid registration access token.');
            }
        } catch (InvalidArgumentException $e) {
            throw OAuth2Error::invalidRequest($e->getMessage(), [], $e);
        }
    }
}
