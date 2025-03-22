<?php

declare(strict_types=1);

namespace Siganushka\ApiFactory\Alipay;

use Siganushka\ApiFactory\ResolverInterface;
use Siganushka\ApiFactory\ResolverTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see https://opendocs.alipay.com/open/203/105286
 */
class NotifyHandler implements ResolverInterface
{
    use ResolverTrait;

    private SignatureUtils $signatureUtils;

    public function __construct(?SignatureUtils $signatureUtils = null)
    {
        $this->signatureUtils = $signatureUtils ?? new SignatureUtils();
    }

    /**
     * @param Request $request 支付宝支付结果通知请求对象
     * @param array   $options 自定义选项
     *
     * @return array             支付宝支付结果通知数据
     * @throws \RuntimeException 支付通知请求数据无效/签名验证失败
     */
    public function handle(Request $request, array $options = []): array
    {
        $data = $request->request->all();

        $signature = $data['sign'] ?? '';
        $signatureData = array_filter($data, fn ($key) => !\in_array($key, ['sign', 'sign_type']), \ARRAY_FILTER_USE_KEY);

        $resolved = $this->resolve($options);

        if (!$this->signatureUtils->verify($signature, $signatureData, $resolved)) {
            throw new \RuntimeException('Invalid signature.');
        }

        return $data;
    }

    public function success(): Response
    {
        return new Response('success');
    }

    public function fail(): Response
    {
        return new Response('fail');
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        OptionSet::app_private_key($resolver);
        OptionSet::alipay_public_key($resolver);
    }
}
