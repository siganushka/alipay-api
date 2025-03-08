<?php

declare(strict_types=1);

namespace Siganushka\ApiFactory\Alipay;

use Siganushka\ApiFactory\ResolverInterface;
use Siganushka\ApiFactory\ResolverTrait;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see https://opendocs.alipay.com/apis/api_1/alipay.trade.page.pay
 */
class PagePayUtils implements ResolverInterface
{
    use ResolverTrait;

    private SignatureUtils $signatureUtils;

    public function __construct(?SignatureUtils $signatureUtils = null)
    {
        $this->signatureUtils = $signatureUtils ?? new SignatureUtils();
    }

    /**
     * 生成网站支付参数.
     *
     * @param array $options 网站支付参数
     */
    public function params(array $options = []): array
    {
        $resolved = $this->resolve($options);
        $bizContent = array_filter([
            'out_trade_no' => $resolved['out_trade_no'],
            'total_amount' => $resolved['total_amount'],
            'subject' => $resolved['subject'],
            'product_code' => $resolved['product_code'],
            'qr_pay_mode' => $resolved['qr_pay_mode'],
            'qrcode_width' => $resolved['qrcode_width'],
            'goods_detail' => $resolved['goods_detail'],
            'time_expire' => $resolved['time_expire'],
            'sub_merchant' => $resolved['sub_merchant'],
            'extend_params' => $resolved['extend_params'],
            'business_params' => $resolved['business_params'],
            'promo_params' => $resolved['promo_params'],
            'integration_type' => $resolved['integration_type'],
            'request_from_url' => $resolved['request_from_url'],
            'store_id' => $resolved['store_id'],
            'merchant_order_no' => $resolved['merchant_order_no'],
            'ext_user_info' => $resolved['ext_user_info'],
            'invoice_info' => $resolved['invoice_info'],
        ], fn ($value) => null !== $value && [] !== $value);

        $query = array_filter([
            'app_id' => $resolved['appid'],
            'method' => 'alipay.trade.page.pay',
            'charset' => 'UTF-8',
            'sign_type' => $resolved['sign_type'],
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'notify_url' => $resolved['notify_url'],
            'biz_content' => json_encode($bizContent),
        ], fn ($value) => null !== $value);

        // Generate signature
        $query['sign'] = $this->signatureUtils->generate([
            'public_key' => $resolved['public_key'],
            'private_key' => $resolved['private_key'],
            'sign_type' => $resolved['sign_type'],
            'data' => $query,
        ]);

        return $query;
    }

    /**
     * 生成网站支付收银台地址
     *
     * @param array $options 网站支付参数
     */
    public function url(array $options = []): string
    {
        $query = $this->params($options);

        return \sprintf('https://openapi.alipay.com/gateway.do?%s', http_build_query($query));
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        OptionSet::appid($resolver);
        OptionSet::public_key($resolver);
        OptionSet::private_key($resolver);
        OptionSet::sign_type($resolver);

        $resolver
            ->define('notify_url')
            ->default(null)
            ->allowedTypes('null', 'string')
        ;

        $resolver
            ->define('out_trade_no')
            ->required()
            ->allowedTypes('string')
        ;

        $resolver
            ->define('total_amount')
            ->required()
            ->allowedTypes('string')
        ;

        $resolver
            ->define('subject')
            ->required()
            ->allowedTypes('string')
        ;

        $resolver
            ->define('product_code')
            ->default('FAST_INSTANT_TRADE_PAY')
            ->allowedValues('FAST_INSTANT_TRADE_PAY')
        ;

        $resolver
            ->define('qr_pay_mode')
            ->default(null)
            ->allowedValues(null, 0, 1, 2, 3, 4)
        ;

        $resolver
            ->define('qrcode_width')
            ->default(null)
            ->allowedTypes('null', 'int')
        ;

        $resolver
            ->define('goods_detail')
            ->default(null)
            ->allowedTypes('null', 'array')
        ;

        $resolver
            ->define('time_expire')
            ->default(null)
            ->allowedTypes('null', \DateTimeInterface::class)
            ->normalize(fn (Options $options, ?\DateTimeInterface $timeExpire) => null === $timeExpire ? null : $timeExpire->format('Y-m-d H:i:s'))
        ;

        $resolver
            ->define('sub_merchant')
            ->default(null)
            ->allowedTypes('null', 'array')
        ;

        $resolver
            ->define('extend_params')
            ->default(null)
            ->allowedTypes('null', 'array')
        ;

        $resolver
            ->define('business_params')
            ->default(null)
            ->allowedTypes('null', 'array')
        ;

        $resolver
            ->define('promo_params')
            ->default(null)
            ->allowedTypes('null', 'array')
        ;

        $resolver
            ->define('integration_type')
            ->default(null)
            ->allowedValues(null, 'ALIAPP', 'PCWEB')
        ;

        $resolver
            ->define('request_from_url')
            ->default(null)
            ->allowedTypes('null', 'string')
        ;

        $resolver
            ->define('store_id')
            ->default(null)
            ->allowedTypes('null', 'string')
        ;

        $resolver
            ->define('merchant_order_no')
            ->default(null)
            ->allowedTypes('null', 'string')
        ;

        $resolver
            ->define('ext_user_info')
            ->default(null)
            ->allowedTypes('null', 'array')
        ;

        $resolver
            ->define('invoice_info')
            ->default(null)
            ->allowedTypes('null', 'array')
        ;
    }
}
