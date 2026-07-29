<?php

$copyEmails = array_filter(array_map(
    fn (string $email): string => trim($email),
    preg_split('/[\s,;]+/', (string) env('TICKET_CREATED_COPY_EMAILS', ''), -1, PREG_SPLIT_NO_EMPTY) ?: [],
));

return [
    /*
    |--------------------------------------------------------------------------
    | Ticket Creation Mail Copies
    |--------------------------------------------------------------------------
    |
    | These addresses receive a professional internal email every time a ticket
    | is created, regardless of who is assigned inside the system.
    |
    */

    'created_copy_emails' => array_values(array_unique($copyEmails)),
];
