<?php
// admin/exportar_completo.php
// Exportação profissional em Excel com múltiplas abas (CORRIGIDO)

require_once '../includes/db.php';

// Configurar headers corretos para Excel
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="App_Louvor_PIB_Oliveira_' . date('Y-m-d_His') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM para UTF-8
echo "\xEF\xBB\xBF";

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    
</head>

<body>

    <?php
    $tipo = $_GET['tipo'] ?? 'tudo';
    ?>

    <?php if ($tipo === 'tudo' || $tipo === 'membros'): ?>
        <!-- ==================== ABA 1: MEMBROS ==================== -->
        <h2>MEMBROS DO MINISTÉRIO DE LOUVOR</h2>
        <p>PIB Oliveira - <?= date('d/m/Y H:i') ?></p>

        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Instrumento</th>
                    <th>Função</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $membros = $pdo->query("SELECT * FROM users ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($membros as $membro):
                ?>
                    <tr>
                        <td><?= htmlspecialchars($membro['name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($membro['email'] ?? '') ?></td>
                        <td><?= htmlspecialchars($membro['phone'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($membro['instrument'] ?? '') ?></td>
                        <td><?= $membro['role'] === 'admin' ? 'Administrador' : 'Membro' ?></td>
                        <td>Ativo</td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="6">Total de Membros: <?= count($membros) ?></td>
                </tr>
            </tbody>
        </table>

        <br><br>
    <?php endif; ?>

    <?php if ($tipo === 'tudo' || $tipo === 'repertorio'): ?>
        <!-- ==================== ABA 2: REPERTÓRIO ==================== -->
        <h2>REPERTÓRIO DE MÚSICAS</h2>
        <p>PIB Oliveira - <?= date('d/m/Y H:i') ?></p>

        <table>
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Artista</th>
                    <th>Tom</th>
                    <th>BPM</th>
                    <th>Duração</th>
                    <th>Categoria</th>
                    <th>Letra</th>
                    <th>Cifra</th>
                    <th>Áudio</th>
                    <th>Vídeo</th>
                    <th>Tags</th>
                    <th>Observações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $musicas = $pdo->query("SELECT * FROM songs ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($musicas as $musica):
                ?>
                    <tr>
                        <td><?= htmlspecialchars($musica['title'] ?? '') ?></td>
                        <td><?= htmlspecialchars($musica['artist'] ?? '') ?></td>
                        <td><?= htmlspecialchars($musica['tone'] ?? '-') ?></td>
                        <td><?= $musica['bpm'] ?? '-' ?></td>
                        <td><?= htmlspecialchars($musica['duration'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($musica['category'] ?? '') ?></td>
                        <td><?= !empty($musica['link_letra']) ? 'Sim' : 'Não' ?></td>
                        <td><?= !empty($musica['link_cifra']) ? 'Sim' : 'Não' ?></td>
                        <td><?= !empty($musica['link_audio']) ? 'Sim' : 'Não' ?></td>
                        <td><?= !empty($musica['link_video']) ? 'Sim' : 'Não' ?></td>
                        <td><?= htmlspecialchars($musica['tags'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($musica['notes'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="12">Total de Músicas: <?= count($musicas) ?></td>
                </tr>
            </tbody>
        </table>

        <br><br>
    <?php endif; ?>

    <?php if ($tipo === 'tudo' || $tipo === 'escalas'): ?>
        <!-- ==================== ABA 3: ESCALAS ==================== -->
        <h2>ESCALAS DE LOUVOR</h2>
        <p>PIB Oliveira - <?= date('d/m/Y H:i') ?></p>

        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo de Evento</th>
                    <th>Equipe</th>
                    <th>Músicas</th>
                    <th>Status</th>
                    <th>Confirmados</th>
                    <th>Pendentes</th>
                    <th>Observações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $escalas = $pdo->query("SELECT * FROM schedules ORDER BY event_date DESC")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($escalas as $escala):
                    // Contar membros
                    $membros_stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmados FROM schedule_users WHERE schedule_id = ?");
                    $membros_stmt->execute([$escala['id']]);
                    $membros_info = $membros_stmt->fetch(PDO::FETCH_ASSOC);

                    // Contar músicas
                    $musicas_count = $pdo->prepare("SELECT COUNT(*) as total FROM schedule_songs WHERE schedule_id = ?");
                    $musicas_count->execute([$escala['id']]);
                    $total_musicas = $musicas_count->fetchColumn();
                ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($escala['event_date'])) ?></td>
                        <td><?= htmlspecialchars($escala['event_type'] ?? '') ?></td>
                        <td><?= $membros_info['total'] ?> membros</td>
                        <td><?= $total_musicas ?> músicas</td>
                        <td><?= strtotime($escala['event_date']) >= strtotime('today') ? 'Próxima' : 'Realizada' ?></td>
                        <td><?= $membros_info['confirmados'] ?></td>
                        <td><?= ($membros_info['total'] - $membros_info['confirmados']) ?></td>
                        <td><?= htmlspecialchars($escala['notes'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="8">Total de Escalas: <?= count($escalas) ?></td>
                </tr>
            </tbody>
        </table>

        <br><br>
    <?php endif; ?>

    <!-- Rodapé -->
    <p style="text-align: center; font-size: 9pt; color: #666;">
        Gerado automaticamente pelo App Louvor PIB Oliveira em <?= date('d/m/Y \à\s H:i:s') ?>
    </p>

</body>

</html>