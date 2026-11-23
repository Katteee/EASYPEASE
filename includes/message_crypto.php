<?php
// APP_MESSAGE_KEY: 64 hex characters representing a random 32-byte secret.
function message_key(): string {
    $hex = getenv('APP_MESSAGE_KEY');
    if (!is_string($hex) || !preg_match('/^[a-f0-9]{64}$/iD', $hex)) throw new RuntimeException('APP_MESSAGE_KEY není nastaven.');
    return hex2bin($hex);
}
function message_encrypt(string $plaintext): string {
    $nonce = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', message_key(), OPENSSL_RAW_DATA, $nonce, $tag);
    if ($ciphertext === false) throw new RuntimeException('Šifrování selhalo.');
    return 'enc:v1:' . base64_encode($nonce.$tag.$ciphertext);
}
function message_decrypt(string $stored): string {
    if (!str_starts_with($stored, 'enc:v1:')) return $stored; // legacy unencrypted rows
    $data = base64_decode(substr($stored, 7), true);
    if ($data === false || strlen($data)<28) throw new RuntimeException('Poškozená zpráva.');
    $plaintext = openssl_decrypt(substr($data,28), 'aes-256-gcm', message_key(), OPENSSL_RAW_DATA, substr($data,0,12), substr($data,12,16));
    if ($plaintext === false) throw new RuntimeException('Dešifrování selhalo.');
    return $plaintext;
}
