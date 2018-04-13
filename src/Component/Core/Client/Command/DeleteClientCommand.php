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

namespace OAuth2Framework\Component\Core\Client\Command;

use OAuth2Framework\Component\Core\Client\ClientId;

class DeleteClientCommand
{
    /**
     * @var ClientId
     */
    private $clientId;

    /**
     * DeleteClientCommand constructor.
     *
     * @param ClientId $clientId
     */
    private function __construct(ClientId $clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @param ClientId $clientId
     *
     * @return DeleteClientCommand
     */
    public static function create(ClientId $clientId): self
    {
        return new self($clientId);
    }

    /**
     * @return ClientId
     */
    public function getClientId(): ClientId
    {
        return $this->clientId;
    }
}
