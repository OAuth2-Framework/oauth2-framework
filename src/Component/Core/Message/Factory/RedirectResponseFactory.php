<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\Message\Factory;

use function array_key_exists;
use InvalidArgumentException;
use function is_string;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseMode;
use Psr\Http\Message\ResponseInterface;

final class RedirectResponseFactory implements ResponseFactory
{
    public function getSupportedCode(): int
    {
        return 303;
    }

    public function createResponse(array $data, ResponseInterface $response): ResponseInterface
    {
        if (! array_key_exists('response_mode', $data) || ! $data['response_mode'] instanceof ResponseMode) {
            throw new InvalidArgumentException('The "response_mode" parameter is missing or invalid.');
        }
        if (! array_key_exists('redirect_uri', $data) || ! is_string($data['redirect_uri'])) {
            throw new InvalidArgumentException('The "redirect_uri" parameter is missing or invalid.');
        }

        /** @var ResponseMode $responseMode */
        $responseMode = $data['response_mode'];
        /** @var string $redirectUri */
        $redirectUri = $data['redirect_uri'];

        unset($data['response_mode'], $data['redirect_uri']);

        return $responseMode->buildResponse($response, $redirectUri, $data);
    }
}
