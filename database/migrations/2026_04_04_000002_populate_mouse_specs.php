<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('produtos')
            ->where('id', 158)
            ->update([
                'specs' => json_encode([
                    'sensor'       => 'Pulsar XS-1 Optical',
                    'dpi_maximo'   => '32.000 DPI',
                    'switches'     => 'Óptico Pulsar (100M cliques)',
                    'peso'         => '47 g',
                    'conexao'      => '2,4 GHz Wireless 8K + USB-C',
                    'polling_rate' => '8.000 Hz',
                    'dimensoes'    => '116 × 62 × 37 mm',
                    'cabo'         => 'Superflex Paracord USB-C 1,8 m',
                    'iluminacao'   => 'Sem RGB',
                    'garantia'     => '2 anos',
                ]),
            ]);
    }

    public function down(): void
    {
        DB::table('produtos')->where('id', 158)->update(['specs' => null]);
    }
};
