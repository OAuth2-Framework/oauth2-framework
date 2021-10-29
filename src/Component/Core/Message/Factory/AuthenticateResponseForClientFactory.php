<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\Message\Factory;

use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use Psr\Http\Message\ResponseInterface;

final class AuthenticateResponseForClientFactory extends OAuth2ResponseFactory
{
    public function __construct(
        private AuthenticationMethodManager $authenticationMethodManager
    ) {
    }

    public function getSupportedCode(): int
    {
        return 401;
    }

    public function createResponse(array $data, ResponseInterface $response): ResponseInterface
    {
        $response = $response->withStatus($this->getSupportedCode());

        $schemes = $this->authenticationMethodManager->getSchemes($data);
        $headers = [
            'Cache-Control' => 'no-store, private',
            'Pragma' => 'no-cache',
            'WWW-Authenticate' => $schemes,
        ];

        return $this->updateHeaders($headers, $response);
    }
}
