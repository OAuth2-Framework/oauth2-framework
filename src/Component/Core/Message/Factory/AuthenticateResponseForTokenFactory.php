<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\Message\Factory;

use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use Psr\Http\Message\ResponseInterface;

final class AuthenticateResponseForTokenFactory extends OAuth2ResponseFactory
{
    public function __construct(
        private TokenTypeManager $tokenTypeManager
    ) {
    }

    public function getSupportedCode(): int
    {
        return 401;
    }

    public function createResponse(array $data, ResponseInterface $response): ResponseInterface
    {
        $response = $response->withStatus($this->getSupportedCode());

        $schemes = $this->tokenTypeManager->getSchemes($data);
        $headers = [
            'Cache-Control' => 'no-store, private',
            'Pragma' => 'no-cache',
            'WWW-Authenticate' => $schemes,
        ];

        return $this->updateHeaders($headers, $response);
    }
}
