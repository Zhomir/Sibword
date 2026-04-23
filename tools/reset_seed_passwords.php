<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$emails = [
    'admin@sibword.ru',
    'bairma.teacher@sibword.ru',
    'seseg.teacher@sibword.ru',
    'bato.student@sibword.ru',
    'dulma.student@sibword.ru',
    'ayur.student@sibword.ru',
    'nomina.student@sibword.ru',
];

DB::table('users')
    ->whereIn('email', $emails)
    ->update([
        'password' => Hash::make('password123'),
        'updated_at' => now(),
    ]);

echo "Seed users password has been reset to: password123" . PHP_EOL;
