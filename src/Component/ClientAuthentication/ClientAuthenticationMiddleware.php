<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientAuthentication;

use Assert\Assertion;
use function in_array;
use InvalidArgumentException;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class ClientAuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ClientRepository $clientRepository,
        private AuthenticationMethodManager $authenticationMethodManager
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $authentication_method = null;
            $clientCredentials = null;
            $clientId = $this->authenticationMethodManager->findClientIdAndCredentials(
                $request,
                $authentication_method,
                $clientCredentials
            );
            if ($clientId !== null && $authentication_method instanceof AuthenticationMethod) {
                $client = $this->clientRepository->find($clientId);
                Assertion::notNull($client, 'Client authentication failed.');
                $this->checkClient($client);
                $this->checkAuthenticationMethod($request, $client, $authentication_method, $clientCredentials);
                $request = $request->withAttribute('client', $client);
                $request = $request->withAttribute('client_authentication_method', $authentication_method);
                $request = $request->withAttribute('client_credentials', $clientCredentials);
            }
        } catch (Throwable $e) {
            throw new OAuth2Error(401, OAuth2Error::ERROR_INVALID_CLIENT, $e->getMessage(), [], $e);
        }

        return $handler->handle($request);
    }

    private function checkClient(Client $client): void
    {
        if ($client->isDeleted()) {
            throw new InvalidArgumentException('Client authentication failed.');
        }
        if ($client->areClientCredentialsExpired()) {
            throw new InvalidArgumentException('Client credentials expired.');
        }
    }

    private function checkAuthenticationMethod(
        ServerRequestInterface $request,
        Client $client,
        AuthenticationMethod $authenticationMethod,
        mixed $clientCredentials
    ): void {
        if (! $client->has('token_endpoint_auth_method') || ! in_array(
            $client->get('token_endpoint_auth_method'),
            $authenticationMethod->getSupportedMethods(),
            true
        )) {
            throw new InvalidArgumentException('Client authentication failed.');
        }
        if (! $authenticationMethod->isClientAuthenticated($client, $clientCredentials, $request)) {
            throw new InvalidArgumentException('Client authentication failed.');
        }
    }
}
