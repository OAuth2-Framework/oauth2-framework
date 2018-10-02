<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;

final class PromptParameterChecker implements ParameterChecker
{
    public const PROMPT_NONE = 'none';

    public const PROMPT_LOGIN = 'login';

    public const PROMPT_CONSENT = 'consent';

    public const PROMPT_SELECT_ACCOUNT = 'select_account';

    public function check(AuthorizationRequest $authorization): void
    {
        if (!$authorization->hasQueryParam('prompt')) {
            return;
        }
        $prompt = $authorization->getPrompt();
        $diff = \array_diff($prompt, $this->getAllowedPromptValues());
        if (!empty($diff)) {
            throw new \InvalidArgumentException(\Safe\sprintf('Invalid parameter "prompt". Allowed values are %s', \implode(', ', $this->getAllowedPromptValues())));
        }
        if (\in_array('none', $prompt, true) && 1 !== \count($prompt)) {
            throw new \InvalidArgumentException('Invalid parameter "prompt". Prompt value "none" must be used alone.');
        }
    }

    /**
     * @return string[]
     */
    private function getAllowedPromptValues(): array
    {
        return [
            self::PROMPT_NONE,
            self::PROMPT_LOGIN,
            self::PROMPT_CONSENT,
            self::PROMPT_SELECT_ACCOUNT,
        ];
    }
}
