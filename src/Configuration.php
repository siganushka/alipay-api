<?php

declare(strict_types=1);

namespace Siganushka\ApiFactory\Alipay;

use Siganushka\ApiFactory\AbstractConfiguration;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractConfiguration<array{ appid: string, app_private_key: string, alipay_public_key: string }>
 */
class Configuration extends AbstractConfiguration
{
    public static function configureOptions(OptionsResolver $resolver): void
    {
        OptionSet::appid($resolver);
        OptionSet::app_private_key($resolver);
        OptionSet::alipay_public_key($resolver);
    }
}
