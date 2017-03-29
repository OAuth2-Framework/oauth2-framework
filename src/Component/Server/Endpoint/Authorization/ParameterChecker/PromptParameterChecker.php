<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Endpoint\Authorization\ParameterChecker;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;

final class PromptParameterChecker implements ParameterCheckerInterface
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
                Assertion::true(empty(array_diff($prompt, $this->getAllowedPromptValues())), sprintf('Invalid parameter \'prompt\'. Allowed values are %s', implode(', ', $this->getAllowedPromptValues())));
                Assertion::false(in_array('none', $prompt) && 1 !== count($prompt), 'Invalid parameter \'prompt\'. Prompt value \'none\' must be used alone.');
            }

            return $next($authorization);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => $e->getMessage(), 'authorization' => $authorization]);
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
