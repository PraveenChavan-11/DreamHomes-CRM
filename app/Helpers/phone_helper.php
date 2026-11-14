<?php

/**
 * TalentHub Consultant — Phone Number Validation and Normalization Helper
 * Supports Indian (+91) and International (E.164) Formats
 */

if (!function_exists('normalize_phone_number')) {
    /**
     * Validate and normalize a telephone or mobile number to E.164 standard.
     * Returns normalized string (e.g., '+919876543210') or null if invalid.
     */
    function normalize_phone_number(?string $rawPhone): ?string
    {
        if ($rawPhone === null) {
            return null;
        }

        $phone = trim($rawPhone);
        if ($phone === '') {
            return null;
        }

        // Reject if contains illegal characters (letters, excessive special chars)
        if (preg_match('/[a-zA-Z]/', $phone)) {
            return null;
        }

        // Check if starts with a plus
        $hasPlus = str_starts_with($phone, '+');

        // Extract digits only
        $digits = preg_replace('/\D/', '', $phone);
        $len = strlen($digits);

        // Reject too short or too long
        if ($len < 7 || $len > 15) {
            return null;
        }

        // Reject obvious dummy all-same or all-zero sequences
        if (preg_match('/^0+$/', $digits) || preg_match('/^(\d)\1{7,}$/', $digits)) {
            return null;
        }

        // 1. Indian Standard Formats:
        // Case A: 10 digits starting with 6, 7, 8, 9 (standard Indian mobile)
        if ($len === 10 && preg_match('/^[6-9]\d{9}$/', $digits)) {
            return '+91' . $digits;
        }

        // Case B: 11 digits starting with 0 followed by 6, 7, 8, 9 (Indian trunk prefix)
        if ($len === 11 && str_starts_with($digits, '0') && preg_match('/^[6-9]\d{9}$/', substr($digits, 1))) {
            return '+91' . substr($digits, 1);
        }

        // Case C: 12 digits starting with 91 followed by 6, 7, 8, 9 (India with country code)
        if ($len === 12 && str_starts_with($digits, '91') && preg_match('/^[6-9]\d{9}$/', substr($digits, 2))) {
            return '+' . $digits;
        }

        // Case D: Landline Indian with STD code (10-11 digits starting with 1-5)
        if (($len === 10 || $len === 11) && !$hasPlus && preg_match('/^[1-5]\d{8,10}$/', $digits)) {
            return '+91' . ltrim($digits, '0');
        }

        // 2. International E.164 Format:
        // Must start with '+' or be formatted with country code (7 to 15 digits)
        if ($hasPlus) {
            // If explicitly tagged with India (+91), enforce strict 10-digit Indian subscriber rules
            if (str_starts_with($digits, '91')) {
                if ($len === 12 && preg_match('/^[1-9]\d{9}$/', substr($digits, 2))) {
                    return '+' . $digits;
                }
                return null;
            }

            // Must have a valid non-zero country code (1-3 digits) and valid subscriber number
            if (preg_match('/^[1-9]\d{6,14}$/', $digits)) {
                return '+' . $digits;
            }
            return null;
        }

        // If no plus, but valid 11-15 digits starting with non-zero
        if ($len >= 10 && $len <= 15 && preg_match('/^[1-9]\d{9,14}$/', $digits)) {
            return '+' . $digits;
        }

        return null;
    }
}

if (!function_exists('is_valid_phone_number')) {
    /**
     * Check if a phone number is valid
     */
    function is_valid_phone_number(?string $phone): bool
    {
        return normalize_phone_number($phone) !== null;
    }
}

if (!function_exists('validate_experience_years')) {
    /**
     * Validate candidate experience in years.
     * Rejects negative values and values exceeding 50.0 years.
     * Supports increments of 0.5 or 0.1 years.
     * Returns normalized float, or false if invalid.
     */
    function validate_experience_years($exp): float|false|null
    {
        if ($exp === null || $exp === '') {
            return null;
        }

        if (!is_numeric($exp)) {
            return false;
        }

        $val = (float) $exp;

        // Reject negative experience
        if ($val < 0.0) {
            return false;
        }

        // Sensible maximum: 50.0 years
        if ($val > 50.0) {
            return false;
        }

        // Supported increments: round to 1 decimal place (e.g. 4.5, 6.0)
        return round($val, 1);
    }
}
