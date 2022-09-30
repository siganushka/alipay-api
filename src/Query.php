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
 * @see https://opendocs.alipay.com/apis/api_1/alipay.trade.query
 */
class Query extends AbstractRequest
{
    public const URL = 'https://openapi.alipay.com/gateway.do';

    private SignatureUtils $signatureUtils;

    public function __construct(HttpClientInterface $httpClient = null, SignatureUtils $signatureUtils = null)
    {
        $this->signatureUtils = $signatureUtils ?? new SignatureUtils();

        parent::__construct($httpClient);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        OptionsUtils::appid($resolver);
        OptionsUtils::public_key($resolver);
        OptionsUtils::private_key($resolver);
        OptionsUtils::sign_type($resolver);

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

    protected function configureRequest(RequestOptions $request, array $options): void
    {
        $bizContent = array_filter([
            'trade_no' => $options['trade_no'],
            'out_trade_no' => $options['out_trade_no'],
            'org_pid' => $options['org_pid'],
            'query_options' => $options['query_options'],
        ], fn ($value) => null !== $value);

        $query = array_filter([
            'app_id' => $options['appid'],
            'method' => 'alipay.trade.query',
            'charset' => 'UTF-8',
            'sign_type' => $options['sign_type'],
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'app_auth_token' => $options['app_auth_token'],
            'biz_content' => json_encode($bizContent, \JSON_UNESCAPED_UNICODE),
        ], fn ($value) => null !== $value);

        // Generate signature
        $query['sign'] = $this->signatureUtils->generate([
            'public_key' => $options['public_key'],
            'private_key' => $options['private_key'],
            'sign_type' => $options['sign_type'],
            'data' => $query,
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

        $alipayResponse = (array) ($result['alipay_trade_query_response'] ?? []);
        if (isset($alipayResponse['code']) && '10000' === $alipayResponse['code']) {
            return $alipayResponse;
        }

        $subCode = $alipayResponse['sub_code'] ?? ($alipayResponse['code'] ?? '00000');
        $subMsg = $alipayResponse['sub_msg'] ?? ($alipayResponse['msg'] ?? 'error');

        throw new ParseResponseException($response, sprintf('%s (%s)', $subMsg, $subCode));
    }
}
