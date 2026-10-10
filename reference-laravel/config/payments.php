<?php
// Webhook signing secrets, one per gateway. Values come from .env (deploy.sh generates them).
return [
    'mtn'       => ['secret' => env('MTN_WEBHOOK_SECRET')],
    'airtel'    => ['secret' => env('AIRTEL_WEBHOOK_SECRET')],
    'schoolpay' => ['secret' => env('SCHOOLPAY_WEBHOOK_SECRET')],
];
