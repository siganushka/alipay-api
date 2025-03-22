<?php

declare(strict_types=1);

namespace Siganushka\ApiFactory\Alipay\Tests;

use PHPUnit\Framework\TestCase;
use Siganushka\ApiFactory\Alipay\Configuration;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class ConfigurationTest extends TestCase
{
    public const PUBLIC_KEY = __DIR__.'/Fixtures/rsa_public_key.pem';
    public const PRIVATE_KEY = __DIR__.'/Fixtures/rsa_private_key.pem';

    public function testAll(): void
    {
        $configuration = static::create();

        static::assertInstanceOf(\Countable::class, $configuration);
        static::assertInstanceOf(\IteratorAggregate::class, $configuration);
        static::assertInstanceOf(\ArrayAccess::class, $configuration);
        static::assertSame(3, $configuration->count());

        static::assertEquals([
            'appid' => 'test_appid',
            'alipay_public_key' => file_get_contents(static::PUBLIC_KEY),
            'app_private_key' => file_get_contents(static::PRIVATE_KEY),
        ], $configuration->toArray());
    }

    public function testPublicKeyAsString(): void
    {
        /** @var array{ app_private_key: string, alipay_public_key: string } */
        $configuration = static::create([
            'appid' => 'test_appid',
            'app_private_key' => static::PRIVATE_KEY,
            'alipay_public_key' => '
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCziCITZIaFCjpHPk5EeCeWFKzC
iihZFcK2dYxk9+YKgBUV9FM/LjruexAUUeRnNZBZtLMe2c6xvTQwQTQ9Kw8RArXL
VvHv9yS4oDNR2gI5ST2X+R5Kx0D6RZdEBwAvSiqjDPvXFZQJCMsr+tKlWTkWIbfi
ciuSZgZmuT7HtoNRGQIDAQAB',
        ]);

        static::assertStringStartsWith('-----BEGIN PUBLIC KEY-----', $configuration['alipay_public_key']);
        static::assertStringEndsWith('-----END PUBLIC KEY-----', $configuration['alipay_public_key']);
    }

    public function testPrivateKeyAsString(): void
    {
        /** @var array{ app_private_key: string } */
        $configuration = static::create([
            'appid' => 'test_appid',
            'app_private_key' => '
MIICXQIBAAKBgQCziCITZIaFCjpHPk5EeCeWFKzCiihZFcK2dYxk9+YKgBUV9FM/
LjruexAUUeRnNZBZtLMe2c6xvTQwQTQ9Kw8RArXLVvHv9yS4oDNR2gI5ST2X+R5K
x0D6RZdEBwAvSiqjDPvXFZQJCMsr+tKlWTkWIbficiuSZgZmuT7HtoNRGQIDAQAB
AoGAIW3IwommTqFv5pIgarlgzZ496N9m0eeuYOEUajyKlgvxYSwkUBBgosVBYjc5
a0pa/YkbDTSLOyc6z31kp7sby8P0FZE9dxPKKejO9V/ZPAxcAq2s9VUDlnD0hYN8
Pmw58Q64xeMyO4X4g644iAoyPeCCyYY85Ko8WdKvh8Qx6PECQQDo3VONHcTuZAEi
9nt41jvGFdGQ1fthjkE7BDqUxb/ZTdE78OlQl3fYDVfI2DGQ/6hPNzhpuN14Ah5U
6jVELZwNAkEAxV5XaW6gMYekBZRQT3QITcOUx2gHhpKzPWfqljYdnXSCQJwQ3fgq
5S0l2VGneo5j4jj1iK45gsZKTNkauncqPQJBAIr34q/ZrzfxeHgsDr2rZFqvlLRR
70ZmBem5eVhltztw5EhYWnTdIAlQ1S2oT9RPrlswAjudtpWy9fUJHKbGbVUCQEOl
SBRsxB71vHPlF3mD7Wypwg5uS1YGZcSAH1kIhzH2QsZeNzG84wbVaImJgPtyXi2l
FBKalD+MMt8P8idCvIkCQQDNSmggQwPNP4FxjAdcrTKAl4hVsQ4KTAPkPZmlXmCu
qZmgD4svzyYKUHe7xbgpR/87+GHuH/nJhGS7tf/6/Z0Z',
            'alipay_public_key' => static::PUBLIC_KEY,
        ]);

        static::assertStringStartsWith('-----BEGIN RSA PRIVATE KEY-----', $configuration['app_private_key']);
        static::assertStringEndsWith('-----END RSA PRIVATE KEY-----', $configuration['app_private_key']);
    }

    public function testAppidInvalidOptionsException(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "appid" with value 123 is expected to be of type "string", but is of type "int"');

        static::create([
            'appid' => 123,
            'alipay_public_key' => static::PUBLIC_KEY,
            'app_private_key' => static::PRIVATE_KEY,
        ]);
    }

    public function testPublicKeyInvalidOptionsException(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "alipay_public_key" is invalid');

        static::create([
            'appid' => 'test_appid',
            'alipay_public_key' => 'test_public_key',
            'app_private_key' => static::PRIVATE_KEY,
        ]);
    }

    public function testPrivateKeyInvalidOptionsException(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "app_private_key" is invalid');

        static::create([
            'appid' => 'test_appid',
            'alipay_public_key' => static::PUBLIC_KEY,
            'app_private_key' => 'test_private_key',
        ]);
    }

    public static function create(?array $configs = null): Configuration
    {
        if (null === $configs) {
            $configs = [
                'appid' => 'test_appid',
                'alipay_public_key' => static::PUBLIC_KEY,
                'app_private_key' => static::PRIVATE_KEY,
            ];
        }

        return new Configuration($configs);
    }
}
