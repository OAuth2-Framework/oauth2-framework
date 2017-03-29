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

namespace OAuth2Framework\Component\Server\Command\AuthCode;

use OAuth2Framework\Component\Server\Model\AuthCode\AuthCodeId;

final class MarkAuthCodeAsUsedCommand
{
    /**
     * @var AuthCodeId
     */
    private $authCodeId;

    /**
     * MarkAuthCodeAsUsedCommand constructor.
     *
     * @param AuthCodeId $authCodeId
     */
    private function __construct(AuthCodeId $authCodeId)
    {
        $this->authCodeId = $authCodeId;
    }

    /**
     * @param AuthCodeId $authCodeId
     *
     * @return MarkAuthCodeAsUsedCommand
     */
    public static function create(AuthCodeId $authCodeId): MarkAuthCodeAsUsedCommand
    {
        return new self($authCodeId);
    }

    /**
     * @return AuthCodeId
     */
    public function getAuthCodeId(): AuthCodeId
    {
        return $this->authCodeId;
    }
}
