<?php

namespace OAuth2Framework\Component\Client\Client;
use OAuth2Framework\Component\Client\Grant\GrantTypeInterface;
use OAuth2Framework\Component\Client\ResponseMode\ResponseModeInterface;

/**
 * @method string getPublicId()
 * @method string getClientSecret()
 */
interface OAuth2ClientInterface extends \JsonSerializable
{
    /**
     * The configuration of the client
     * 
     * @return array
     */
    public function getConfiguration();

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has($key);

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get($key);

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value);

    /**
     * This method will try to get an access token
     *
     * @param \OAuth2Framework\Component\Client\Grant\GrantTypeInterface           $grant
     * @param \OAuth2Framework\Component\Client\ResponseMode\ResponseModeInterface $response_mode
     * @param string[]                                         $scope
     *
     * @return \OAuth2Framework\Component\Client\Response\OAuth2ResponseInterface
     */
    public function getAccessToken(GrantTypeInterface $grant, ResponseModeInterface $response_mode, array $scope = []);

}
