<?php

return [
    /*
     * Number of days after payment within which a refund request is accepted.
     * Override via MARKETPLACE_REFUND_WINDOW_DAYS in .env.
     */
    'refund_window_days' => (int) env('MARKETPLACE_REFUND_WINDOW_DAYS', 14),
];
