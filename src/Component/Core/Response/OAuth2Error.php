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

namespace OAuth2Framework\Component\Core\Response;

use Psr\Http\Message\ResponseInterface;

class OAuth2Error extends OAuth2Response
{
    /**
     * OAuth2Error constructor.
     *
     * @param int               $code     HTTP code
     * @param array             $data     Data to add to the response
     * @param ResponseInterface $response
     */
    public function __construct(int $code, array $data, ResponseInterface $response)
    {
        if (!array_key_exists('error', $data)) {
            throw new \InvalidArgumentException('The "error" parameter is not set in the data.');
        }
        //Assertion::regex($data['error'], '/^[\x20-\x21\x23-\x5B\x5D-\x7E]+$/', 'The parameter "error" contains forbidden characters');
        if (array_key_exists('error_description', $data)) {
            if (!is_string($data['error_description'])) {
                throw new \InvalidArgumentException('The parameter "error_description" must be a string.');
            }
            //Assertion::regex($data['error_description'], '/^[\x20-\x21\x23-\x5B\x5D-\x7E]+$/', 'The parameter "error_description" contains forbidden characters.');
        }
        parent::__construct($code, $data, $response);
    }
}
