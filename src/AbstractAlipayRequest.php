<?php

declare(strict_types=1);

namespace Siganushka\ApiFactory\Alipay;

use Siganushka\ApiFactory\AbstractRequest;
use Siganushka\ApiFactory\Exception\ParseResponseException;
use Siganushka\ApiFactory\RequestOptions;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @extends AbstractRequest<array>
 */
abstract class AbstractAlipayRequest extends AbstractRequest
{
    protected readonly SignatureUtils $signatureUtils;

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
        OptionSet::app_auth_token($resolver);
    }

    protected function configureRequest(RequestOptions $request, array $options): void
    {
        $bizContent = $this->getBizContent($options);
        $bizContent = array_filter($bizContent, static fn ($value) => null !== $value);
        $bizContent = json_encode($bizContent, \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);

        $query = array_filter([
            'app_id' => $options['appid'],
            'method' => $this->getMethodName(),
            'charset' => 'UTF-8',
            'sign_type' => $options['sign_type'],
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'app_auth_token' => $options['app_auth_token'],
            'biz_content' => $bizContent,
        ], static fn ($value) => null !== $value);

        // Generate signature
        $query['sign'] = $this->signatureUtils->generate($query, [
            'alipay_public_key' => $options['alipay_public_key'],
            'app_private_key' => $options['app_private_key'],
            'sign_type' => $options['sign_type'],
        ]);

        $request
            ->setUrl('https://openapi.alipay.com/gateway.do')
            ->setQuery($query)
        ;
    }

    protected function parseResponse(ResponseInterface $response): array
    {
        $rawResponse = $this->parseRawResponse($response);
        if (isset($rawResponse['code']) && '10000' === $rawResponse['code']) {
            return $rawResponse;
        }

        $subCode = $rawResponse['sub_code'] ?? ($rawResponse['code'] ?? '00000');
        $subMsg = $rawResponse['sub_msg'] ?? ($rawResponse['msg'] ?? 'error');

        throw new ParseResponseException($response, \sprintf('%s (%s)', $subMsg, $subCode));
    }

    abstract protected function getMethodName(): string;

    abstract protected function getBizContent(array $options): array;

    abstract protected function parseRawResponse(ResponseInterface $response): array;
}
