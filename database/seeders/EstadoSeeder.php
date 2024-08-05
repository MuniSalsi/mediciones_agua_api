<?php

namespace Database\Seeders;

use App\Models\Estado;
use Illuminate\Database\Seeder;

class EstadoSeeder extends Seeder
{
    public function run()
    {
        $estados = [
            'Borroso',
            'Con Aire',
            'En Interior',
            'Roto',
            'Sin Instalar',
            'Sin Situación',
            'Tapado',
        ];

        foreach ($estados as $estado) {
            Estado::create([
                'tipo' => $estado
            ]);
        }
    }
}