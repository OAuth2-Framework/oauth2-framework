<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Service;

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FormPostResponseRenderer;
use Twig\Environment;

final class TwigFormPostResponseRenderer implements FormPostResponseRenderer
{
    public function __construct(
        private readonly Environment $templateEngine,
        private readonly string $template
    ) {
    }

    public function render(string $redirectUri, array $data): string
    {
        return $this->templateEngine->render(
            $this->template,
            [
                'redirect_uri' => $redirectUri,
                'inputs' => $data,
            ]
        );
    }
}
