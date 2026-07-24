<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Lending\Eloquent\MemberRecord;
use Illuminate\Database\Seeder;

class LendingSeeder extends Seeder
{
    public function run(): void
    {
        // Readable IDs so the URLs are pleasant while developing.
        // Real systems use UUIDs — MemberId::generate() gives you one.
        foreach ([
            'ana' => 'Ana Petrovska',
            'bojan' => 'Bojan Ristov',
            'cveta' => 'Cveta Nikolova',
        ] as $id => $name) {
            MemberRecord::updateOrCreate(['id' => $id], ['name' => $name]);
        }
    }
}
