<?php

declare(strict_types=1);

namespace Siganushka\ApiFactory\Alipay\Tests;

use PHPUnit\Framework\TestCase;
use Siganushka\ApiFactory\Alipay\ConfigurationExtension;
use Siganushka\ApiFactory\Alipay\NotifyHandler;
use Siganushka\ApiFactory\Alipay\PagePayUtils;
use Siganushka\ApiFactory\Alipay\ParameterUtils;
use Siganushka\ApiFactory\Alipay\Query;
use Siganushka\ApiFactory\Alipay\Refund;
use Siganushka\ApiFactory\Alipay\SignatureUtils;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurationExtensionTest extends TestCase
{
    protected ConfigurationExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new ConfigurationExtension(ConfigurationTest::create());
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined(['appid', 'alipay_public_key', 'app_private_key']);
        $this->extension->configureOptions($resolver);

        static::assertEquals([
            'appid' => 'test_appid',
            'alipay_public_key' => file_get_contents(ConfigurationTest::PUBLIC_KEY),
            'app_private_key' => file_get_contents(ConfigurationTest::PRIVATE_KEY),
        ], $resolver->resolve());
    }

    public function testGetExtendedClasses(): void
    {
        static::assertEquals([
            NotifyHandler::class,
            Query::class,
            Refund::class,
            PagePayUtils::class,
            ParameterUtils::class,
            SignatureUtils::class,
        ], ConfigurationExtension::getExtendedClasses());
    }
}
