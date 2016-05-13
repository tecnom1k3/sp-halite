<?php
namespace Acme\Service;

use ParagonIE\Halite\Alerts\InvalidMessage;
use ParagonIE\Halite\Alerts\InvalidSalt;
use ParagonIE\Halite\KeyFactory;
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
     * @param $cipherText
     * @param EncryptionKey $key
     * @return string
     * @throws InvalidMessage
     */
    public function decrypt($cipherText, EncryptionKey $key)
    {
        return Crypto::decrypt($cipherText, $key, true);
    }

    /**
     * @param $plainText
     * @param EncryptionKey $key
     * @return string
     */
    public function encrypt($plainText, EncryptionKey $key)
    {
        return Crypto::encrypt($plainText, $key, true);
    }
}