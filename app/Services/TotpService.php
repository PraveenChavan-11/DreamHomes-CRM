<?php

namespace App\Services;

/**
 * TotpService provides standard RFC 6238 TOTP generation & verification
 * and single-use backup recovery code management.
 */
class TotpService
{
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a new random Base32 TOTP secret (16 bytes = 26 base32 chars)
     */
    public function generateSecret(int $byteCount = 20): string
    {
        $bytes = random_bytes($byteCount);
        return $this->base32Encode($bytes);
    }

    /**
     * Verify a 6-digit TOTP code against a secret with +/- 1 time step window (90s window)
     */
    public function verifyCode(string $secret, string $code, int $discrepancy = 1): bool
    {
        $cleanCode = trim(str_replace(' ', '', $code));
        if (strlen($cleanCode) !== 6 || !ctype_digit($cleanCode)) {
            return false;
        }

        $secretBinary = $this->base32Decode($secret);
        if ($secretBinary === '') {
            return false;
        }

        $currentSlice = (int) floor(time() / 30);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $slice = $currentSlice + $i;
            $calculatedOtp = $this->generateOtp($secretBinary, $slice);
            if (hash_equals($calculatedOtp, $cleanCode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a set of secure random backup recovery codes
     * Returns an array of formatted strings (e.g. "ABCD-1234")
     */
    public function generateBackupCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $part1 = strtoupper(bin2hex(random_bytes(2)));
            $part2 = strtoupper(bin2hex(random_bytes(2)));
            $codes[] = "{$part1}-{$part2}";
        }
        return $codes;
    }

    /**
     * Verify and consume a backup code from stored JSON
     * Returns array ['valid' => bool, 'remaining_codes_json' => string]
     */
    public function verifyAndConsumeBackupCode(string $providedCode, ?string $storedCodesJson): array
    {
        if (empty($storedCodesJson)) {
            return ['valid' => false, 'remaining_codes_json' => '[]'];
        }

        $codes = json_decode($storedCodesJson, true);
        if (!is_array($codes)) {
            return ['valid' => false, 'remaining_codes_json' => '[]'];
        }

        $cleanProvided = strtoupper(trim(str_replace([' ', '-'], '', $providedCode)));

        foreach ($codes as $index => $candidate) {
            $cleanStored = strtoupper(trim(str_replace([' ', '-'], '', $candidate)));
            if (hash_equals($cleanStored, $cleanProvided)) {
                unset($codes[$index]);
                return [
                    'valid' => true,
                    'remaining_codes_json' => json_encode(array_values($codes)),
                ];
            }
        }

        return ['valid' => false, 'remaining_codes_json' => $storedCodesJson];
    }

    /**
     * Generate OTP for a specific time slice
     */
    private function generateOtp(string $secretBinary, int $timeSlice): string
    {
        // 64-bit big endian binary
        $timeBytes = pack('N*', 0, $timeSlice);
        $hmac = hash_hmac('sha1', $timeBytes, $secretBinary, true);
        $offset = ord($hmac[19]) & 0x0F;

        $unpacked = unpack('N', substr($hmac, $offset, 4));
        $code = ($unpacked[1] & 0x7FFFFFFF) % 1000000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Base32 encoding
     */
    private function base32Encode(string $binary): string
    {
        if (empty($binary)) {
            return '';
        }

        $chars = self::BASE32_CHARS;
        $binaryString = '';
        foreach (str_split($binary) as $char) {
            $binaryString .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $fiveBitChunks = str_split($binaryString, 5);
        $base32 = '';
        foreach ($fiveBitChunks as $chunk) {
            $base32 .= $chars[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $base32;
    }

    /**
     * Base32 decoding
     */
    private function base32Decode(string $base32): string
    {
        $base32 = strtoupper(trim($base32));
        if (empty($base32)) {
            return '';
        }

        $chars = self::BASE32_CHARS;
        $binaryString = '';

        for ($i = 0; $i < strlen($base32); $i++) {
            $char = $base32[$i];
            if ($char === '=') {
                break;
            }
            $pos = strpos($chars, $char);
            if ($pos === false) {
                continue;
            }
            $binaryString .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $eightBitChunks = str_split($binaryString, 8);
        $binary = '';
        foreach ($eightBitChunks as $chunk) {
            if (strlen($chunk) === 8) {
                $binary .= chr(bindec($chunk));
            }
        }

        return $binary;
    }
}
