<?php

declare(strict_types = 1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\Service;

use OAuth2Framework\Component\Server\ResponseMode\FormPostResponseRendererInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

final class FormPostResponseRenderer implements FormPostResponseRendererInterface
{
    /**
     * @var EngineInterface
     */
    private $templateEngine;

    /**
     * @var string
     */
    private $template;

    /**
     * FormPostResponseMode constructor.
     *
     * @param EngineInterface $templateEngine
     * @param string          $template
     */
    public function __construct(EngineInterface $templateEngine, string $template)
    {
        $this->templateEngine = $templateEngine;
        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
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
