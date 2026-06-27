<?php

namespace App\Jobs;

use App\Domain\Integrations\Guardian\Services\GuardianService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SincronizarTarasGuardianJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function handle(GuardianService $guardian): void
    {
        $atualizadas = $guardian->sincronizarTodasTaras();

        Log::info("Guardian sync taras: {$atualizadas} ordem(ns) atualizadas.");
    }
}
