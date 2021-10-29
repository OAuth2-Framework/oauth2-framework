<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\Message\Factory;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use Psr\Http\Message\ResponseInterface;

abstract class OAuth2ResponseFactory implements ResponseFactory
{
    public function createResponse(array $data, ResponseInterface $response): ResponseInterface
    {
        $response = $response->withStatus($this->getSupportedCode());
        $this->updateBody($data, $response);
        $headers = $this->getDefaultHeaders();

        return $this->updateHeaders($headers, $response);
    }

    public function updateBody(array $data, ResponseInterface $response): void
    {
        $response->getBody()
            ->write(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
        ;
    }

    public function updateHeaders(array $headers, ResponseInterface $response): ResponseInterface
    {
        foreach ($headers as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        return $response;
    }

    protected function getDefaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
            'Pragma' => 'no-cache',
        ];
    }
}
