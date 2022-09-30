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
     * @param array $options 数据签名选项
     *
     * @return string 数据签名
     *
     * @throws \RuntimeException 生成数据签名失败
     */
    public function generate(array $options = []): string
    {
        $resolved = $this->resolve($options);
        $rawData = $resolved['data'];

        ksort($rawData);
        $data = http_build_query($rawData);
        $data = urldecode($data);

        $algorithm = (OptionsUtils::SIGN_TYPE_RSA === $resolved['sign_type'])
            ? \OPENSSL_ALGO_SHA1
            : \OPENSSL_ALGO_SHA256;

        try {
            $result = openssl_sign($data, $rawSignature, $resolved['private_key'], $algorithm);
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
     * @param array  $options   数据签名选项（注意 data 不包含签名）
     *
     * @return bool 数据签名是否有效
     */
    public function verify(string $signature, array $options = []): bool
    {
        $resolved = $this->resolve($options);
        $rawData = $resolved['data'];

        ksort($rawData);
        $data = http_build_query($rawData);
        $data = urldecode($data);

        $rawSignature = base64_decode($signature);
        if (!$rawSignature) {
            return false;
        }

        $algorithm = (OptionsUtils::SIGN_TYPE_RSA === $resolved['sign_type'])
            ? \OPENSSL_ALGO_SHA1
            : \OPENSSL_ALGO_SHA256;

        try {
            $result = openssl_verify($data, $rawSignature, $resolved['public_key'], $algorithm);
        } catch (\Throwable $th) {
            return false;
        }

        return $result && $result > 0;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        OptionsUtils::public_key($resolver);
        OptionsUtils::private_key($resolver);
        OptionsUtils::sign_type($resolver);

        $resolver
            ->define('data')
            ->required()
            ->allowedTypes('array')
        ;
    }
}
