<!-- NOVO DESIGN: ABA EQUIPE -->
<div id="equipe" class="tab-content <?= $activeTab === 'equipe' ? 'active' : '' ?>">
    <?php
    // Agrupar por tipo
    $vozMembers = [];
    $instrumentoMembers = [];

    foreach ($currentMembers as $m) {
        $inst = $m['instrument'] ?: 'Voz';
        if (stripos($inst, 'voz') !== false && strlen($inst) <= 10) {
            $vozMembers[] = $m;
        } else {
            $instrumentoMembers[] = $m;
        }
    }

    $totalMembros = count($currentMembers);
    ?>

    <?php if (empty($currentMembers)): ?>
        <!-- Empty State -->
        <div style="text-align: center; padding: 60px 20px;">
            <div style="background: var(--bg-tertiary); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i data-lucide="users" style="color: var(--text-muted); width: 40px; height: 40px;"></i>
            </div>
            <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">Equipe Vazia</h3>
            <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 24px;">Adicione instrumentistas e vocalistas para esta escala.</p>
            <button onclick="openModal('modalMembers')" class="btn-action-add ripple">
                <i data-lucide="plus" style="width: 18px;"></i> Adicionar Membros
            </button>
        </div>
    <?php else: ?>

        <!-- Header com Contador -->
        <div style="background: var(--bg-secondary); border-radius: 16px; padding: 20px; margin-bottom: 20px; border: 1px solid var(--border-subtle); box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div>
                    <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 10px;">
                        <i data-lucide="users" style="width: 22px; color: #047857;"></i>
                        Equipe Escalada
                    </h3>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 4px 0 0 32px;"><?= $totalMembros ?> <?= $totalMembros == 1 ? 'membro' : 'membros' ?></p>
                </div>
                <button onclick="openModal('modalMembers')" class="ripple" style="background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);">
                    <i data-lucide="plus" style="width: 16px;"></i> Adicionar
                </button>
            </div>
        </div>

        <!-- Vocalistas -->
        <?php if (!empty($vozMembers)): ?>
            <div style="margin-bottom: 24px;">
                <h4 style="font-size: 0.85rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 12px 0; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="mic-2" style="width: 16px; color: #8b5cf6;"></i>
                    Voz (<?= count($vozMembers) ?>)
                </h4>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach ($vozMembers as $member):
                        $statusColors = [
                            'confirmed' => ['bg' => '#d1fae5', 'text' => '#065f46', 'label' => 'Confirmado', 'icon' => 'check-circle'],
                            'pending' => ['bg' => '#fef3c7', 'text' => '#92400e', 'label' => 'Pendente', 'icon' => 'clock'],
                            'declined' => ['bg' => '#fee2e2', 'text' => '#991b1b', 'label' => 'Recusado', 'icon' => 'x-circle'],
                        ];
                        $status = $statusColors[$member['status']] ?? $statusColors['pending'];
                    ?>
                        <div style="background: var(--bg-secondary); border: 1.5px solid var(--border-subtle); border-radius: 14px; padding: 14px; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s; box-shadow: 0 1px 4px rgba(0,0,0,0.05);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'; this.style.borderColor='#8b5cf6';" onmouseout="this.style.transform=''; this.style.boxShadow='0 1px 4px rgba(0,0,0,0.05)'; this.style.borderColor='var(--border-subtle)';">
                            <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                                <!-- Avatar -->
                                <div style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #ddd6fe 0%, #c4b5fd 100%); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; color: #5b21b6; flex-shrink: 0; border: 2px solid #a78bfa;">
                                    <?= strtoupper(substr($member['name'], 0, 1)) ?>
                                </div>

                                <!-- Info -->
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-primary); margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($member['name']) ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary); font-weight: 500;">
                                        <?= htmlspecialchars($member['instrument'] ?: 'Voz') ?>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div style="background: <?= $status['bg'] ?>; color: <?= $status['text'] ?>; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; gap: 4px; white-space: nowrap;">
                                    <i data-lucide="<?= $status['icon'] ?>" style="width: 14px;"></i>
                                    <span><?= $status['label'] ?></span>
                                </div>
                            </div>

                            <!-- Botão Remover -->
                            <form method="POST" onsubmit="return confirm('Remover <?= htmlspecialchars($member['name']) ?>?');" style="margin: 0 0 0 12px;">
                                <input type="hidden" name="action" value="remove_member">
                                <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                                <input type="hidden" name="current_tab" value="equipe">
                                <button type="submit" class="ripple" style="background: transparent; border: none; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #dc2626; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#fee2e2';" onmouseout="this.style.background='transparent';">
                                    <i data-lucide="trash-2" style="width: 18px;"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Instrumentistas -->
        <?php if (!empty($instrumentoMembers)): ?>
            <div>
                <h4 style="font-size: 0.85rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 12px 0; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="guitar" style="width: 16px; color: #f59e0b;"></i>
                    Instrumentos (<?= count($instrumentoMembers) ?>)
                </h4>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach ($instrumentoMembers as $member):
                        $statusColors = [
                            'confirmed' => ['bg' => '#d1fae5', 'text' => '#065f46', 'label' => 'Confirmado', 'icon' => 'check-circle'],
                            'pending' => ['bg' => '#fef3c7', 'text' => '#92400e', 'label' => 'Pendente', 'icon' => 'clock'],
                            'declined' => ['bg' => '#fee2e2', 'text' => '#991b1b', 'label' => 'Recusado', 'icon' => 'x-circle'],
                        ];
                        $status = $statusColors[$member['status']] ?? $statusColors['pending'];

                        // Cor do avatar baseada no instrumento
                        $instColors = [
                            'Violão' => ['bg' => '#fed7aa', 'border' => '#fb923c', 'text' => '#9a3412'],
                            'Guitarra' => ['bg' => '#bfdbfe', 'border' => '#60a5fa', 'text' => '#1e40af'],
                            'Bateria' => ['bg' => '#fecaca', 'border' => '#f87171', 'text' => '#991b1b'],
                            'Teclado' => ['bg' => '#a7f3d0', 'border' => '#34d399', 'text' => '#065f46'],
                            'Baixo' => ['bg' => '#e9d5ff', 'border' => '#c084fc', 'text' => '#6b21a8'],
                        ];
                        $instColor = $instColors[$member['instrument']] ?? ['bg' => '#fed7aa', 'border' => '#fb923c', 'text' => '#9a3412'];
                    ?>
                        <div style="background: var(--bg-secondary); border: 1.5px solid var(--border-subtle); border-radius: 14px; padding: 14px; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s; box-shadow: 0 1px 4px rgba(0,0,0,0.05);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'; this.style.borderColor='#f59e0b';" onmouseout="this.style.transform=''; this.style.boxShadow='0 1px 4px rgba(0,0,0,0.05)'; this.style.borderColor='var(--border-subtle)';">
                            <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                                <!-- Avatar -->
                                <div style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, <?= $instColor['bg'] ?> 0%, <?= $instColor['bg'] ?> 100%); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; color: <?= $instColor['text'] ?>; flex-shrink: 0; border: 2px solid <?= $instColor['border'] ?>;">
                                    <?= strtoupper(substr($member['name'], 0, 1)) ?>
                                </div>

                                <!-- Info -->
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-primary); margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($member['name']) ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary); font-weight: 500;">
                                        <?= htmlspecialchars($member['instrument']) ?>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div style="background: <?= $status['bg'] ?>; color: <?= $status['text'] ?>; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; gap: 4px; white-space: nowrap;">
                                    <i data-lucide="<?= $status['icon'] ?>" style="width: 14px;"></i>
                                    <span><?= $status['label'] ?></span>
                                </div>
                            </div>

                            <!-- Botão Remover -->
                            <form method="POST" onsubmit="return confirm('Remover <?= htmlspecialchars($member['name']) ?>?');" style="margin: 0 0 0 12px;">
                                <input type="hidden" name="action" value="remove_member">
                                <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                                <input type="hidden" name="current_tab" value="equipe">
                                <button type="submit" class="ripple" style="background: transparent; border: none; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #dc2626; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#fee2e2';" onmouseout="this.style.background='transparent';">
                                    <i data-lucide="trash-2" style="width: 18px;"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>