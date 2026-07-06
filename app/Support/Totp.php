<?php

namespace App\Support;

/**
 * TOTP خفيف (RFC 6238) بلا حزم خارجية — يعمل offline. للمصادقة الثنائية (Phase 3C).
 *
 * السرّ base32. الكود 6 أرقام كل 30 ثانية (متوافق مع Google Authenticator وغيره).
 */
class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD   = 30;
    private const DIGITS   = 6;

    /** ولّد سرّاً عشوائياً base32 (16 حرفاً = 80 بت). */
    public static function generateSecret(int $length = 16): string
    {
        $s = '';
        for ($i = 0; $i < $length; $i++) {
            $s .= self::ALPHABET[random_int(0, 31)];
        }
        return $s;
    }

    /** تحقّق من كود المستخدم ضمن نافذة (±window خطوات لتحمّل انزياح الساعة). */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== self::DIGITS) {
            return false;
        }
        $counter = (int) floor(time() / self::PERIOD);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::codeAt($secret, $counter + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    /** الكود الحالي (للاختبار/العرض). */
    public static function current(string $secret): string
    {
        return self::codeAt($secret, (int) floor(time() / self::PERIOD));
    }

    /** رابط otpauth للإضافة في تطبيق المصادقة (أو الإدخال اليدوي بالسرّ). */
    public static function uri(string $secret, string $label, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $label)
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&digits=' . self::DIGITS
            . '&period=' . self::PERIOD;
    }

    private static function codeAt(string $secret, int $counter): string
    {
        $key  = self::base32Decode($secret);
        $bin  = pack('J', $counter); // 8 بايت big-endian
        $hash = hash_hmac('sha1', $bin, $key, true);
        $off  = ord($hash[strlen($hash) - 1]) & 0x0f;
        $num  = ((ord($hash[$off]) & 0x7f) << 24)
              | ((ord($hash[$off + 1]) & 0xff) << 16)
              | ((ord($hash[$off + 2]) & 0xff) << 8)
              | (ord($hash[$off + 3]) & 0xff);
        return str_pad((string) ($num % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Decode(string $b32): string
    {
        $b32    = strtoupper($b32);
        $buffer = 0;
        $bits   = 0;
        $out    = '';
        foreach (str_split($b32) as $ch) {
            $v = strpos(self::ALPHABET, $ch);
            if ($v === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $v;
            $bits  += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $out  .= chr(($buffer >> $bits) & 0xff);
            }
        }
        return $out;
    }
}
