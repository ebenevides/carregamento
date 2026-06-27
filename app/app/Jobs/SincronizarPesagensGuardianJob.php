<?php

namespace App\Jobs;

use App\Domain\Integrations\Guardian\Services\GuardianService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SincronizarPesagensGuardianJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function handle(GuardianService $guardian): void
    {
        $atualizadas = $guardian->sincronizarTodasPesagens();

        Log::info("Guardian sync pesagens: {$atualizadas} ordem(ns) atualizadas.");
    }
}
