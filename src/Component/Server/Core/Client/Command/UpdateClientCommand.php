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

namespace OAuth2Framework\Component\Server\Core\Client\Command;

use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;

final class UpdateClientCommand
{
    /**
     * @var ClientId
     */
    private $clientId;

    /**
     * @var DataBag
     */
    private $parameters;

    /**
     * UpdateClientCommand constructor.
     *
     * @param ClientId $clientId
     * @param DataBag  $parameters
     */
    protected function __construct(ClientId $clientId, DataBag $parameters)
    {
        $this->clientId = $clientId;
        $this->parameters = $parameters;
    }

    /**
     * @param ClientId $clientId
     * @param DataBag  $parameters
     *
     * @return UpdateClientCommand
     */
    public static function create(ClientId $clientId, DataBag $parameters): UpdateClientCommand
    {
        return new self($clientId, $parameters);
    }

    /**
     * @return ClientId
     */
    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    /**
     * @return DataBag
     */
    public function getParameters(): DataBag
    {
        return $this->parameters;
    }
}
