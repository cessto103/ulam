<?php

return [
    'strike_expiry_months' => (int) env('MODERATION_STRIKE_EXPIRY_MONTHS', 9),
    'restriction_days' => (int) env('MODERATION_RESTRICTION_DAYS', 7),
];
