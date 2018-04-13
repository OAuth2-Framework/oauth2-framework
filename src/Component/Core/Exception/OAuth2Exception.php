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

namespace OAuth2Framework\Component\Core\Exception;

class OAuth2Exception extends \Exception
{
    //Error messages from the RFC5749
    public const ERROR_INVALID_REQUEST = 'invalid_request';

    public const ERROR_INVALID_CLIENT = 'invalid_client';

    public const ERROR_INVALID_GRANT = 'invalid_grant';

    public const ERROR_INVALID_SCOPE = 'invalid_scope';

    public const ERROR_INVALID_TOKEN = 'invalid_token';

    public const ERROR_UNAUTHORIZED_CLIENT = 'unauthorized_client';

    public const ERROR_UNSUPPORTED_GRANT_TYPE = 'unsupported_grant_type';

    public const ERROR_ACCESS_DENIED = 'access_denied';

    public const ERROR_UNSUPPORTED_RESPONSE_TYPE = 'unsupported_response_type';

    public const ERROR_SERVER_ERROR = 'server_error';

    public const ERROR_TEMPORARILY_UNAVAILABLE = 'temporarily_unavailable';

    // Error messages from the RFC5750
    public const ERROR_INSUFFICIENT_SCOPE = 'insufficient_scope';

    //Error messages from OpenID Connect specifications
    public const ERROR_INTERACTION_REQUIRED = 'interaction_required';

    public const ERROR_LOGIN_REQUIRED = 'login_required';

    public const ERROR_ACCOUNT_SELECTION_REQUIRED = 'account_selection_required';

    public const ERROR_CONSENT_REQUIRED = 'consent_required';

    public const ERROR_INVALID_REQUEST_URI = 'invalid_request_uri';

    public const ERROR_INVALID_REQUEST_OBJECT = 'invalid_request_object';

    public const ERROR_REQUEST_NOT_SUPPORTED = 'request_not_supported';

    public const ERROR_REQUEST_URI_NOT_SUPPORTED = 'request_uri_not_supported';

    public const ERROR_REGISTRATION_NOT_SUPPORTED = 'registration_not_supported';

    //Error message for server errors (codes 5xx)
    public const ERROR_INTERNAL = 'internal_server_error';

    //Custom message for this library
    public const ERROR_INVALID_RESOURCE_SERVER = 'invalid_resource_server';

    /**
     * @var null|string
     */
    private $errorDescription;

    /**
     * OAuth2Exception constructor.
     *
     * @param int             $code
     * @param string          $error
     * @param null|string     $errorDescription
     * @param \Exception|null $previous
     */
    public function __construct(int $code, string $error, ?string $errorDescription, ? \Exception $previous = null)
    {
        $this->errorDescription = $errorDescription;
        parent::__construct($error, $code, $previous);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $data = ['error' => $this->getMessage()];
        if (null !== $this->errorDescription) {
            $data['error_description'] = $this->errorDescription;
        }

        return $data;
    }

    /**
     * @return null|string
     */
    public function getErrorDescription(): ?string
    {
        return $this->errorDescription;
    }
}
