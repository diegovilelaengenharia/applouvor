<?php
// router.php - Front Controller Central

// 1. Carrega banco de dados e autoloader
require_once __DIR__ . '/src/config/db.php';

// Carrega os helpers globais de segurança (Fase 2)
require_once __DIR__ . '/src/helpers/auth.php';
require_once __DIR__ . '/src/helpers/csrf.php';
require_once __DIR__ . '/src/helpers/rate_limit.php';

// 2. Instancia o roteador
$router = new App\Router();

// 3. Resolve a rota atual (Apache .htaccess vs PHP CLI Server)
if (php_sapi_name() === 'cli-server') {
    $filePath = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (file_exists($filePath) && !is_dir($filePath)) {
        return false; // Serve o arquivo estático diretamente
    }
    $route = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
} else {
    $route = $_GET['route'] ?? '/';
}

// Garante barra inicial na rota
if (strpos($route, '/') !== 0) {
    $route = '/' . $route;
}

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================
// REGISTRO DE ROTAS
// ============================================================

// Tela de Login e Processamento
$router->get('/', [App\Controllers\LoginController::class, 'index']);
$router->post('/login', [App\Controllers\LoginController::class, 'login']);

// Recuperar Senha (público)
$router->get('/recuperar-senha', [App\Controllers\LoginController::class, 'recover']);

// Logout
$router->get('/logout', [App\Controllers\LoginController::class, 'logout']);

// Dashboard
$router->get('/dashboard', [App\Controllers\DashboardController::class, 'index']);

// Rota de teste da API
$router->get('/api/ping', function() {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ok',
        'message' => 'Front Controller & Banco de Dados Ativos',
        'app' => CHURCH_NAME,
        'version' => APP_VERSION
    ]);
});

// ============================================================
// ESCALAS (Phase 4)
// ============================================================
$router->get('/escalas', [App\Controllers\ScheduleController::class, 'index']);
$router->get('/escalas/(?P<id>\d+)', [App\Controllers\ScheduleController::class, 'show']);
$router->get('/escalas/nova', [App\Controllers\ScheduleController::class, 'create']);
$router->post('/escalas/nova', [App\Controllers\ScheduleController::class, 'store']);
$router->get('/escalas/(?P<id>\d+)/editar', [App\Controllers\ScheduleController::class, 'edit']);
$router->post('/escalas/(?P<id>\d+)/editar', [App\Controllers\ScheduleController::class, 'update']);
$router->post('/escalas/(?P<id>\d+)/status', [App\Controllers\ScheduleController::class, 'updateStatus']);
$router->get('/escalas/(?P<id>\d+)/faltas', [App\Controllers\ScheduleController::class, 'attendance']);
$router->post('/escalas/(?P<id>\d+)/faltas', [App\Controllers\ScheduleController::class, 'storeAttendance']);

// ============================================================
// REPERTÓRIO (Phase 5)
// ============================================================
$router->get('/repertorio', [App\Controllers\SongController::class, 'index']);
$router->get('/musicas/(?P<id>\d+)', [App\Controllers\SongController::class, 'show']);
$router->get('/musicas/nova', [App\Controllers\SongController::class, 'create']);
$router->post('/musicas/nova', [App\Controllers\SongController::class, 'store']);
$router->get('/musicas/(?P<id>\d+)/editar', [App\Controllers\SongController::class, 'edit']);
$router->post('/musicas/(?P<id>\d+)/editar', [App\Controllers\SongController::class, 'update']);
$router->post('/musicas/(?P<id>\d+)/deletar', [App\Controllers\SongController::class, 'destroy']);
$router->get('/musicas/(?P<id>\d+)/cifra', [App\Controllers\SongController::class, 'cifra']);

// ============================================================
// PERFIL (Wave 3)
// ============================================================
$router->get('/perfil', [App\Controllers\ProfileController::class, 'index']);
$router->get('/perfil/editar', [App\Controllers\ProfileController::class, 'edit']);
$router->post('/perfil/editar', [App\Controllers\ProfileController::class, 'update']);
$router->get('/perfil/senha', [App\Controllers\ProfileController::class, 'password']);
$router->post('/perfil/senha', [App\Controllers\ProfileController::class, 'updatePassword']);

