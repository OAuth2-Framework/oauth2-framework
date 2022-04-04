<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Rule;

use Base64Url\Base64Url;
use OAuth2Framework\Component\ClientConfigurationEndpoint\Rule\ClientConfigurationRouteRule as Base;
use OAuth2Framework\Component\Core\Client\ClientId;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class ClientConfigurationRouteRule extends Base
{
    public function __construct(
        private readonly RouterInterface $router
    ) {
    }

    protected function getRegistrationClientUri(ClientId $clientId): string
    {
        return $this->router->generate('oauth2_server_client_configuration', [
            'client_id' => $clientId->getValue(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    protected function generateRegistrationAccessToken(): string
    {
        $length = random_int(62, 64);

        return Base64Url::encode(random_bytes($length));
    }
}
