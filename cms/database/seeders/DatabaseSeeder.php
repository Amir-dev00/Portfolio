<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Intentionally no default admin user — create the owner via
        // tools/make-owner-hash.php + phpMyAdmin (see INSTALL-CPANEL.md).
        $this->call(PortfolioSeeder::class);
    }
}
