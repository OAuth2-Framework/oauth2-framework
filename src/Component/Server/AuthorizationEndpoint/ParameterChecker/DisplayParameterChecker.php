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

final class DisplayParameterChecker implements ParameterChecker
{
    const DISPLAY_PAGE = 'page';

    const DISPLAY_POPUP = 'popup';

    const DISPLAY_TOUCH = 'touch';

    const DISPLAY_WAP = 'wap';

    /**
     * {@inheritdoc}
     */
    public function process(Authorization $authorization, callable $next): Authorization
    {
        try {
            if ($authorization->hasQueryParam('display')) {
                Assertion::true(in_array($authorization->getQueryParam('display'), $this->getAllowedDisplayValues()), sprintf('Invalid parameter "display". Allowed values are %s', json_encode($this->getAllowedDisplayValues(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
            }

            return $next($authorization);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), $authorization, $e);
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
