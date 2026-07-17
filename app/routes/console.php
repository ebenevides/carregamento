<?php

use App\Jobs\SincronizarFilaGuardianJob;
use App\Jobs\SincronizarPesagensGuardianJob;
use App\Jobs\SincronizarTarasGuardianJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Guardian: sincronizar taras a cada 2 minutos (ordens CRIADO com ticket sem tara)
Schedule::job(new SincronizarTarasGuardianJob)->everyTwoMinutes()
    ->name('guardian:sync-taras')
    ->withoutOverlapping();

// Guardian: sincronizar pesagens a cada 2 minutos (ordens AGUARDANDO_PESAGEM_FINAL)
Schedule::job(new SincronizarPesagensGuardianJob)->everyTwoMinutes()
    ->name('guardian:sync-pesagens')
    ->withoutOverlapping();

// Guardian: sincronizar fila a cada 2 minutos (ordens TARA_REALIZADA liberadas na fila do Guardian
// entram em AGUARDANDO_CARREGAMENTO — ver DT-014)
Schedule::job(new SincronizarFilaGuardianJob)->everyTwoMinutes()
    ->name('guardian:sync-fila')
    ->withoutOverlapping();
