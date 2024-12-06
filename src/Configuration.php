<?php

declare(strict_types=1);

namespace Siganushka\ApiFactory\Alipay;

use Siganushka\ApiFactory\AbstractConfiguration;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Configuration extends AbstractConfiguration
{
    public static function configureOptions(OptionsResolver $resolver): void
    {
        OptionSet::appid($resolver);
        OptionSet::public_key($resolver);
        OptionSet::private_key($resolver);
    }
}
