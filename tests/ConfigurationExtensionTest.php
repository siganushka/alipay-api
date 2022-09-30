<?php

declare(strict_types=1);

namespace Siganushka\ApiFactory\Alipay\Tests;

use PHPUnit\Framework\TestCase;
use Siganushka\ApiFactory\Alipay\ConfigurationExtension;
use Siganushka\ApiFactory\Alipay\ParameterUtils;
use Siganushka\ApiFactory\Alipay\Query;
use Siganushka\ApiFactory\Alipay\Refund;
use Siganushka\ApiFactory\Alipay\SignatureUtils;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurationExtensionTest extends TestCase
{
    protected ?ConfigurationExtension $extension = null;

    protected function setUp(): void
    {
        $this->extension = new ConfigurationExtension(ConfigurationTest::create());
    }

    protected function tearDown(): void
    {
        $this->extension = null;
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $this->extension->configureOptions($resolver);

        static::assertEquals([
            'appid' => 'test_appid',
            'public_key' => file_get_contents(ConfigurationTest::PUBLIC_KEY),
            'private_key' => file_get_contents(ConfigurationTest::PRIVATE_KEY),
        ], $resolver->resolve());
    }

    public function testGetExtendedClasses(): void
    {
        static::assertEquals([
            Query::class,
            Refund::class,
            ParameterUtils::class,
            SignatureUtils::class,
        ], ConfigurationExtension::getExtendedClasses());
    }
}
