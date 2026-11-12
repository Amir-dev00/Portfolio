<?php

/**
 * SMS.ir owner alerts for Book a Call (REST bulk send).
 * Docs: https://github.com/IPeCompany/SmsPanelV2.Laravel/blob/main/Send.md
 * HTTP: POST https://api.sms.ir/v1/send/bulk with X-API-KEY.
 */
return [
    'enabled' => (bool) env('SMSIR_ENABLED', false),
    'api_key' => env('SMSIR_API_KEY'),
    'line_number' => env('SMSIR_LINE_NUMBER'),
    'owner_mobile' => env('SMSIR_OWNER_MOBILE'),
    /** Short timeout so a slow provider never holds the booking response. */
    'timeout' => (int) env('SMSIR_TIMEOUT', 5),
    'base_url' => rtrim((string) env('SMSIR_BASE_URL', 'https://api.sms.ir'), '/'),
];