// ============================================================
// CONFIGURAÇÕES & NOTIFICAÇÕES (Wave 3)
// ============================================================
$router->get('/configuracoes', [App\Controllers\SettingsController::class, 'index']);
$router->get('/configuracoes/notificacoes', [App\Controllers\SettingsController::class, 'notifications']);
$router->post('/configuracoes/notificacoes', [App\Controllers\SettingsController::class, 'updateNotifications']);

// ============================================================
// INDISPONIBILIDADES (Wave 3)
// ============================================================
$router->get('/indisponibilidades', [App\Controllers\UnavailabilityController::class, 'index']);
$router->post('/indisponibilidades', [App\Controllers\UnavailabilityController::class, 'store']);
$router->post('/indisponibilidades/(?P<id>\d+)/remover', [App\Controllers\UnavailabilityController::class, 'destroy']);

// ============================================================
// PÁGINAS UTILITÁRIAS (Wave 3)
// ============================================================
$router->get('/ajuda', [App\Controllers\PageController::class, 'ajuda']);
$router->get('/onboarding', [App\Controllers\PageController::class, 'onboarding']);
$router->get('/offline', [App\Controllers\PageController::class, 'offline']);

// ============================================================
// AVISOS (Wave 4)
// ============================================================
$router->get('/avisos', [App\Controllers\AvisoController::class, 'index']);
$router->get('/avisos/novo', [App\Controllers\AvisoController::class, 'create']);
$router->post('/avisos/novo', [App\Controllers\AvisoController::class, 'store']);
$router->get('/avisos/(?P<id>\d+)', [App\Controllers\AvisoController::class, 'show']);
$router->post('/avisos/(?P<id>\d+)/excluir', [App\Controllers\AvisoController::class, 'destroy']);

// ============================================================
// NOTIFICAÇÕES (Wave 4)
// ============================================================
$router->get('/notificacoes', [App\Controllers\NotificationController::class, 'index']);
$router->post('/notificacoes/ler-todas', [App\Controllers\NotificationController::class, 'markAllRead']);
$router->post('/notificacoes/(?P<id>\d+)/ler', [App\Controllers\NotificationController::class, 'markRead']);

// ============================================================
// DASHBOARD — Confirmação de presença (Wave 4)
// ============================================================
$router->post('/dashboard/presenca/(?P<id>\d+)', [App\Controllers\DashboardController::class, 'updatePresence']);

// ============================================================
// VIDA ESPIRITUAL — Oração (Wave 4)
// ============================================================
$router->get('/oracao', [App\Controllers\PrayerController::class, 'index']);
$router->get('/oracao/novo', [App\Controllers\PrayerController::class, 'create']);
$router->post('/oracao/novo', [App\Controllers\PrayerController::class, 'store']);
$router->get('/oracao/(?P<id>\d+)', [App\Controllers\PrayerController::class, 'show']);
$router->post('/oracao/(?P<id>\d+)/orar', [App\Controllers\PrayerController::class, 'pray']);
$router->post('/oracao/(?P<id>\d+)/comentar', [App\Controllers\PrayerController::class, 'storeComment']);

// ============================================================
// VIDA ESPIRITUAL — Devocionais (Wave 4)
// ============================================================
$router->get('/devocionais', [App\Controllers\DevotionalController::class, 'index']);
$router->get('/devocionais/(?P<id>\d+)', [App\Controllers\DevotionalController::class, 'show']);
$router->post('/devocionais/(?P<id>\d+)/ler', [App\Controllers\DevotionalController::class, 'markRead']);
$router->post('/devocionais/(?P<id>\d+)/comentarios', [App\Controllers\DevotionalController::class, 'storeComment']);

// ============================================================
// MEMBROS (Wave 4)
// ============================================================
$router->get('/membros', [App\Controllers\MemberController::class, 'index']);
$router->get('/membros/convidar', [App\Controllers\MemberController::class, 'invite']);
$router->post('/membros/convidar', [App\Controllers\MemberController::class, 'storeInvite']);
$router->get('/membros/(?P<id>\d+)', [App\Controllers\MemberController::class, 'show']);

