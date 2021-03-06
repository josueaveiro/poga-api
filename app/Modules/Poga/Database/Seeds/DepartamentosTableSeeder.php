<?php

namespace Raffles\Modules\Poga\Database\Seeds;

use Illuminate\Database\Seeder;

class DepartamentosTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::unprepared(file_get_contents(module_path('poga', 'Database/departamentos.sql')));
    }
}
