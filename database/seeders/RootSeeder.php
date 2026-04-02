<?php

namespace Init\Commerce\Cart\Database\Seeders;

use Init\Core\Database\PackageSeeder;

class RootSeeder extends PackageSeeder
{
    public static function dependencies(): array
    {
        return ['init/commerce-catalog'];
    }

    public function run(): void
    {
        $this->call([
            //
        ]);
    }
}
