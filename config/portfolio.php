<?php

return [
    'price' => (float) env('PORTFOLIO_PRICE', 500),
    'qr_path' => env('PORTFOLIO_QR_PATH', '/images/payment-qr.png'),
    'wallet_label' => env('PORTFOLIO_WALLET_LABEL', 'QR payment'),
];
