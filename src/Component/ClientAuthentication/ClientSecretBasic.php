<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientAuthentication;

use Base64Url\Base64Url;
use InvalidArgumentException;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use Psr\Http\Message\ServerRequestInterface;

final class ClientSecretBasic implements AuthenticationMethod
{
    private int $secretLifetime;

    public function __construct(
        private string $realm,
        int $secretLifetime = 0
    ) {
        if ($secretLifetime < 0) {
            throw new InvalidArgumentException('The secret lifetime must be at least 0 (= unlimited).');
        }
        $this->secretLifetime = $secretLifetime;
    }

    public static function create(string $realm): static
    {
        return new self($realm);
    }

    public function getSchemesParameters(): array
    {
        return [sprintf('Basic realm="%s",charset="UTF-8"', $this->realm)];
    }

    /**
     * @param mixed|null $clientCredentials
     */
    public function findClientIdAndCredentials(ServerRequestInterface $request, &$clientCredentials = null): ?ClientId
    {
        $authorization_headers = $request->getHeader('Authorization');
        foreach ($authorization_headers as $authorization_header) {
            $clientId = $this->findClientIdAndCredentialsInAuthorizationHeader(
                $authorization_header,
                $clientCredentials
            );
            if ($clientId !== null) {
                return $clientId;
            }
        }

        return null;
    }

    public function checkClientConfiguration(DataBag $command_parameters, DataBag $validated_parameters): DataBag
    {
        $validated_parameters->set('client_secret', $this->createClientSecret());
        $validated_parameters->set(
            'client_secret_expires_at',
            ($this->secretLifetime === 0 ? 0 : time() + $this->secretLifetime)
        );

        return $validated_parameters;
    }

    /**
     * @param mixed|null $clientCredentials
     */
    public function isClientAuthenticated(Client $client, $clientCredentials, ServerRequestInterface $request): bool
    {
        return hash_equals($client->get('client_secret'), $clientCredentials);
    }

    public function getSupportedMethods(): array
    {
        return ['client_secret_basic'];
    }

    private function findClientIdAndCredentialsInAuthorizationHeader(
        string $authorization_header,
        ?string &$clientCredentials = null
    ): ?ClientId {
        if (mb_strtolower(mb_substr($authorization_header, 0, 6, '8bit'), '8bit') === 'basic ') {
            [$client_id, $client_secret] = explode(
                ':',
                base64_decode(mb_substr(
                    $authorization_header,
                    6,
                    mb_strlen($authorization_header, '8bit') - 6,
                    '8bit'
                ), true)
            );
            if ($client_id !== '' && $client_secret !== '') {
                $clientCredentials = $client_secret;

                return new ClientId($client_id);
            }
        }

        return null;
    }

    private function createClientSecret(): string
    {
        return Base64Url::encode(random_bytes(32));
    }
}
