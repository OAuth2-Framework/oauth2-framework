<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode;

use League\Uri;
use function League\Uri\build;
use function League\Uri\parse;
use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class FormPostResponseMode implements ResponseMode
{
    private ResponseFactoryInterface $responseFactory;

    public function __construct(
        private FormPostResponseRenderer $renderer
    ) {
        $this->responseFactory = new Psr17Factory();
    }

    public static function create(FormPostResponseRenderer $renderer): static
    {
        return new self($renderer);
    }

    public function name(): string
    {
        return ResponseType::RESPONSE_TYPE_MODE_FORM_POST;
    }

    public function buildResponse(string $redirectUri, array $data): ResponseInterface
    {
        $uri = parse($redirectUri);
        $uri['fragment'] = '_=_'; //A redirect Uri is not supposed to have fragment so we override it.
        $uri = build($uri);

        $template = $this->renderer->render($uri, $data);
        $response = $this->responseFactory->createResponse(200);
        $response = $response->withHeader('Content-Type', 'text/html');
        $response->getBody()
            ->write($template)
        ;

        return $response;
    }
}
