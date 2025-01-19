<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestAlegraSimple extends Command
{
    protected $signature = 'alegra:test-simple';
    protected $description = 'Test simple de conexión con Alegra API';

    public function handle()
    {
        $this->info('Probando conexión con Alegra API...');

        try {
            $baseUrl = config('alegra.base_url');
            $auth = base64_encode(config('alegra.user') . ':' . config('alegra.token'));

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $auth,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->get($baseUrl . '/company');

            if ($response->successful()) {
                $this->info('✓ Conexión exitosa!');
                $this->info(json_encode($response->json(), JSON_PRETTY_PRINT));
            } else {
                $this->error('✗ Error de conexión: ' . $response->status());
                $this->error($response->body());
            }
        } catch (\Exception $e) {
            $this->error('✗ Error: ' . $e->getMessage());
        }
    }
} 