// ============================================================
// RELATÓRIOS & ANIVERSARIANTES (Wave 4)
// ============================================================
$router->get('/relatorios', [App\Controllers\ReportController::class, 'index']);
$router->get('/aniversariantes', [App\Controllers\ReportController::class, 'birthdays']);

// ============================================================
// MINISTÉRIO (Wave 4)
// ============================================================
$router->get('/ministerio', [App\Controllers\MinisterioController::class, 'index']);

// ============================================================
// SUGESTÕES DE MÚSICA (Wave 4)
// ============================================================
$router->get('/sugestoes', [App\Controllers\SuggestionController::class, 'index']);
$router->get('/sugestoes/nova', [App\Controllers\SuggestionController::class, 'create']);
$router->post('/sugestoes/nova', [App\Controllers\SuggestionController::class, 'store']);
$router->post('/sugestoes/(?P<id>\d+)/aprovar', [App\Controllers\SuggestionController::class, 'approve']);
$router->post('/sugestoes/(?P<id>\d+)/recusar', [App\Controllers\SuggestionController::class, 'reject']);

// ============================================================
// PAINEL DO LÍDER (Wave 4)
// ============================================================
$router->get('/lider', [App\Controllers\LiderController::class, 'index']);

// ============================================================
// MENSAGENS (Wave 4)
// ============================================================
$router->get('/mensagens', [App\Controllers\MessageController::class, 'index']);

// ============================================================
// METRÔNOMO (Wave 5)
// ============================================================
$router->get('/metronomo', [App\Controllers\MetronomeController::class, 'index']);

// ============================================================
// ESCALAS — Ao Vivo / Ensaio / Setlist / Sugestão (Wave 5)
// ============================================================
$router->get('/escalas/(?P<id>\d+)/ao-vivo',              [App\Controllers\LiveController::class, 'live']);
$router->get('/escalas/(?P<id>\d+)/ensaio',               [App\Controllers\LiveController::class, 'rehearsal']);
$router->get('/escalas/(?P<id>\d+)/setlist',              [App\Controllers\LiveController::class, 'setlist']);
$router->get('/escalas/(?P<id>\d+)/setlist-sugerida',     [App\Controllers\LiveController::class, 'suggestSetlist']);
$router->post('/escalas/(?P<id>\d+)/setlist-sugerida/salvar', [App\Controllers\LiveController::class, 'saveSetlist']);

// ============================================================
// AUTO-ESCALAÇÃO (Wave 5)
// ============================================================
$router->get('/escalas/auto',             [App\Controllers\AutoScheduleController::class, 'index']);
$router->post('/escalas/auto/gerar',      [App\Controllers\AutoScheduleController::class, 'generate']);
$router->post('/escalas/auto/confirmar',  [App\Controllers\AutoScheduleController::class, 'confirm']);

// ============================================================
// ESTATÍSTICAS DO REPERTÓRIO (Wave 5)
// ============================================================
$router->get('/repertorio/stats', [App\Controllers\StatsController::class, 'repertorio']);

// ============================================================
// LEITURA BÍBLICA (Wave 5)
// ============================================================
$router->get('/leitura',          [App\Controllers\ReadingController::class, 'index']);
$router->post('/leitura/ler',     [App\Controllers\ReadingController::class, 'markRead']);
$router->get('/leitura/planos',   [App\Controllers\ReadingController::class, 'plans']);
$router->post('/leitura/planos',  [App\Controllers\ReadingController::class, 'setPlan']);

// ============================================================
// BUSCA GLOBAL (Wave 6)
// ============================================================
$router->get('/busca', [App\Controllers\SearchController::class, 'index']);

// ============================================================
// AGENDA (Wave 6)
// ============================================================
$router->get('/agenda', [App\Controllers\AgendaController::class, 'index']);

// 4. Despacha a requisição
$router->dispatch($route, $method);
