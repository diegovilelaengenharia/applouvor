<?php
/**
 * Sistema de Notificações
 * Classe para gerenciar notificações do sistema
 */

require_once __DIR__ . '/web_push_helper.php';

class NotificationSystem {
    // ... (rest of class)

    /**
     * Criar notificação
     */
    public function create($userId, $type, $title, $message = null, $data = null, $link = null) {
        try {
            // Verificar se usuário tem preferência ativa para este tipo
            if (!$this->isTypeEnabled($userId, $type)) {
                return false;
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, data, link)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $jsonData = $data ? json_encode($data) : null;
            
            $result = $stmt->execute([$userId, $type, $title, $message, $jsonData, $link]);
            
            if ($result) {
                // Enviar Push Notification em background (tentativa)
                $this->sendPushNotification($userId, $title, $message, $link, $type);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Erro ao criar notificação: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Criar notificação para múltiplos usuários
     */
    public function createForMultiple($userIds, $type, $title, $message = null, $data = null, $link = null) {
        $count = 0;
        foreach ($userIds as $userId) {
            if ($this->create($userId, $type, $title, $message, $data, $link)) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * Criar notificação para todos os líderes
     */
    public function createForLeaders($type, $title, $message = null, $data = null, $link = null) {
        try {
            $stmt = $this->pdo->query("SELECT id FROM users WHERE role = 'admin'");
            $leaders = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $this->createForMultiple($leaders, $type, $title, $message, $data, $link);
        } catch (PDOException $e) {
            error_log("Erro ao buscar líderes: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Listar notificações do usuário
     */
    public function getByUser($userId, $limit = 50, $offset = 0, $unreadOnly = false) {
        try {
            $sql = "
                SELECT * FROM notifications
                WHERE user_id = ?
            ";
            
            if ($unreadOnly) {
                $sql .= " AND is_read = FALSE";
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $limit, $offset]);
            
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Adicionar configuração de tipo
            foreach ($notifications as &$notification) {
                $notification['config'] = $this->typeConfig[$notification['type']] ?? ['icon' => 'bell', 'color' => '#64748b'];
                if ($notification['data']) {
                    $notification['data'] = json_decode($notification['data'], true);
                }
            }
            
            return $notifications;
        } catch (PDOException $e) {
            error_log("Erro ao buscar notificações: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Contar notificações não lidas
     */
    public function countUnread($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM notifications
                WHERE user_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$userId]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erro ao contar não lidas: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Marcar como lida
     */
    public function markAsRead($notificationId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications
                SET is_read = TRUE, read_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$notificationId, $userId]);
        } catch (PDOException $e) {
            error_log("Erro ao marcar como lida: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Marcar todas como lidas
     */
    public function markAllAsRead($userId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications
                SET is_read = TRUE, read_at = NOW()
                WHERE user_id = ? AND is_read = FALSE
            ");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Erro ao marcar todas como lidas: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Deletar notificação
     */
    public function delete($notificationId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM notifications
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$notificationId, $userId]);
        } catch (PDOException $e) {
            error_log("Erro ao deletar notificação: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar se tipo de notificação está ativo para usuário
     */
    private function isTypeEnabled($userId, $type) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT enabled FROM notification_preferences
                WHERE user_id = ? AND type = ?
            ");
            $stmt->execute([$userId, $type]);
            $result = $stmt->fetchColumn();
            
            // Se não existe preferência, assumir habilitado
            return $result === false ? true : (bool) $result;
        } catch (PDOException $e) {
            error_log("Erro ao verificar preferência: " . $e->getMessage());
            return true; // Default: habilitado
        }
    }
    
    /**
     * Métodos específicos para criar notificações de eventos
     */
    
    public function notifyNewEscala($escalaId, $escalaNome, $data) {
        $title = "Nova Escala: $escalaNome";
        $message = "Uma nova escala foi criada para " . date('d/m/Y', strtotime($data));
        $link = "escalas.php?id=$escalaId";
        $data = ['escala_id' => $escalaId, 'data' => $data];
        
        return $this->createForLeaders(self::TYPE_NEW_ESCALA, $title, $message, $data, $link);
    }
    
    public function notifyNewMusic($musicaId, $musicaNome, $artista) {
        $title = "Nova Música: $musicaNome";
        $message = "A música \"$musicaNome\" de $artista foi adicionada ao repertório";
        $link = "musica_editar.php?id=$musicaId";
        $data = ['musica_id' => $musicaId];
        
        return $this->createForLeaders(self::TYPE_NEW_MUSIC, $title, $message, $data, $link);
    }
    
    public function notifyNewAviso($avisoId, $titulo, $prioridade) {
        $title = "Novo Aviso: $titulo";
        $message = "Um novo aviso foi publicado";
        $link = "avisos.php?id=$avisoId";
        $data = ['aviso_id' => $avisoId, 'prioridade' => $prioridade];
        
        $type = $prioridade === 'urgent' ? self::TYPE_AVISO_URGENT : self::TYPE_NEW_AVISO;
        
        // Notificar todos os usuários
        $stmt = $this->pdo->query("SELECT id FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        return $this->createForMultiple($users, $type, $title, $message, $data, $link);
    }
    
    /**
     * Enviar Notification Push via WebPush
     */
    private function sendPushNotification($userId, $title, $message, $link = null, $type = 'info') {
        try {
            // Verificar subscriptions do usuário
            // Verifica se a tabela existe primeiro para evitar erros em migrações parciais, ou assume que existe.
            // Vamos assumir que existe pois já criamos.
            
            $stmt = $this->pdo->prepare("SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE user_id = ?");
            $stmt->execute([$userId]);
            $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($subscriptions)) {
                return false;
            }

            // Instanciar Helper
            if (class_exists('WebPushHelper')) {
                // Carregar configurações VAPID
                $vapid = require __DIR__ . '/vapid_config.php';
                
                $webPush = new WebPushHelper(
                    $vapid['publicKey'],
                    $vapid['privateKey'],
                    $vapid['subject']
                ); 

                $payload = [
                    'title' => $title,
                    'body' => $message,
                    'icon' => '../assets/icons/icon-192x192.png',
                    'url' => $link ?? '/',
                    'data' => 
                        ['type' => $type]
                ];

                foreach ($subscriptions as $sub) {
                    // O método espera um array com endpoint, p256dh, auth
                    $webPush->sendNotification($sub, $payload);
                }
                return true;
            }
            return false;

        } catch (Exception $e) {
            error_log("Erro no Push Notification: " . $e->getMessage());
            return false;
        }
    }
}
?>
