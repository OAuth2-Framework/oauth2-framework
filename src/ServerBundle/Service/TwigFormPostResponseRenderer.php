<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Service;

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FormPostResponseRenderer;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

final class TwigFormPostResponseRenderer implements FormPostResponseRenderer
{
    /**
     * @var EngineInterface
     */
    private $templateEngine;

    /**
     * @var string
     */
    private $template;

    public function __construct(EngineInterface $templateEngine, string $template)
    {
        $this->templateEngine = $templateEngine;
        $this->template = $template;
    }

    public function render(string $redirect_uri, array $data): string
    {
        return $this->templateEngine->render(
            $this->template,
            [
                'redirect_uri' => $redirect_uri,
                'inputs' => $data,
            ]
        );
    }
}
