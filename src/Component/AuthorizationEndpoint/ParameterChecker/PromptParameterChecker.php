<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker;

use Assert\Assertion;
use function in_array;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;

final class PromptParameterChecker implements ParameterChecker
{
    public const PROMPT_NONE = 'none';

    public const PROMPT_LOGIN = 'login';

    public const PROMPT_CONSENT = 'consent';

    public const PROMPT_SELECT_ACCOUNT = 'select_account';

    public static function create(): self
    {
        return new self();
    }

    public function check(AuthorizationRequest $authorization): void
    {
        if (! $authorization->hasQueryParam('prompt')) {
            return;
        }
        $prompt = $authorization->getPrompt();
        $diff = array_diff($prompt, $this->getAllowedPromptValues());
        Assertion::noContent(
            $diff,
            sprintf('Invalid parameter "prompt". Allowed values are %s', implode(', ', $this->getAllowedPromptValues()))
        );

        if (in_array('none', $prompt, true)) {
            Assertion::count($prompt, 1, 'Invalid parameter "prompt". Prompt value "none" must be used alone.');
        }
    }

    /**
     * @return string[]
     */
    private function getAllowedPromptValues(): array
    {
        return [self::PROMPT_NONE, self::PROMPT_LOGIN, self::PROMPT_CONSENT, self::PROMPT_SELECT_ACCOUNT];
    }
}
