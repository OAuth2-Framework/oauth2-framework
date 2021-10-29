<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\Pairwise;

use Base64Url\Base64Url;
use function count;
use function in_array;
use InvalidArgumentException;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use const OPENSSL_RAW_DATA;

final class EncryptedSubjectIdentifier implements PairwiseSubjectIdentifierAlgorithm
{
    private string $algorithm;

    public function __construct(
        private string $pairwiseEncryptionKey,
        string $algorithm
    ) {
        if (! in_array($algorithm, openssl_get_cipher_methods(), true)) {
            throw new InvalidArgumentException(sprintf('The algorithm "%s" is not supported.', $algorithm));
        }
        $this->algorithm = $algorithm;
    }

    public function calculateSubjectIdentifier(UserAccount $userAccount, string $sectorIdentifierHost): string
    {
        $prepared = sprintf('%s:%s', $sectorIdentifierHost, $userAccount->getUserAccountId()->getValue());
        $iv = hash('sha512', $userAccount->getUserAccountId()->getValue(), true);
        $ivSize = openssl_cipher_iv_length($this->algorithm);
        $iv = mb_substr($iv, 0, $ivSize, '8bit');

        return Base64Url::encode($iv) . ':' . Base64Url::encode(
            openssl_encrypt($prepared, $this->algorithm, $this->pairwiseEncryptionKey, OPENSSL_RAW_DATA, $iv)
        );
    }

    public function getPublicIdFromSubjectIdentifier(string $subjectIdentifier): ?string
    {
        $data = explode(':', $subjectIdentifier);
        if (count($data) !== 2) {
            return null;
        }
        $decoded = openssl_decrypt(
            Base64Url::decode($data[1]),
            $this->algorithm,
            $this->pairwiseEncryptionKey,
            OPENSSL_RAW_DATA,
            Base64Url::decode($data[0])
        );
        $parts = explode(':', $decoded);
        if (count($parts) !== 3) {
            return null;
        }

        return $parts[1];
    }
}
