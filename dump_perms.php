<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$permissions = \Spatie\Permission\Models\Permission::all()->pluck('name')->toArray();
$roles = \Spatie\Permission\Models\Role::all()->pluck('name')->toArray();
echo json_encode([
    'permissions' => $permissions,
    'roles' => $roles
], JSON_PRETTY_PRINT);
