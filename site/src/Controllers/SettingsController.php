<?php

namespace App\Controllers;

use App\AuthMiddleware;
use App\Models\UserSetting;

class SettingsController extends Controller
{
    /** Chaves de preferências de notificação e seus rótulos (Tela 35). */
    public const NOTIF_KEYS = [
        'Escalas' => [
            'notif_nova_escala'      => ['Nova escala designada', 'Quando você for incluído em uma escala'],
            'notif_lembrete_culto'   => ['Lembrete de culto', 'Aviso no dia do culto/ensaio'],
            'notif_confirmacao'      => ['Confirmação pendente', 'Quando faltar confirmar presença'],
        ],
        'Repertório' => [
            'notif_nova_musica'      => ['Nova música adicionada', 'Quando uma música entra no repertório'],
            'notif_setlist'          => ['Setlist publicada', 'Quando o setlist de uma escala é definido'],
        ],
        'Comunidade' => [
            'notif_aviso'            => ['Aviso da liderança', 'Novos avisos importantes'],
            'notif_oracao'           => ['Pedido de oração', 'Novos pedidos no mural de oração'],
            'notif_aniversario'      => ['Aniversários', 'Aniversariantes do ministério'],
        ],
        'Mensagens' => [
            'notif_mensagens'        => ['Novas mensagens', 'Mensagens no mural do ministério'],
        ],
    ];

    /**
     * Tela 13: Configurações (hub).
     */
    public function index()
    {
        AuthMiddleware::requireLogin();

        $settingsModel = new UserSetting($this->pdo);
        $settings = $settingsModel->allFor((int) $_SESSION['user_id']);

        $this->render('app/configuracoes', ['settings' => $settings]);
    }

    /**
     * Tela 35: Preferências de Notificação (GET).
     */
    public function notifications()
    {
        AuthMiddleware::requireLogin();

        $settingsModel = new UserSetting($this->pdo);
        $settings = $settingsModel->allFor((int) $_SESSION['user_id']);

        $this->render('app/notif-prefs', [
            'settings' => $settings,
            'groups'   => self::NOTIF_KEYS,
        ]);
    }

    /**
     * Tela 35: Salvar preferências (POST).
     */
    public function updateNotifications()
    {
        AuthMiddleware::requireLogin();
        csrf_verify();

        $userId = (int) $_SESSION['user_id'];
        $settingsModel = new UserSetting($this->pdo);

        // Master + canal
        $settingsModel->set($userId, 'notif_paused', isset($_POST['notif_paused']) ? '1' : '0');
        $channel = in_array($_POST['notif_channel'] ?? 'push', ['push', 'email'], true) ? $_POST['notif_channel'] : 'push';
        $settingsModel->set($userId, 'notif_channel', $channel);

        // Toggles por categoria (checkbox ausente = '0')
        foreach (self::NOTIF_KEYS as $keys) {
            foreach ($keys as $key => $_label) {
                $settingsModel->set($userId, $key, isset($_POST[$key]) ? '1' : '0');
            }
        }

        $_SESSION['flash']['success'] = 'Preferências de notificação salvas!';
        $this->redirect('/configuracoes/notificacoes');
    }
}
