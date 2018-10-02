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

final class DisplayParameterChecker implements ParameterChecker
{
    public const DISPLAY_PAGE = 'page';

    public const DISPLAY_POPUP = 'popup';

    public const DISPLAY_TOUCH = 'touch';

    public const DISPLAY_WAP = 'wap';

    public function check(AuthorizationRequest $authorization): void
    {
        if ($authorization->hasQueryParam('display') && !\in_array($authorization->getQueryParam('display'), $this->getAllowedDisplayValues(), true)) {
            throw new \InvalidArgumentException(\Safe\sprintf('Invalid parameter "display". Allowed values are %s', \implode(', ', $this->getAllowedDisplayValues())));
        }
    }

    /**
     * @return string[]
     */
    private function getAllowedDisplayValues(): array
    {
        return [
            self::DISPLAY_PAGE,
            self::DISPLAY_POPUP,
            self::DISPLAY_TOUCH,
            self::DISPLAY_WAP,
        ];
    }
}
