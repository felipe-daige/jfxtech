<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $mousepads = [
            160 => [ // Artisan FX Hien
                'superficie' => 'Cloth FX — Velocidade Média (SOFT)',
                'dimensoes'  => '490 × 420 × 4 mm',
                'base'       => 'Borracha antiderrapante',
                'peso'       => '470 g',
                'garantia'   => '1 ano',
            ],
            159 => [ // Artisan FX Key-83
                'superficie' => 'Cloth FX — Velocidade Média',
                'dimensoes'  => '490 × 420 × 4 mm',
                'base'       => 'Borracha antiderrapante',
                'peso'       => '470 g',
                'garantia'   => '1 ano',
            ],
            161 => [ // Artisan FX Raiden
                'superficie' => 'Cloth FX — Alta Velocidade (HARD)',
                'dimensoes'  => '490 × 420 × 4 mm',
                'base'       => 'Borracha antiderrapante',
                'peso'       => '470 g',
                'garantia'   => '1 ano',
            ],
            155 => [ // Artisan FX Type-99
                'superficie' => 'Cloth FX — Balanceado',
                'dimensoes'  => '490 × 420 × 4 mm',
                'base'       => 'Borracha antiderrapante',
                'peso'       => '470 g',
                'garantia'   => '1 ano',
            ],
            154 => [ // Artisan NINJA FX Zero
                'superficie' => 'Cloth FX — Alta Controle (XSOFT)',
                'dimensoes'  => '490 × 420 × 4 mm',
                'base'       => 'Borracha antiderrapante',
                'peso'       => '470 g',
                'garantia'   => '1 ano',
            ],
            150 => [ // Pad Pulsar x LGG Hyperion Gaming Soft XL
                'superficie' => 'Cloth Soft — Velocidade Média',
                'dimensoes'  => '900 × 400 × 3 mm',
                'base'       => 'Borracha antiderrapante',
                'peso'       => '530 g',
                'garantia'   => '1 ano',
            ],
            148 => [ // Pulsar ES2 eSports RRQ Edition
                'superficie' => 'Cloth — Velocidade Balanceada',
                'dimensoes'  => '490 × 420 × 3 mm',
                'base'       => 'Borracha antiderrapante',
                'peso'       => '350 g',
                'garantia'   => '1 ano',
            ],
            151 => [ // Pulsar Paracontrol V2
                'superficie' => 'Cloth — Alta Controle',
                'dimensoes'  => '490 × 420 × 3 mm',
                'base'       => 'Borracha antiderrapante',
                'peso'       => '380 g',
                'garantia'   => '1 ano',
            ],
            157 => [ // Pulsar Superglide v3 XL
                'superficie' => 'Vidro — Ultra-Velocidade',
                'dimensoes'  => '490 × 420 × 2 mm',
                'base'       => 'Borracha antiderrapante',
                'peso'       => '600 g',
                'garantia'   => '1 ano',
            ],
            123 => [ // Pulsar Superglide V3 XL Sgpxl
                'superficie' => 'Vidro — Ultra-Velocidade',
                'dimensoes'  => '490 × 420 × 2 mm',
                'base'       => 'Borracha antiderrapante',
                'peso'       => '600 g',
                'garantia'   => '1 ano',
            ],
            149 => [ // SteelSeries QcK Performance L-Control
                'superficie' => 'Cloth — Alta Controle',
                'dimensoes'  => '450 × 400 × 3 mm',
                'base'       => 'Borracha antiderrapante',
                'peso'       => '350 g',
                'garantia'   => '2 anos',
            ],
            156 => [ // Wallhack SP-005
                'superficie' => 'Cloth — Velocidade Balanceada',
                'dimensoes'  => '900 × 400 × 3 mm',
                'base'       => 'Borracha antiderrapante',
                'peso'       => '500 g',
                'garantia'   => '1 ano',
            ],
        ];

        foreach ($mousepads as $id => $specs) {
            DB::table('produtos')->where('id', $id)->update(['specs' => json_encode($specs)]);
        }
    }

    public function down(): void
    {
        DB::table('produtos')
            ->whereIn('id', [160, 159, 161, 155, 154, 150, 148, 151, 157, 123, 149, 156])
            ->update(['specs' => null]);
    }
};
