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

namespace OAuth2Framework\Component\Server\Response;

final class OAuth2Exception extends \Exception implements \Throwable
{
    /**
     * @var array
     */
    private $data;

    /**
     * OAuth2Exception constructor.
     *
     * @param int             $code
     * @param array           $data
     * @param \Exception|null $previous
     */
    public function __construct(int $code, array $data, ? \Exception $previous = null)
    {
        $this->data = $data;
        parent::__construct('', $code, $previous);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
