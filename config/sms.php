<?php

return [
    // the maximum age of the sms verification code (unit is seconds)
    'code_age' => env('SMS_TTL', 180),

    // the maximum number to send the receipt per transaction
    'max_send_receipt_tries' => env('MAX_SEND_RECEIPT_TRIES', 3),

    // the maximum number to send the sms verification code
    'maximum_attempts' => env('SMS_MAXIMUM_ATTEMPTS', 3),

    // Sms gateways configurations
    'providers' => [
        // Twilio SMS (docs: https://www.twilio.com/docs/sms)
        'win_sms' => [
            'api_key' => env('WIN_SMS_API_KEY', ''),
            'sender_ID' => env('WIN_SMS_SENDER_ID', ''),
        ]
    ]
];
