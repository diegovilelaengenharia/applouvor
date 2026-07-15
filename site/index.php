<?php
/**
 * index.php — ponto de entrada (FASE 01, ciclo v7)
 *
 * Acesso direto a `/` (sem passar pelo rewrite do .htaccess, ex.: `php -S` sem router
 * embutido) cai aqui e só delega para o front controller de verdade.
 *
 * Ver: /diag.php — smoke test da conexão com o banco.
 * Ver: .governanca/fases/FASE-01-PLANO.md
 */

declare(strict_types=1);

require __DIR__ . '/router.php';
