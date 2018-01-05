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

namespace OAuth2Framework\Component\Server\AuthorizationEndpoint\ParameterChecker;

use OAuth2Framework\Component\Server\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;

final class PromptParameterChecker implements ParameterChecker
{
    const PROMPT_NONE = 'none';

    const PROMPT_LOGIN = 'login';

    const PROMPT_CONSENT = 'consent';

    const PROMPT_SELECT_ACCOUNT = 'select_account';

    /**
     * {@inheritdoc}
     */
    public function process(Authorization $authorization, callable $next): Authorization
    {
        try {
            if ($authorization->hasQueryParam('prompt')) {
                $prompt = $authorization->getPrompt();
                Assertion::true(empty(array_diff($prompt, $this->getAllowedPromptValues())), sprintf('Invalid parameter "prompt". Allowed values are %s', implode(', ', $this->getAllowedPromptValues())));
                Assertion::false(in_array('none', $prompt) && 1 !== count($prompt), 'Invalid parameter "prompt". Prompt value "none" must be used alone.');
            }

            return $next($authorization);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), $authorization, $e);
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
