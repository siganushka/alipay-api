<?php

declare(strict_types=1);

namespace Siganushka\ApiFactory\Alipay;

use Siganushka\ApiFactory\ResolverInterface;
use Siganushka\ApiFactory\ResolverTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see https://opendocs.alipay.com/common/02khjm
 */
class SignatureUtils implements ResolverInterface
{
    use ResolverTrait;

    /**
     * 生成数据签名.
     *
     * @param array $data    待签名数据
     * @param array $options 自定义选项
     *
     * @return string            数据签名
     * @throws \RuntimeException 生成数据签名失败
     */
    public function generate(array $data, array $options = []): string
    {
        $resolved = $this->resolve($options);

        ksort($data);

        $stringToSignature = http_build_query($data);
        $stringToSignature = urldecode($stringToSignature);

        $algorithm = (OptionSet::SIGN_TYPE_RSA === $resolved['sign_type'])
            ? \OPENSSL_ALGO_SHA1
            : \OPENSSL_ALGO_SHA256;

        try {
            $result = openssl_sign($stringToSignature, $rawSignature, $resolved['app_private_key'], $algorithm);
        } catch (\Throwable $th) {
            throw new \RuntimeException('Unable to generate signature.', 0, $th);
        }

        if (!$result) {
            throw new \RuntimeException('Unable to generate signature.');
        }

        return base64_encode($rawSignature);
    }

    /**
     * 验证数据签名.
     *
     * @param string $signature 数据签名
     * @param array  $data      原始签名数据（不包含 sign 字段）
     * @param array  $options   自定义选项
     *
     * @return bool 数据签名是否有效
     */
    public function verify(string $signature, array $data, array $options = []): bool
    {
        $resolved = $this->resolve($options);

        ksort($data);

        $stringToSignature = http_build_query($data);
        $stringToSignature = urldecode($stringToSignature);

        $rawSignature = base64_decode($signature);
        if (!$rawSignature) {
            return false;
        }

        $algorithm = (OptionSet::SIGN_TYPE_RSA === $resolved['sign_type'])
            ? \OPENSSL_ALGO_SHA1
            : \OPENSSL_ALGO_SHA256;

        try {
            $result = openssl_verify($stringToSignature, $rawSignature, $resolved['alipay_public_key'], $algorithm);
        } catch (\Throwable) {
            return false;
        }

        return $result && $result > 0;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        OptionSet::alipay_public_key($resolver);
        OptionSet::app_private_key($resolver);
        OptionSet::sign_type($resolver);
    }
}
