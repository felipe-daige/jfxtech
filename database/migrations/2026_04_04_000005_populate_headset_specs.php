<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $headsets = [
            141 => [ // SteelSeries Arctis Nova Elite
                'drivers'    => '40mm Neodymium',
                'frequencia' => '20 Hz – 22.000 Hz',
                'microfone'  => 'Retrátil bidirecional ClearCast',
                'conexao'    => 'USB-A / USB-C com fio',
                'cabo'       => 'USB-A + USB-C, 1,5 m',
                'iluminacao' => 'RGB',
                'peso'       => '338 g',
                'garantia'   => '1 ano',
            ],
            142 => [ // SteelSeries Arctis Nova Pro Inalámbrico
                'drivers'    => '40mm Neodymium',
                'frequencia' => '20 Hz – 22.000 Hz',
                'microfone'  => 'Retrátil bidirecional ClearCast Gen 2',
                'bateria'    => '22 horas (hot-swap)',
                'conexao'    => '2,4 GHz sem fio / Bluetooth / USB-C',
                'cabo'       => 'USB-C (carga)',
                'iluminacao' => 'RGB',
                'peso'       => '338 g',
                'garantia'   => '1 ano',
            ],
            143 => [ // Logitech G Pro X 7.1 Dolby Inalámbrico Negro
                'drivers'    => 'PRO-G 50mm',
                'frequencia' => '20 Hz – 20.000 Hz',
                'microfone'  => 'Blue VO!CE, destacável',
                'bateria'    => '50 horas',
                'conexao'    => 'LIGHTSPEED 2,4 GHz / Bluetooth',
                'cabo'       => 'USB-C (carga)',
                'iluminacao' => 'Sem RGB',
                'peso'       => '345 g',
                'garantia'   => '2 anos',
            ],
            146 => [ // Sennheiser Momentum 4 Wireless Blue Denim
                'drivers'    => '42mm',
                'frequencia' => '6 Hz – 22.000 Hz',
                'microfone'  => 'Integrado, redução de ruído',
                'bateria'    => '60 horas',
                'conexao'    => 'Bluetooth 5.2 / P2 3,5 mm',
                'cabo'       => 'USB-C (carga) + P2 3,5 mm',
                'iluminacao' => 'Sem RGB',
                'peso'       => '293 g',
                'garantia'   => '2 anos',
            ],
            147 => [ // Razer Blackshark V3 Pro CS2 Edition
                'drivers'    => '50mm TriForce Titanium',
                'frequencia' => '12 Hz – 28.000 Hz',
                'microfone'  => 'HyperClear Super-Wideband, destacável',
                'bateria'    => '70 horas',
                'conexao'    => 'HyperSpeed 2,4 GHz / Bluetooth / P2 3,5 mm',
                'cabo'       => 'USB-C (carga) + P2 3,5 mm',
                'iluminacao' => 'Sem RGB',
                'peso'       => '320 g',
                'garantia'   => '2 anos',
            ],
        ];

        foreach ($headsets as $id => $specs) {
            DB::table('produtos')->where('id', $id)->update(['specs' => json_encode($specs)]);
        }
    }

    public function down(): void
    {
        DB::table('produtos')
            ->whereIn('id', [141, 142, 143, 146, 147])
            ->update(['specs' => null]);
    }
};
