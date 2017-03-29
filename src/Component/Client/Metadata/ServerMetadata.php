<?php

namespace OAuth2Framework\Component\Client\Metadata;

use Assert\Assertion;
use OAuth2Framework\Component\Client\Behaviour\Metadata;

final class ServerMetadata implements ServerMetadataInterface
{
    use Metadata;

    /**
     * @var array
     */
    private $values;

    private function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * {@inheritdoc}
     */
    static public function createFromServerUri($server_uri, $allow_unsecured_connection = false)
    {
        $metadata = self::downloadContent($server_uri, $allow_unsecured_connection);

        return new self($metadata);
    }

    /**
     * {@inheritdoc}
     */
    static public function createFromValues(array $values)
    {
        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    protected function getValues()
    {
        return $this->values;
    }

    /**
     * {@inheritdoc}
     */
    protected function setValue($key, $value)
    {
        $this->values[$key] = $value;
    }

    /**
     * @param string $url
     * @param bool   $allow_unsecured_connection
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    private static function downloadContent($url, $allow_unsecured_connection)
    {
        // The URL must be a valid URL and scheme must be https
        Assertion::false(
            false === filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED),
            'Invalid URL.'
        );
        Assertion::false(
            false === $allow_unsecured_connection && 'https://' !==  mb_substr($url, 0, 8, '8bit'),
            'Unsecured connection.'
        );

        $params = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL            => $url,
        ];
        if (false === $allow_unsecured_connection) {
            $params[CURLOPT_SSL_VERIFYPEER] = true;
            $params[CURLOPT_SSL_VERIFYHOST] = 2;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $params);
        $content = curl_exec($ch);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        Assertion::eq(1, preg_match('/^application\/json([\s|;].*)?$/', $content_type), sprintf('Content type is not "application/json". It is "%s".', $content_type));
        curl_close($ch);

        Assertion::notEmpty($content, 'Unable to get content.');
        $content = json_decode($content, true);
        Assertion::isArray($content, 'Invalid content.');

        return $content;
    }
}
