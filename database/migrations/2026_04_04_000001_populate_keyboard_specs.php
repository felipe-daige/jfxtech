<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $keyboards = [
            'SteelSeries RGB Apex Pro TKL Gen 3 White Ingles' => [
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
            'SteelSeries 60 RGB Apex Pro Mini Gen 3 Black Ingles' => [
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
            'Pulsar Pcmk 3 HE 60 Bruce Lee 85TH RGB 8K USB C Switch Magnetic White' => [
                'layout'       => '60% (Edição Bruce Lee 85º Aniversário)',
                'switches'     => 'Pulsar HE (Hall Effect Magnético)',
                'polling_rate' => '8.000 Hz',
                'conexao'      => 'USB-C com fio',
                'cabo'         => 'USB-C Type-C',
                'iluminacao'   => 'RGB por tecla',
                'peso'         => '550 g',
                'dimensoes'    => '295 × 105 × 38 mm',
                'garantia'     => '1 ano',
            ],
            'Attack Shark X68 HE 60 RGB 8K USB C Switch Magnetic Ingles' => [
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
            'Pulsar Pcmk 3 HE 60 RGB Hall Effect Magnetic Sw Ing White' => [
                'layout'       => '60%',
                'switches'     => 'Pulsar HE (Hall Effect Magnético)',
                'polling_rate' => '8.000 Hz',
                'conexao'      => 'USB-C com fio',
                'cabo'         => 'USB-C Type-C',
                'iluminacao'   => 'RGB por tecla',
                'peso'         => '550 g',
                'dimensoes'    => '295 × 105 × 38 mm',
                'garantia'     => '1 ano',
            ],
        ];

        foreach ($keyboards as $nome => $specs) {
            DB::table('produtos')
                ->where('nome', $nome)
                ->update(['specs' => json_encode($specs)]);
        }
    }

    public function down(): void
    {
        $names = [
            'SteelSeries RGB Apex Pro TKL Gen 3 White Ingles',
            'SteelSeries 60 RGB Apex Pro Mini Gen 3 Black Ingles',
            'Pulsar Pcmk 3 HE 60 Bruce Lee 85TH RGB 8K USB C Switch Magnetic White',
            'Attack Shark X68 HE 60 RGB 8K USB C Switch Magnetic Ingles',
            'Pulsar Pcmk 3 HE 60 RGB Hall Effect Magnetic Sw Ing White',
        ];

        DB::table('produtos')
            ->whereIn('nome', $names)
            ->update(['specs' => json_encode([])]);
    }
};
