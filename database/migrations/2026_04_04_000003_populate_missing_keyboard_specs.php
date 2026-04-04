<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $teclados = [
            50 => [ // Wooting 60HE+ RGB
                'layout'       => '60%',
                'switches'     => 'Lekker Linear (Hall Effect Magnético, 0,1–4,0 mm)',
                'polling_rate' => '1.000 Hz',
                'conexao'      => 'USB-C com fio',
                'cabo'         => 'USB-C removível',
                'iluminacao'   => 'RGB por tecla',
                'peso'         => '695 g',
                'dimensoes'    => '295 × 105 × 40 mm',
                'garantia'     => '1 ano',
            ],
            51 => [ // Wooting 80He + RGB
                'layout'       => '75% (80 teclas)',
                'switches'     => 'Lekker Linear (Hall Effect Magnético, 0,1–4,0 mm)',
                'polling_rate' => '1.000 Hz',
                'conexao'      => 'USB-C com fio',
                'cabo'         => 'USB-C removível',
                'iluminacao'   => 'RGB por tecla',
                'peso'         => '772 g',
                'dimensoes'    => '322 × 112 × 40 mm',
                'garantia'     => '1 ano',
            ],
            52 => [ // Apex Pro TKL Gen3 RGB Wireless
                'layout'       => 'TKL (Tenkeyless)',
                'switches'     => 'OmniPoint 2.0 HE Ajustável (Hall Effect Magnético, 0,1–4,0 mm)',
                'polling_rate' => '8.000 Hz',
                'conexao'      => '2,4 GHz sem fio / Bluetooth / USB-C',
                'cabo'         => 'USB-C trançado removível',
                'iluminacao'   => 'RGB por tecla (OmniRGB)',
                'peso'         => '1.010 g',
                'dimensoes'    => '360 × 132 × 40 mm',
                'garantia'     => '1 ano',
            ],
            53 => [ // Razer Huntsman V3 Pro Mini
                'layout'       => '60% (65 teclas)',
                'switches'     => 'Razer Analog Optical (0,1–4,0 mm)',
                'polling_rate' => '8.000 Hz',
                'conexao'      => 'USB-C com fio',
                'cabo'         => 'USB-C com fio, 2 m',
                'iluminacao'   => 'Razer Chroma RGB por tecla',
                'peso'         => '520 g',
                'dimensoes'    => '295 × 105 × 38 mm',
                'garantia'     => '2 anos',
            ],
            122 => [ // Logitech G Pro X TKL Lightspeed Wireless
                'layout'       => 'TKL (Tenkeyless)',
                'switches'     => 'GX Red Linear ou GX Blue Clicky (mecânico)',
                'polling_rate' => '1.000 Hz',
                'conexao'      => 'LIGHTSPEED 2,4 GHz / Bluetooth / USB-C',
                'cabo'         => 'USB-C removível',
                'iluminacao'   => 'LIGHTSYNC RGB por tecla',
                'peso'         => '880 g',
                'dimensoes'    => '360 × 132 × 38 mm',
                'garantia'     => '2 anos',
            ],
            135 => [ // WLMouse Ying75
                'layout'       => '75%',
                'switches'     => 'Hall Effect Magnético (ajustável)',
                'polling_rate' => '8.000 Hz',
                'conexao'      => '2,4 GHz sem fio / USB-C',
                'cabo'         => 'USB-C',
                'iluminacao'   => 'RGB por tecla',
                'peso'         => '700 g',
                'dimensoes'    => '325 × 112 × 38 mm',
                'garantia'     => '1 ano',
            ],
            136 => [ // SteelSeries RGB Apex Pro TKL Gen3 White
                'layout'       => 'TKL (Tenkeyless)',
                'switches'     => 'OmniPoint 2.0 HE Ajustável (Hall Effect Magnético, 0,1–4,0 mm)',
                'polling_rate' => '8.000 Hz',
                'conexao'      => 'USB-C com fio',
                'cabo'         => 'USB-C trançado removível, 2 m',
                'iluminacao'   => 'RGB por tecla (OmniRGB)',
                'peso'         => '876 g (sem cabo)',
                'dimensoes'    => '360 × 132 × 40 mm',
                'garantia'     => '1 ano',
            ],
            137 => [ // SteelSeries 60 RGB Apex Pro Mini Gen 3 Black
                'layout'       => '60%',
                'switches'     => 'OmniPoint 2.0 HE Ajustável (Hall Effect Magnético, 0,1–4,0 mm)',
                'polling_rate' => '8.000 Hz',
                'conexao'      => 'USB-C com fio',
                'cabo'         => 'USB-C trançado removível',
                'iluminacao'   => 'RGB por tecla',
                'peso'         => '700 g',
                'dimensoes'    => '295 × 105 × 40 mm',
                'garantia'     => '1 ano',
            ],
            139 => [ // Attack Shark X68HE
                'layout'       => '65% (68 teclas)',
                'switches'     => 'Hall Effect Magnético',
                'polling_rate' => '8.000 Hz',
                'conexao'      => 'USB-C com fio',
                'cabo'         => 'USB-C Type-C',
                'iluminacao'   => 'RGB por tecla',
                'peso'         => '600 g',
                'dimensoes'    => '315 × 105 × 38 mm',
                'garantia'     => '1 ano',
            ],
        ];

        foreach ($teclados as $id => $specs) {
            DB::table('produtos')->where('id', $id)->update(['specs' => json_encode($specs)]);
        }
    }

    public function down(): void
    {
        DB::table('produtos')
            ->whereIn('id', [50, 51, 52, 53, 122, 135, 136, 137, 139])
            ->update(['specs' => null]);
    }
};
