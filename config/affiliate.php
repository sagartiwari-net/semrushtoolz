<?php

return [
    'commission_rate' => (float) env('AFFILIATE_COMMISSION_RATE', 0.20),
    'min_payout' => (int) env('AFFILIATE_MIN_PAYOUT', 500),
];
