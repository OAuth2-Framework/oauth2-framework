<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientAuthentication;

use Base64Url\Base64Url;
use InvalidArgumentException;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

final class ClientSecretPost implements AuthenticationMethod
{
    private readonly int $secretLifetime;

    public function __construct(int $secretLifetime = 0)
    {
        if ($secretLifetime < 0) {
            throw new InvalidArgumentException('The secret lifetime must be at least 0 (= unlimited).');
        }

        $this->secretLifetime = $secretLifetime;
    }

    public static function create(int $secretLifetime = 0): static
    {
        return new self($secretLifetime);
    }

    public function getSchemesParameters(): array
    {
        return [];
    }

    public function findClientIdAndCredentials(
        ServerRequestInterface $request,
        mixed &$clientCredentials = null
    ): ?ClientId {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        if ($parameters->has('client_id') && $parameters->has('client_secret')) {
            $clientCredentials = $parameters->get('client_secret');

            return new ClientId($parameters->get('client_id'));
        }

        return null;
    }

    public function checkClientConfiguration(DataBag $commandParameters, DataBag $validatedParameters): DataBag
    {
        $validatedParameters->set('client_secret', $this->createClientSecret());
        $validatedParameters->set(
            'client_secret_expires_at',
            ($this->secretLifetime === 0 ? 0 : time() + $this->secretLifetime)
        );

        return $validatedParameters;
    }

    /**
     * @param mixed|null $clientCredentials
     */
    public function isClientAuthenticated(
        Client $client,
        mixed $clientCredentials,
        ServerRequestInterface $request
    ): bool {
        return hash_equals($client->get('client_secret'), $clientCredentials);
    }

    public function getSupportedMethods(): array
    {
        return ['client_secret_post'];
    }

    private function createClientSecret(): string
    {
        return Base64Url::encode(random_bytes(32));
    }
}
