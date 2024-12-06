<?php

declare(strict_types=1);

namespace Siganushka\ApiFactory\Alipay\Tests;

use PHPUnit\Framework\TestCase;
use Siganushka\ApiFactory\Alipay\SignatureUtils;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class SignatureUtilsTest extends TestCase
{
    protected SignatureUtils $signatureUtils;

    protected function setUp(): void
    {
        $this->signatureUtils = new SignatureUtils();
    }

    public function testResolve(): void
    {
        $data = ['foo' => 'hello'];
        static::assertEquals([
            'sign_type' => 'RSA2',
            'public_key' => file_get_contents(ConfigurationTest::PUBLIC_KEY),
            'private_key' => file_get_contents(ConfigurationTest::PRIVATE_KEY),
            'data' => $data,
        ], $this->signatureUtils->resolve([
            'public_key' => ConfigurationTest::PUBLIC_KEY,
            'private_key' => ConfigurationTest::PRIVATE_KEY,
            'data' => $data,
        ]));

        static::assertEquals([
            'sign_type' => 'RSA',
            'public_key' => file_get_contents(ConfigurationTest::PUBLIC_KEY),
            'private_key' => file_get_contents(ConfigurationTest::PRIVATE_KEY),
            'data' => $data,
        ], $this->signatureUtils->resolve([
            'sign_type' => 'RSA',
            'public_key' => ConfigurationTest::PUBLIC_KEY,
            'private_key' => ConfigurationTest::PRIVATE_KEY,
            'data' => $data,
        ]));
    }

    /**
     * @dataProvider getSignatureProvider
     */
    public function testGenerateAndVerify(array $data, string $signType): void
    {
        $options = [
            'public_key' => ConfigurationTest::PUBLIC_KEY,
            'private_key' => ConfigurationTest::PRIVATE_KEY,
            'sign_type' => $signType,
            'data' => $data,
        ];

        $signature = $this->signatureUtils->generate($options);
        static::assertTrue($this->signatureUtils->verify($signature, $options));
    }

    public function testPublicKeyMissingOptionsException(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "public_key" is missing');

        $data = ['foo' => 'hello'];
        $this->signatureUtils->generate([
            'private_key' => ConfigurationTest::PRIVATE_KEY,
            'data' => $data,
        ]);
    }

    public function testPrivateKeyMissingOptionsException(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "private_key" is missing');

        $data = ['foo' => 'hello'];
        $this->signatureUtils->generate([
            'public_key' => ConfigurationTest::PUBLIC_KEY,
            'data' => $data,
        ]);
    }

    public function testSignTypeInvalidOptionsException(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "sign_type" with value "foo" is invalid. Accepted values are: "RSA", "RSA2"');

        $data = ['foo' => 'hello'];
        $this->signatureUtils->generate([
            'public_key' => ConfigurationTest::PUBLIC_KEY,
            'private_key' => ConfigurationTest::PRIVATE_KEY,
            'sign_type' => 'foo',
            'data' => $data,
        ]);
    }

    public function getSignatureProvider(): array
    {
        return [
            [
                ['foo' => 'hello'],
                'RSA',
            ],
            [
                ['bar' => 'world'],
                'RSA2',
            ],
        ];
    }
}
