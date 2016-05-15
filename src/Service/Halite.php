<?php
namespace Acme\Service;

use ParagonIE\Halite\Alerts\InvalidMessage;
use ParagonIE\Halite\Alerts\InvalidSalt;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\AuthenticationKey;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\Halite\Symmetric\EncryptionKey;

/**
 * Class Halite
 * @package Acme\Service
 */
class Halite
{

    /**
     * @param string $password
     * @return EncryptionKey
     * @throws InvalidSalt
     */
    public function deriveKey($password)
    {
        return KeyFactory::deriveEncryptionKey($password, base64_decode(getenv('HALITE_ENCRYPTION_KEY_SALT')));
    }

    /**
     * @param string $password
     * @return AuthenticationKey
     * @throws InvalidSalt
     */
    public function deriveAuthenticationKey($password)
    {
        return KeyFactory::deriveAuthenticationKey($password, base64_decode(getenv('HALITE_ENCRYPTION_KEY_SALT')));
    }

    /**
     * @param $cipherText
     * @param EncryptionKey $key
     * @return string
     * @throws InvalidMessage
     */
    public function decrypt($cipherText, EncryptionKey $key)
    {
        return Crypto::decrypt(base64_decode($cipherText), $key, true);
    }

    /**
     * @param $plainText
     * @param EncryptionKey $key
     * @return string
     */
    public function encrypt($plainText, EncryptionKey $key)
    {
        return base64_encode(Crypto::encrypt($plainText, $key, true));
    }

    /**
     * @param string $message
     * @param AuthenticationKey $authenticationKey
     * @return string
     */
    public function authenticate($message, AuthenticationKey $authenticationKey)
    {
        return base64_encode(Crypto::authenticate(base64_decode($message), $authenticationKey, true));
    }

    /**
     * @param string $message
     * @param AuthenticationKey $authenticationKey
     * @param string $mac
     * @return bool
     */
    public function verify($message, AuthenticationKey $authenticationKey, $mac)
    {
        return Crypto::verify(base64_decode($message), $authenticationKey, base64_decode($mac), true);
    }
}