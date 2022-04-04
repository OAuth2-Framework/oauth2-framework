<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientAuthentication;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use Psr\Http\Message\ServerRequestInterface;

interface AuthenticationMethod
{
    /**
     * @return string[]
     */
    public function getSupportedMethods(): array;

    /**
     * Find a client using the request. If the client is confidential, the client credentials must be checked.
     *
     * @param ServerRequestInterface $request           The request
     * @param mixed                  $clientCredentials The client credentials found in the request
     *
     * @return ClientId|null Return the client public ID if found else null. If credentials have are needed to authenticate the client, they are set to the variable $clientCredentials
     */
    public function findClientIdAndCredentials(
        ServerRequestInterface $request,
        mixed &$clientCredentials = null
    ): ?ClientId;

    public function checkClientConfiguration(DataBag $commandParameters, DataBag $validatedParameters): DataBag;

    /**
     * @param mixed $clientCredentials The client credentials found in the request
     */
    public function isClientAuthenticated(
        Client $client,
        mixed $clientCredentials,
        ServerRequestInterface $request
    ): bool;

    /**
     * @return string[]
     */
    public function getSchemesParameters(): array;
}
