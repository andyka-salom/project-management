<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Friendly role display labels
    |--------------------------------------------------------------------------
    |
    | Internal role names (spatie/permission) stay stable for code & permissions,
    | but these human-friendly labels are what non-IT users see in the UI. Add an
    | entry here when you introduce a new role; anything missing falls back to a
    | title-cased version of the internal name.
    |
    */
    'labels' => [
        'super_admin' => 'Super Admin',
        'admin' => 'Administrator',
        'chief' => 'Chief',
        'cto' => 'Chief (CTO)',
        'manager' => 'Manager',
        'staff' => 'Staff',
        'member' => 'Staff (Member)',
        'system_analyst' => 'Analyst',
        'programmer' => 'Developer',
        'qa' => 'Quality Assurance',
    ],
];
