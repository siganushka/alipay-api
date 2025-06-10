<?php

declare(strict_types=1);

namespace Siganushka\ApiFactory\Alipay;

use Siganushka\ApiFactory\AbstractRequest;
use Siganushka\ApiFactory\Exception\ParseResponseException;
use Siganushka\ApiFactory\RequestOptions;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @extends AbstractRequest<array>
 */
class Refund extends AbstractRequest
{
    /**
     * @see https://opendocs.alipay.com/apis/api_1/alipay.trade.refund
     */
    public const URL = 'https://openapi.alipay.com/gateway.do';

    private readonly SignatureUtils $signatureUtils;

    public function __construct(?HttpClientInterface $httpClient = null, ?SignatureUtils $signatureUtils = null)
    {
        $this->signatureUtils = $signatureUtils ?? new SignatureUtils();

        parent::__construct($httpClient);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        OptionSet::appid($resolver);
        OptionSet::alipay_public_key($resolver);
        OptionSet::app_private_key($resolver);
        OptionSet::sign_type($resolver);

        $resolver
            ->define('app_auth_token')
            ->default(null)
            ->allowedTypes('null', 'string')
        ;

        $resolver
            ->define('trade_no')
            ->default(null)
            ->allowedTypes('null', 'string')
        ;

        $resolver
            ->define('out_trade_no')
            ->default(null)
            ->allowedTypes('null', 'string')
            ->normalize(function (Options $options, ?string $outTradeNo) {
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
            ->normalize(function (Options $options, ?string $refundAmount) {
                if (\is_string($refundAmount)) {
                    return $refundAmount;
                }

                // 注意：格式化后不能出现逗号
                if (\is_int($options['refund_amount_as_cents'])) {
                    return number_format($options['refund_amount_as_cents'] / 100, 2, '.', '');
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

    protected function configureRequest(RequestOptions $request, array $options): void
    {
        $bizContent = array_filter([
            'trade_no' => $options['trade_no'],
            'out_trade_no' => $options['out_trade_no'],
            'refund_amount' => $options['refund_amount'],
            'refund_reason' => $options['refund_reason'],
            'out_request_no' => $options['out_request_no'],
            'refund_royalty_parameters' => $options['refund_royalty_parameters'],
            'query_options' => $options['query_options'],
        ], fn ($value) => null !== $value);

        $query = array_filter([
            'app_id' => $options['appid'],
            'method' => 'alipay.trade.refund',
            'charset' => 'UTF-8',
            'sign_type' => $options['sign_type'],
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'app_auth_token' => $options['app_auth_token'],
            'biz_content' => json_encode($bizContent),
        ], fn ($value) => null !== $value);

        // Generate signature
        $query['sign'] = $this->signatureUtils->generate($query, [
            'alipay_public_key' => $options['alipay_public_key'],
            'app_private_key' => $options['app_private_key'],
            'sign_type' => $options['sign_type'],
        ]);

        $request
            ->setMethod('GET')
            ->setUrl(static::URL)
            ->setQuery($query)
        ;
    }

    protected function parseResponse(ResponseInterface $response): array
    {
        $result = $response->toArray();

        $alipayResponse = (array) ($result['alipay_trade_refund_response'] ?? []);
        if (isset($alipayResponse['code']) && '10000' === $alipayResponse['code']) {
            return $alipayResponse;
        }

        $subCode = $alipayResponse['sub_code'] ?? ($alipayResponse['code'] ?? '00000');
        $subMsg = $alipayResponse['sub_msg'] ?? ($alipayResponse['msg'] ?? 'error');

        throw new ParseResponseException($response, \sprintf('%s (%s)', $subMsg, $subCode));
    }
}
