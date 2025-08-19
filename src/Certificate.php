<?php
namespace Ostap\Nube;

class Certificate
{
    public static function generate(string $certPath, string $keyPath, string $cn = 'php-client'): array
    {
        $dn = [
            "commonName" => $cn,
        ];

        $privKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA
        ]);

        $csr = openssl_csr_new($dn, $privKey);
        $cert = openssl_csr_sign($csr, null, $privKey, 365);

        openssl_x509_export_to_file($cert, $certPath);
        openssl_pkey_export_to_file($privKey, $keyPath);

        openssl_x509_export($cert, $certOut);
        openssl_pkey_export($privKey, $keyOut);

        return [
            'cert' => $certOut,
            'key'  => $keyOut
        ];
    }
}
