<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\User;
use App\Mail\TemporaryPasswordMail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GuestToUserConversionService
{
    public function convert(Pedido $pedido): void
    {
        if ($pedido->checkout_mode !== 'guest' || empty($pedido->customer_email)) {
            return;
        }

        $user = User::where('email', $pedido->customer_email)->first();

        if (!$user) {
            $tempPassword = Str::random(8);

            DB::transaction(function () use ($pedido, $tempPassword, &$user) {
                // Ensure name and phone fallbacks in case customer_name is missing
                $name = !empty($pedido->customer_name) ? $pedido->customer_name : 'Cliente ' . $pedido->id;
                
                $user = User::create([
                    'name' => $name,
                    'email' => $pedido->customer_email,
                    'phone' => $pedido->customer_phone,
                    'password' => Hash::make($tempPassword),
                ]);

                $this->linkOrderToUser($pedido, $user->id);
            });

            try {
                Mail::to($user->email)->queue(new TemporaryPasswordMail($user, $tempPassword));
                Log::info('Conta automática criada para guest e senha enviada', ['pedido_id' => $pedido->id, 'user_id' => $user->id]);
            } catch (\Throwable $e) {
                Log::error('Falha ao enviar senha temporária', [
                    'user_id' => $user->id,
                    'pedido_id' => $pedido->id,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            // Conta já existe. Apenas vincula para exibir no histórico (Não envia nova senha).
            $this->linkOrderToUser($pedido, $user->id);
            Log::info('Pedido guest vinculado a conta existente', ['pedido_id' => $pedido->id, 'user_id' => $user->id]);
        }
    }

    protected function linkOrderToUser(Pedido $pedido, int $userId): void
    {
        $pedido->update([
            'user_id' => $userId,
            'checkout_mode' => 'authenticated',
        ]);

        if ($pedido->endereco) {
            $pedido->endereco->update(['user_id' => $userId]);
        }
    }
}
