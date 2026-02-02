<?php

declare(strict_types=1);

namespace Siganushka\ApiFactory\Alipay;

use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @see https://opendocs.alipay.com/apis/api_1/alipay.trade.query
 */
class Query extends AbstractAlipayRequest
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
            ->define('org_pid')
            ->default(null)
            ->allowedTypes('null', 'string')
        ;

        $resolver
            ->define('query_options')
            ->default(null)
            ->allowedTypes('null', 'array')
        ;
    }

    protected function getMethodName(): string
    {
        return 'alipay.trade.query';
    }

    protected function getBizContent(array $options): array
    {
        return array_intersect_key($options, array_flip([
            'trade_no',
            'out_trade_no',
            'org_pid',
            'query_options',
        ]));
    }

    protected function parseRawResponse(ResponseInterface $response): array
    {
        return $response->toArray()['alipay_trade_query_response'] ?? [];
    }
}
