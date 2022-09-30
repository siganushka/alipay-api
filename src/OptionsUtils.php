<?php

declare(strict_types=1);

namespace Siganushka\ApiFactory\Alipay;

use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionConfigurator;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OptionsUtils
{
    public const SIGN_TYPE_RSA = 'RSA';
    public const SIGN_TYPE_RSA2 = 'RSA2';

    public static function appid(OptionsResolver $resolver): OptionConfigurator
    {
        return $resolver
            ->define('appid')
            ->required()
            ->allowedTypes('string')
        ;
    }

    public static function public_key(OptionsResolver $resolver): OptionConfigurator
    {
        return $resolver
            ->define('public_key')
            ->required()
            ->allowedTypes('string')
            ->normalize(function (Options $options, ?string $publicKey) {
                if (null === $publicKey) {
                    return $publicKey;
                }

                $publicKey = trim($publicKey);
                if (openssl_pkey_get_public($publicKey)) {
                    return $publicKey;
                }

                $publicKeyContent = "-----BEGIN PUBLIC KEY-----\n".
                    wordwrap($publicKey, 64, "\n", true).
                    "\n-----END PUBLIC KEY-----";

                if (openssl_pkey_get_public($publicKeyContent)) {
                    return $publicKeyContent;
                }

                if (is_file($publicKey)) {
                    $publicKeyContent = file_get_contents($publicKey);
                    if (openssl_pkey_get_public($publicKeyContent)) {
                        return $publicKeyContent;
                    }
                }

                throw new InvalidOptionsException('The option "public_key" is invalid.');
            })
        ;
    }

    public static function private_key(OptionsResolver $resolver): OptionConfigurator
    {
        return $resolver
            ->define('private_key')
            ->required()
            ->allowedTypes('string')
            ->normalize(function (Options $options, ?string $privateKey) {
                if (null === $privateKey) {
                    return $privateKey;
                }

                $privateKey = trim($privateKey);
                if (openssl_pkey_get_private($privateKey)) {
                    return $privateKey;
                }

                $privateKeyContent = "-----BEGIN RSA PRIVATE KEY-----\n".
                    wordwrap($privateKey, 64, "\n", true).
                    "\n-----END RSA PRIVATE KEY-----";

                if (openssl_pkey_get_private($privateKeyContent)) {
                    return $privateKeyContent;
                }

                if (is_file($privateKey)) {
                    $privateKeyContent = file_get_contents($privateKey);
                    if (openssl_pkey_get_private($privateKeyContent)) {
                        return $privateKeyContent;
                    }
                }

                throw new InvalidOptionsException('The option "private_key" is invalid.');
            })
        ;
    }

    public static function sign_type(OptionsResolver $resolver): OptionConfigurator
    {
        return $resolver
            ->define('sign_type')
            ->default(static::SIGN_TYPE_RSA2)
            ->allowedValues(static::SIGN_TYPE_RSA, static::SIGN_TYPE_RSA2)
        ;
    }
}
