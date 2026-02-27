<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CepController extends Controller
{
    /**
     * Busca informações do CEP usando a API do ViaCEP
     */
    public function buscarCep($cep)
    {
        // Remove caracteres não numéricos do CEP
        $cep = preg_replace('/\D/', '', $cep);
        
        // Valida se o CEP tem 8 dígitos
        if (strlen($cep) !== 8) {
            return response()->json(['erro' => 'CEP deve ter 8 dígitos'], 400);
        }
        
        try {
            // Faz a requisição para a API do ViaCEP
            $response = Http::timeout(10)->get("https://viacep.com.br/ws/{$cep}/json/");
            
            if ($response->failed()) {
                return response()->json(['erro' => 'Erro ao consultar CEP'], 500);
            }
            
            $data = $response->json();
            
            // Verifica se o CEP foi encontrado
            if (isset($data['erro'])) {
                return response()->json(['erro' => 'CEP não encontrado'], 404);
            }
            
            // Retorna os dados formatados
            return response()->json([
                'success' => true,
                'cep' => $data['cep'],
                'logradouro' => $data['logradouro'],
                'complemento' => $data['complemento'] ?? '',
                'bairro' => $data['bairro'],
                'localidade' => $data['localidade'],
                'uf' => $data['uf']
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['erro' => 'Erro interno do servidor'], 500);
        }
    }
}
