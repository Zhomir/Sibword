<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:sync-sql-schema', function () {
    if (!Schema::hasTable('migrations')) {
        Schema::create('migrations', function ($table) {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
        });
    }

    $migrationFiles = collect(File::files(database_path('migrations')))
        ->map(fn ($file) => pathinfo($file->getFilename(), PATHINFO_FILENAME))
        ->sort()
        ->values();

    $existing = DB::table('migrations')->pluck('migration')->all();
    $toInsert = $migrationFiles->reject(fn ($name) => in_array($name, $existing, true))->values();

    if ($toInsert->isEmpty()) {
        $this->info('Migrations table is already synced with SQL-first schema.');
        return self::SUCCESS;
    }

    $batch = ((int) DB::table('migrations')->max('batch')) + 1;
    $rows = $toInsert->map(fn ($name) => [
        'migration' => $name,
        'batch' => $batch,
    ])->all();

    DB::table('migrations')->insert($rows);

    $this->info('Synced migrations: '.$toInsert->count());
    return self::SUCCESS;
})->purpose('Mark existing migration files as executed for SQL-first databases');
