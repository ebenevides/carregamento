<?php

return [
    App\Providers\AppServiceProvider::class,
    // Telescope é require-dev: só registra o provider se o pacote estiver
    // instalado (evita quebrar boot em builds --no-dev, ex.: imagem Docker).
    ...(class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)
        ? [App\Providers\TelescopeServiceProvider::class]
        : []),
];
