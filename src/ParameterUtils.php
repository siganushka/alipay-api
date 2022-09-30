<?php

declare(strict_types=1);

namespace Siganushka\ApiFactory\Alipay;

use Siganushka\ApiFactory\ResolverInterface;
use Siganushka\ApiFactory\ResolverTrait;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see https://opendocs.alipay.com/open/204/01dcc0
 * @see https://opendocs.alipay.com/apis/api_1/alipay.trade.app.pay
 */
class ParameterUtils implements ResolverInterface
{
    use ResolverTrait;

    private SignatureUtils $signatureUtils;

    public function __construct(SignatureUtils $signatureUtils = null)
    {
        $this->signatureUtils = $signatureUtils ?? new SignatureUtils();
    }

    /**
     * 生成 APP 支付参数.
     *
     * @param array $options APP 支付参数选项
     */
    public function app(array $options = []): string
    {
        $resolved = $this->resolve($options);
        $bizContent = array_filter([
            'out_trade_no' => $resolved['out_trade_no'],
            'total_amount' => $resolved['total_amount'],
            'subject' => $resolved['subject'],
            'product_code' => $resolved['product_code'],
            'body' => $resolved['body'],
            'goods_detail' => $resolved['goods_detail'],
            'time_expire' => $resolved['time_expire'],
            'extend_params' => $resolved['extend_params'],
            'passback_params' => $resolved['passback_params'],
            'agreement_sign_params' => $resolved['agreement_sign_params'],
            'enable_pay_channels' => $resolved['enable_pay_channels'],
            'specified_channel' => $resolved['specified_channel'],
            'disable_pay_channels' => $resolved['disable_pay_channels'],
            'merchant_order_no' => $resolved['merchant_order_no'],
            'ext_user_info' => $resolved['ext_user_info'],
            'query_options' => $resolved['query_options'],
        ], fn ($value) => null !== $value);

        $parameter = array_filter([
            'app_id' => $resolved['appid'],
            'method' => 'alipay.trade.app.pay',
            'charset' => 'UTF-8',
            'sign_type' => $resolved['sign_type'],
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'notify_url' => $resolved['notify_url'],
            'app_auth_token' => $resolved['app_auth_token'],
            'biz_content' => json_encode($bizContent, \JSON_UNESCAPED_UNICODE),
        ], fn ($value) => null !== $value);

        // Generate signature
        $parameter['sign'] = $this->signatureUtils->generate([
            'public_key' => $resolved['public_key'],
            'private_key' => $resolved['private_key'],
            'sign_type' => $resolved['sign_type'],
            'data' => $parameter,
        ]);

        return http_build_query($parameter);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        OptionsUtils::appid($resolver);
        OptionsUtils::public_key($resolver);
        OptionsUtils::private_key($resolver);
        OptionsUtils::sign_type($resolver);

        $resolver
            ->define('notify_url')
            ->default(null)
            ->allowedTypes('null', 'string')
        ;

        $resolver
            ->define('app_auth_token')
            ->default(null)
            ->allowedTypes('null', 'string')
        ;

        $resolver
            ->define('subject')
            ->required()
            ->allowedTypes('string')
        ;

        $resolver
            ->define('out_trade_no')
            ->required()
            ->allowedTypes('string')
        ;

        $resolver
            ->define('total_amount')
            ->default(null)
            ->allowedTypes('null', 'string')
            ->normalize(function (Options $options, ?string $totalAmount) {
                if (\is_string($totalAmount)) {
                    return $totalAmount;
                }

                // 注意：格式化后不能出现逗号
                if (\is_int($options['total_amount_as_cents'])) {
                    return number_format($options['total_amount_as_cents'] / 100, 2, '.', '');
                }

                throw new MissingOptionsException('The required option "total_amount" is missing.');
            })
        ;

        $resolver
            ->define('total_amount_as_cents')
            ->default(null)
            ->allowedTypes('null', 'int')
        ;

        $resolver
            ->define('product_code')
            ->default(null)
            ->allowedValues(null, 'QUICK_MSECURITY_PAY', 'CYCLE_PAY_AUTH')
        ;

        $resolver
            ->define('body')
            ->default(null)
            ->allowedTypes('null', 'string')
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
            ->define('extend_params')
            ->default(null)
            ->allowedTypes('null', 'array')
        ;

        $resolver
            ->define('passback_params')
            ->default(null)
            ->allowedTypes('null', 'string')
        ;

        $resolver
            ->define('agreement_sign_params')
            ->default(null)
            ->allowedTypes('null', 'array')
            ->normalize(function (Options $options, ?array $agreementSignParams) {
                if ('CYCLE_PAY_AUTH' === $options['product_code'] && null === $agreementSignParams) {
                    throw new MissingOptionsException('The required option "agreement_sign_params" is missing (when "product_code" option is set to "CYCLE_PAY_AUTH").');
                }

                return $agreementSignParams;
            })
        ;

        $resolver
            ->define('enable_pay_channels')
            ->default(null)
            ->allowedTypes('null', 'string')
        ;

        $resolver
            ->define('disable_pay_channels')
            ->default(null)
            ->allowedTypes('null', 'string')
        ;

        $resolver
            ->define('specified_channel')
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
            ->define('query_options')
            ->default(null)
            ->allowedTypes('null', 'array')
        ;
    }
}
