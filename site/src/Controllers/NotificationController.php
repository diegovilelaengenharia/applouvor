<?php
namespace App\Controllers;

use App\AuthMiddleware;
use App\Models\Notification;

class NotificationController extends Controller
{
    // Tela 16: Lista de Notificações (com filtro por tipo)
    public function index()
    {
        AuthMiddleware::requireLogin();

        $filter = $_GET['tipo'] ?? 'todas';
        $userId = (int) $_SESSION['user_id'];

        $model = new Notification($this->pdo);
        $notifications = $model->forUser($userId, $filter);
        $unreadCount   = $model->countUnread($userId);

        $this->render('app/notificacoes', [
            'notifications' => $notifications,
            'unreadCount'   => $unreadCount,
            'filter'        => $filter,
        ]);
    }

    // Marcar uma notificação como lida
    public function markRead(int $id)
    {
        AuthMiddleware::requireLogin();
        csrf_verify();

        $model = new Notification($this->pdo);
        $model->markRead($id, (int) $_SESSION['user_id']);

        // Se vier com redirect, vai para lá; senão volta para notificações
        $redirect = $_POST['redirect'] ?? '/notificacoes';
        $this->redirect($redirect);
    }

    // Marcar todas como lidas
    public function markAllRead()
    {
        AuthMiddleware::requireLogin();
        csrf_verify();

        $model = new Notification($this->pdo);
        $model->markAllRead((int) $_SESSION['user_id']);

        $_SESSION['flash']['success'] = 'Todas as notificações marcadas como lidas.';
        $this->redirect('/notificacoes');
    }
}
