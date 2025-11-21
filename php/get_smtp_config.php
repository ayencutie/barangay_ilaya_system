<?php
// get_smtp_config.php
// Prefer environment variables for SMTP configuration, fall back to php/smtp_config.php

function get_smtp_config(): array
{
    // Helper to read boolean-ish env values
    $bool = function ($v) {
        if ($v === null) return null;
        $vl = strtolower(trim($v));
        return in_array($vl, ['1', 'true', 'on', 'yes'], true);
    };

    $cfg = [];
    $envEnabled = getenv('SMTP_ENABLED');
    if ($envEnabled !== false) {
        // Build config from environment
        $cfg['enabled'] = $bool($envEnabled) ?: true;
        $cfg['host'] = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $cfg['username'] = getenv('SMTP_USER') ?: '';
        $cfg['password'] = getenv('SMTP_PASS') ?: '';
        $cfg['port'] = intval(getenv('SMTP_PORT') ?: 587);
        $cfg['secure'] = getenv('SMTP_SECURE') ?: 'tls';
        $cfg['from_email'] = getenv('SMTP_FROM_EMAIL') ?: $cfg['username'];
        $cfg['from_name'] = getenv('SMTP_FROM_NAME') ?: 'No Reply';
        $cfg['smtp_auto_tls'] = $bool(getenv('SMTP_AUTO_TLS')) ?? true;
        return $cfg;
    }

    // Fall back to local php/smtp_config.php if present
    $local = __DIR__ . '/smtp_config.php';
    if (file_exists($local)) {
        $fileCfg = include $local;
        if (is_array($fileCfg)) return $fileCfg;
    }

    // Default: disabled
    return ['enabled' => false];
}

// When included directly, return the config array for convenience
return get_smtp_config();
