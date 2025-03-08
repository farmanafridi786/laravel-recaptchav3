<?php
return [
    'origin' => env('RECAPTCHAV3_ORIGIN', 'https://www.google.com/recaptcha'),
    'sitekey' => env('RECAPTCHAV3_SITEKEY', ''),
    'secret' => env('RECAPTCHAV3_SECRET', ''),
    'sitekeyv2' => env('RECAPTCHAV2_SITEKEY', ''),
    'secretv2' => env('RECAPTCHAV2_SECRET', ''),
    'locale' => env('RECAPTCHAV3_LOCALE', '')
];
