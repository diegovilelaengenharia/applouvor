<?php
namespace App\Controllers;

use App\AuthMiddleware;
use PDO;

class ReadingController extends Controller
{
    private array $plans = [
        'mcheyne'    => ['name' => "Plano M'Cheyne",             'icon' => 'menu_book',    'desc' => '4 capítulos/dia · Bíblia toda + Salmos 2× ao ano', 'days' => 365],
        'cronologico'=> ['name' => 'Cronológico',                 'icon' => 'explore',      'desc' => 'Leia na ordem dos acontecimentos históricos',        'days' => 365],
        'navigators' => ['name' => 'Navigators',                  'icon' => 'auto_stories', 'desc' => 'Plano equilibrado AT + NT',                           'days' => 365],
        'nt90'       => ['name' => 'Novo Testamento em 90 dias',  'icon' => 'speed',        'desc' => 'Leitura intensiva do NT completo',                   'days' => 90],
    ];

    private array $planPassages = [
        'mcheyne'    => ['Gênesis 5', 'Mateus 5', 'Esdras 5', 'Atos 5'],
        'cronologico'=> ['Gênesis 5', 'Gênesis 6', 'Gênesis 7'],
        'navigators' => ['Gênesis 5', 'Mateus 5'],
        'nt90'       => ['Mateus 5', 'Mateus 6'],
    ];

    public function index()
    {
        AuthMiddleware::requireLogin();
        $uid = $_SESSION['user_id'];

        $stmtPlan = $this->pdo->prepare(
            "SELECT setting_value FROM user_settings WHERE user_id = :uid AND setting_key = 'reading_plan'"
        );
        $stmtPlan->execute(['uid' => $uid]);
        $planKey = $stmtPlan->fetchColumn() ?: 'mcheyne';

        $plan = $this->plans[$planKey] ?? $this->plans['mcheyne'];

        $stmtDays = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT DATE(completed_at)) FROM reading_progress
             WHERE user_id = :uid AND plan_key = :pk"
        );
        $stmtDays->execute(['uid' => $uid, 'pk' => $planKey]);
        $daysRead = (int)$stmtDays->fetchColumn();

        $streak = $this->calculateStreak($uid, $planKey);

        $todayPassages = $this->planPassages[$planKey] ?? ['Gênesis 1'];

        $stmtToday = $this->pdo->prepare(
            "SELECT COUNT(*) FROM reading_progress
             WHERE user_id = :uid AND plan_key = :pk AND DATE(completed_at) = CURDATE()"
        );
        $stmtToday->execute(['uid' => $uid, 'pk' => $planKey]);
        $readToday = (int)$stmtToday->fetchColumn() > 0;

        $this->render('vida-espiritual/leitura', compact(
            'plan', 'planKey', 'daysRead', 'streak', 'todayPassages', 'readToday'
        ));
    }

    public function markRead()
    {
        AuthMiddleware::requireLogin();
        csrf_verify();

        $uid     = $_SESSION['user_id'];
        $planKey = trim($_POST['plan_key'] ?? 'mcheyne');

        $already = $this->pdo->prepare(
            "SELECT COUNT(*) FROM reading_progress
             WHERE user_id = :uid AND plan_key = :pk AND DATE(completed_at) = CURDATE()"
        );
        $already->execute(['uid' => $uid, 'pk' => $planKey]);

        if ((int)$already->fetchColumn() === 0) {
            $ins = $this->pdo->prepare(
                "INSERT INTO reading_progress (user_id, plan_key, chapter_ref) VALUES (:uid, :pk, :ref)"
            );
            $ins->execute(['uid' => $uid, 'pk' => $planKey, 'ref' => date('Y-m-d')]);
        }

        $_SESSION['flash']['success'] = 'Leitura marcada! Continue firme 🔥';
        $this->redirect('/leitura');
    }

    public function plans()
    {
        AuthMiddleware::requireLogin();
        $uid = $_SESSION['user_id'];

        $stmtPlan = $this->pdo->prepare(
            "SELECT setting_value FROM user_settings WHERE user_id = :uid AND setting_key = 'reading_plan'"
        );
        $stmtPlan->execute(['uid' => $uid]);
        $activePlan = $stmtPlan->fetchColumn() ?: 'mcheyne';

        $this->render('vida-espiritual/leitura-planos', ['plans' => $this->plans, 'activePlan' => $activePlan]);
    }

    public function setPlan()
    {
        AuthMiddleware::requireLogin();
        csrf_verify();

        $uid     = $_SESSION['user_id'];
        $planKey = trim($_POST['plan_key'] ?? 'mcheyne');
        if (!isset($this->plans[$planKey])) $planKey = 'mcheyne';

        $stmt = $this->pdo->prepare("
            INSERT INTO user_settings (user_id, setting_key, setting_value)
            VALUES (:uid, 'reading_plan', :val)
            ON DUPLICATE KEY UPDATE setting_value = :val
        ");
        $stmt->execute(['uid' => $uid, 'val' => $planKey]);

        $_SESSION['flash']['success'] = 'Plano "' . htmlspecialchars($this->plans[$planKey]['name']) . '" ativado!';
        $this->redirect('/leitura');
    }

    private function calculateStreak(int $uid, string $planKey): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT DATE(completed_at) AS d FROM reading_progress
             WHERE user_id = :uid AND plan_key = :pk
             ORDER BY d DESC LIMIT 90"
        );
        $stmt->execute(['uid' => $uid, 'pk' => $planKey]);
        $dates = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'd');

        $streak = 0;
        $check  = date('Y-m-d');
        foreach ($dates as $d) {
            if ($d === $check) {
                $streak++;
                $check = date('Y-m-d', strtotime($check . ' -1 day'));
            } else {
                break;
            }
        }
        return $streak;
    }
}
