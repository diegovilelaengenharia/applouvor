<?php
// admin/google_calendar_config.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';
require_once '../includes/google_calendar.php';

$googleCal = new GoogleCalendarIntegration($pdo, $_SESSION['user_id']);
$isConnected = $googleCal->isConnected();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['disconnect'])) {
        $googleCal->disconnect();
        header("Location: google_calendar_config.php");
        exit;
    }
}

// Processar callback OAuth2
if (isset($_GET['code'])) {
    try {
        $googleCal->exchangeCodeForTokens($_GET['code']);
        $success = "Conta Google conectada com sucesso!";
        $isConnected = true;
    } catch (Exception $e) {
        $error = "Erro ao conectar: " . $e->getMessage();
    }
}

renderAppHeader('Google Calendar');
renderPageHeader('Integração Google Calendar', 'Sincronize eventos automaticamente');
?>

<link rel="stylesheet" href="../assets/css/pages/config-pages.css">

<div class="config-container">
    <?php if (isset($success)): ?>
        <div style="background: #d1fae5; border: 1px solid #10b981; color: #065f46; padding: 12px; border-radius: 10px; margin-bottom: 16px;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div style="background: var(--rose-100); border: 1px solid var(--rose-500); color: var(--rose-700); padding: 12px; border-radius: 10px; margin-bottom: 16px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <div class="info-card">
        <div class="card-title">
            <i data-lucide="info" style="width: 18px;"></i>
            Status da Integração
        </div>
        
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
            <?php if ($isConnected): ?>
                <span class="status-badge status-connected">
                    <i data-lucide="check-circle" style="width: 16px;"></i>
                    Conectado
                </span>
                <span style="color: var(--text-muted); font-size: 0.875rem;">
                    Sincronização automática ativa
                </span>
            <?php else: ?>
                <span class="status-badge status-disconnected">
                    <i data-lucide="x-circle" style="width: 16px;"></i>
                    Não Conectado
                </span>
            <?php endif; ?>
        </div>
        
        <?php if ($isConnected): ?>
            <div class="info-box">
                <h4>✅ Integração Ativa</h4>
                <p>Os eventos criados no App Louvor serão automaticamente sincronizados com o seu Google Calendar.</p>
            </div>
            
            <form method="POST">
                <button type="submit" name="disconnect" class="btn-danger" style="width: 100%;">
                    <i data-lucide="unlink" style="width: 16px;"></i>
                    Desconectar Google Calendar
                </button>
            </form>
        <?php else: ?>
            <div class="warning-box">
                <strong>⚠️ Credenciais não configuradas</strong>
                <p>Para ativar a sincronização com Google Calendar, você precisa configurar as credenciais OAuth2 no arquivo `includes/config.php`:</p>
                <ul style="margin-top: 8px;">
                    <li>GOOGLE_CLIENT_ID</li>
                    <li>GOOGLE_CLIENT_SECRET</li>
                    <li>GOOGLE_REDIRECT_URI</li>
                </ul>
            </div>
            
            <?php 
            // Verificar se as credenciais estão configuradas
            $hasCredentials = defined('GOOGLE_CLIENT_ID') && !empty(GOOGLE_CLIENT_ID);
            if ($hasCredentials):
                try {
                    $authUrl = $googleCal->getAuthUrl();
            ?>
                <a href="<?= htmlspecialchars($authUrl) ?>" class="btn-primary" style="width: 100%; text-decoration: none;">
                    <i data-lucide="link" style="width: 16px;"></i>
                    Conectar com Google Calendar
                </a>
            <?php 
                } catch (Exception $e) {
                    echo "<p style='color: var(--rose-700); text-align: center;'>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            else: 
            ?>
                <button class="btn-secondary" style="width: 100%;" disabled>
                    <i data-lucide="alert-circle" style="width: 16px;"></i>
                    Configure as credenciais primeiro
                </button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <div class="info-card">
        <div class="card-title">
            <i data-lucide="book-open" style="width: 18px;"></i>
            Como Funciona
        </div>
        
        <div style="color: var(--text-secondary); line-height: 1.6; font-size: 0.875rem;">
            <p style="margin-bottom: 12px;">
                Ao conectar sua conta Google, os eventos criados no App Louvor serão automaticamente adicionados ao seu Google Calendar.
            </p>
            
            <p style="margin-bottom: 8px;"><strong>Recursos:</strong></p>
            <ul style="margin: 0 0 12px; padding-left: 20px;">
                <li>Sincronização automática de eventos novos</li>
                <li>Atualização de eventos modificados</li>
                <li>Remoção de eventos excluídos</li>
                <li>Cores personalizadas por tipo de evento</li>
            </ul>
            
            <p style="margin-bottom: 8px;"><strong>Observações:</strong></p>
            <ul style="margin: 0; padding-left: 20px;">
                <li>Apenas eventos que você criar serão sincronizados</li>
                <li>A sincronização ocorre automaticamente a cada 30 minutos</li>
                <li>Você pode desconectar a qualquer momento</li>
            </ul>
        </div>
    </div>
    
    <a href="agenda.php" class="btn-secondary" style="width: 100%; text-decoration: none;">
        <i data-lucide="arrow-left" style="width: 16px;"></i>
        Voltar para Agenda
    </a>
</div>

<script>
lucide.createIcons();
</script>

<?php renderAppFooter(); ?>
