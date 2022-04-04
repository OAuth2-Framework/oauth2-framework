<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker;

use Assert\Assertion;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;

final class DisplayParameterChecker implements ParameterChecker
{
    public const DISPLAY_PAGE = 'page';

    public const DISPLAY_POPUP = 'popup';

    public const DISPLAY_TOUCH = 'touch';

    public const DISPLAY_WAP = 'wap';

    public static function create(): static
    {
        return new self();
    }

    public function check(AuthorizationRequest $authorization): void
    {
        if ($authorization->hasQueryParam('display')) {
            Assertion::inArray(
                $authorization->getQueryParam('display'),
                $this->getAllowedDisplayValues(),
                sprintf('Invalid parameter "display". Allowed values are %s', implode(
                    ', ',
                    $this->getAllowedDisplayValues()
                ))
            );
        }
    }

    /**
     * @return string[]
     */
    private function getAllowedDisplayValues(): array
    {
        return [self::DISPLAY_PAGE, self::DISPLAY_POPUP, self::DISPLAY_TOUCH, self::DISPLAY_WAP];
    }
}
