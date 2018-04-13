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

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;

class PromptParameterChecker implements ParameterChecker
{
    public const PROMPT_NONE = 'none';

    public const PROMPT_LOGIN = 'login';

    public const PROMPT_CONSENT = 'consent';

    public const PROMPT_SELECT_ACCOUNT = 'select_account';

    /**
     * {@inheritdoc}
     */
    public function check(Authorization $authorization): Authorization
    {
        try {
            if ($authorization->hasQueryParam('prompt')) {
                $prompt = $authorization->getPrompt();
                $diff = array_diff($prompt, $this->getAllowedPromptValues());
                if (!empty($diff)) {
                    throw new \InvalidArgumentException(sprintf('Invalid parameter "prompt". Allowed values are %s', implode(', ', $this->getAllowedPromptValues())));
                }
                if (in_array('none', $prompt) && 1 !== count($prompt)) {
                    throw new \InvalidArgumentException('Invalid parameter "prompt". Prompt value "none" must be used alone.');
                }
            }

            return $authorization;
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2AuthorizationException(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), $authorization, $e);
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
