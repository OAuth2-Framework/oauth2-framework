<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\TokenRevocationEndpoint;

use function array_key_exists;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class TokenRevocationEndpoint implements MiddlewareInterface
{
    public function __construct(
        private TokenTypeHintManager $tokenTypeHintManager
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $callback = $this->getCallback($request);

        try {
            $client = $this->getClient($request);
            $token = $this->getToken($request);
            $hints = $this->getTokenTypeHints($request);

            foreach ($hints as $hint) {
                $result = $hint->find($token);
                if ($result !== null) {
                    if ($client->getPublicId()->getValue() === $result->getClientId()->getValue()) {
                        $hint->revoke($result);

                        return $this->getResponse($response, 200, '', $callback);
                    }

                    throw OAuth2Error::invalidRequest('The parameter "token" is invalid.');
                }
            }

            return $this->getResponse($response, 200, '', $callback);
        } catch (OAuth2Error $e) {
            return $this->getResponse(
                $response,
                $e->getCode(),
                json_encode($e->getData(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $callback
            );
        }
    }

    protected function getToken(ServerRequestInterface $request): string
    {
        $params = $this->getRequestParameters($request);
        if (! array_key_exists('token', $params)) {
            throw OAuth2Error::invalidRequest('The parameter "token" is missing.');
        }

        return $params['token'];
    }

    /**
     * @return TokenTypeHint[]
     */
    protected function getTokenTypeHints(ServerRequestInterface $request): array
    {
        $params = $this->getRequestParameters($request);
        $tokenTypeHints = $this->tokenTypeHintManager->getTokenTypeHints();

        if (array_key_exists('token_type_hint', $params)) {
            $tokenTypeHint = $params['token_type_hint'];
            if (! array_key_exists($params['token_type_hint'], $tokenTypeHints)) {
                throw new OAuth2Error(400, 'unsupported_token_type', sprintf(
                    'The token type hint "%s" is not supported. Please use one of the following values: %s.',
                    $params['token_type_hint'],
                    implode(', ', array_keys($tokenTypeHints))
                ));
            }

            $hint = $tokenTypeHints[$tokenTypeHint];
            unset($tokenTypeHints[$tokenTypeHint]);
            $tokenTypeHints = [
                $tokenTypeHint => $hint,
            ] + $tokenTypeHints;
        }

        return $tokenTypeHints;
    }

    protected function getCallback(ServerRequestInterface $request): ?string
    {
        $params = $this->getRequestParameters($request);
        if (array_key_exists('callback', $params)) {
            return $params['callback'];
        }

        return null;
    }

    abstract protected function getRequestParameters(ServerRequestInterface $request): array;

    private function getResponse(
        ResponseInterface $response,
        int $code,
        string $data,
        ?string $callback
    ): ResponseInterface {
        if ($callback !== null) {
            $data = sprintf('%s(%s)', $callback, $data);
        }

        $response = $response->withStatus($code);
        $response->getBody()
            ->write($data)
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

    private function getClient(ServerRequestInterface $request): Client
    {
        $client = $request->getAttribute('client');
        if ($client === null) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_CLIENT, 'Client authentication failed.');
        }

        return $client;
    }
}
