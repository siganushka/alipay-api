<?php

declare(strict_types=1);

namespace Siganushka\ApiFactory\Alipay;

use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @see https://opendocs.alipay.com/apis/api_1/alipay.trade.refund
 */
class Refund extends AbstractAlipayRequest
{
    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->define('trade_no')
            ->default(null)
            ->allowedTypes('null', 'string')
        ;

        $resolver
            ->define('out_trade_no')
            ->default(null)
            ->allowedTypes('null', 'string')
            ->normalize(static function (Options $options, ?string $outTradeNo) {
                if (null === $options['trade_no'] && null === $outTradeNo) {
                    throw new MissingOptionsException('The required option "trade_no" or "out_trade_no" is missing.');
                }

                return $outTradeNo;
            })
        ;

        $resolver
            ->define('refund_amount')
            ->default(null)
            ->allowedTypes('null', 'string')
            ->normalize(static function (Options $options, ?string $refundAmount) {
                if (\is_string($refundAmount)) {
                    return $refundAmount;
                }

                // 注意：格式化后不能出现逗号
                if (\is_int($options['refund_amount_as_cents'])) {
                    return number_format($options['refund_amount_as_cents'] / 100, 2, thousands_separator: '');
                }

                throw new MissingOptionsException('The required option "refund_amount" is missing.');
            })
        ;

        $resolver
            ->define('refund_amount_as_cents')
            ->default(null)
            ->allowedTypes('null', 'int')
        ;

        $resolver
            ->define('refund_reason')
            ->default(null)
            ->allowedTypes('null', 'string')
        ;

        $resolver
            ->define('out_request_no')
            ->default(null)
            ->allowedTypes('null', 'string')
        ;

        $resolver
            ->define('refund_royalty_parameters')
            ->default(null)
            ->allowedTypes('null', 'array')
        ;

        $resolver
            ->define('query_options')
            ->default(null)
            ->allowedTypes('null', 'array')
        ;
    }

    protected function getMethodName(): string
    {
        return 'alipay.trade.refund';
    }

    protected function getBizContent(array $options): array
    {
        return array_intersect_key($options, array_flip([
            'trade_no',
            'out_trade_no',
            'refund_amount',
            'refund_reason',
            'out_request_no',
            'refund_royalty_parameters',
            'query_options',
        ]));
    }

    protected function parseRawResponse(ResponseInterface $response): array
    {
        return $response->toArray()['alipay_trade_refund_response'] ?? [];
    }
}
