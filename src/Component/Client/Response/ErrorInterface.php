<?php

namespace OAuth2Framework\Component\Client\Response;


/**
 * @method string getError();
 * @method bool hasErrorDescription();
 * @method string getErrorDescription();
 * @method bool hasErrorUri();
 * @method string getErrorUri();
 */
interface ErrorInterface extends OAuth2ResponseInterface
{
}
