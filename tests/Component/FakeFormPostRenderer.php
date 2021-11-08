<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component;

use const JSON_THROW_ON_ERROR;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FormPostResponseRenderer;

final class FakeFormPostRenderer implements FormPostResponseRenderer
{
    public static function create(): self
    {
        return new self();
    }

    public function render(string $redirectUri, array $data): string
    {
        return json_encode([$redirectUri, $data], JSON_THROW_ON_ERROR);
    }
}
