<?php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AffiliateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'codigo'           => strtoupper(Str::random(8)),
            'commission_type'  => 'percent',
            'commission_value' => null,
            'status'           => 'ativo',
            'pix_key'          => null,
            'bank_info'        => null,
            'approved_at'      => now(),
        ];
    }
}
