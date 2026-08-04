<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Who is the user logged in as? The user mentioned "admin saat ini masih belum tampil"
// Meaning they are probably logged in as admin@example.com still.
// Let's dump all users and their roles to see what's in the DB.
$users = \App\Models\User::with('roles')->get();
foreach($users as $u) {
    echo $u->email . " => " . $u->roles->pluck('name')->implode(', ') . "\n";
}
