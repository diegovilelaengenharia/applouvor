<!-- NOVO DESIGN: ABA DETALHES -->
<div id="detalhes" class="tab-content <?= $activeTab === 'detalhes' ? 'active' : '' ?>">
    <?php
    $d = new DateTime($schedule['event_date']);
    $dayNum = $d->format('d');
    $monthStr = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'][(int)$d->format('m') - 1];
    $timeStr = '19:00';

    // Calcular estatísticas
    $totalMembros = count($currentMembers);
    $totalMusicas = count($currentSongs);
    $duracaoEstimada = $totalMusicas * 5; // 5 min por música

    // Agrupar instrumentos
    $instrumentos = [];
    foreach ($currentMembers as $m) {
        $inst = $m['instrument'] ?: 'Voz';
        $instrumentos[$inst] = ($instrumentos[$inst] ?? 0) + 1;
    }
    ksort($instrumentos);
    ?>

    <!-- Card Principal -->
    <div style="background: var(--bg-secondary); border-radius: 20px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.08); border: 1px solid var(--border-subtle);">

        <!-- Header com Data -->
        <div style="background: linear-gradient(135deg, #047857 0%, #065f46 100%); padding: 24px; color: white;">
            <div style="display: flex; gap: 20px; align-items: flex-start;">
                <!-- Badge de Data -->
                <div style="background: white; border-radius: 16px; padding: 12px 16px; text-align: center; min-width: 80px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    <div style="font-size: 0.75rem; font-weight: 800; color: #047857; text-transform: uppercase; letter-spacing: 1px;"><?= $monthStr ?></div>
                    <div style="font-size: 2.5rem; font-weight: 900; color: #1f2937; line-height: 1; margin: 4px 0;"><?= $dayNum ?></div>
                    <div style="font-size: 0.7rem; font-weight: 600; color: #6b7280; text-transform: uppercase;"><?= $dayName ?></div>
                </div>

                <!-- Informações -->
                <div style="flex: 1;">
                    <div style="display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap;">
                        <span style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                            <i data-lucide="clock" style="width: 14px;"></i> <?= $timeStr ?>
                        </span>
                    </div>
                    <h2 style="font-size: 1.5rem; font-weight: 800; margin: 0; line-height: 1.2;">
                        <?= htmlspecialchars($schedule['event_type']) ?>
                    </h2>
                </div>
            </div>
        </div>

        <!-- Resumo Estatístico -->
        <div style="padding: 24px; border-bottom: 1px solid var(--border-subtle);">
            <h3 style="font-size: 0.85rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="bar-chart-2" style="width: 16px; color: #047857;"></i>
                Resumo da Escala
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 12px;">
                <!-- Card: Equipe -->
                <div style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 12px; padding: 16px; text-align: center; border: 1px solid #93c5fd;">
                    <div style="font-size: 0.7rem; font-weight: 700; color: #1e40af; text-transform: uppercase; margin-bottom: 6px;">Equipe</div>
                    <div style="font-size: 2rem; font-weight: 900; color: #1e3a8a; line-height: 1;">
                        <i data-lucide="users" style="width: 24px; height: 24px; margin-bottom: 4px;"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: #1e3a8a;"><?= $totalMembros ?></div>
                </div>

                <!-- Card: Músicas -->
                <div style="background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%); border-radius: 12px; padding: 16px; text-align: center; border: 1px solid #f9a8d4;">
                    <div style="font-size: 0.7rem; font-weight: 700; color: #9f1239; text-transform: uppercase; margin-bottom: 6px;">Músicas</div>
                    <div style="font-size: 2rem; font-weight: 900; color: #881337; line-height: 1;">
                        <i data-lucide="music" style="width: 24px; height: 24px; margin-bottom: 4px;"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: #881337;"><?= $totalMusicas ?></div>
                </div>

                <!-- Card: Duração -->
                <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 12px; padding: 16px; text-align: center; border: 1px solid #fcd34d;">
                    <div style="font-size: 0.7rem; font-weight: 700; color: #92400e; text-transform: uppercase; margin-bottom: 6px;">Duração</div>
                    <div style="font-size: 2rem; font-weight: 900; color: #78350f; line-height: 1;">
                        <i data-lucide="timer" style="width: 24px; height: 24px; margin-bottom: 4px;"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: #78350f;">~<?= $duracaoEstimada ?>min</div>
                </div>
            </div>
        </div>

        <!-- Instrumentos Escalados -->
        <?php if (!empty($instrumentos)): ?>
            <div style="padding: 24px; border-bottom: 1px solid var(--border-subtle);">
                <h3 style="font-size: 0.85rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="guitar" style="width: 16px; color: #047857;"></i>
                    Instrumentos Escalados
                </h3>

                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <?php foreach ($instrumentos as $inst => $count):
                        $cores = [
                            'Voz' => ['bg' => '#ddd6fe', 'text' => '#5b21b6', 'border' => '#a78bfa'],
                            'Violão' => ['bg' => '#fed7aa', 'text' => '#9a3412', 'border' => '#fb923c'],
                            'Guitarra' => ['bg' => '#bfdbfe', 'text' => '#1e40af', 'border' => '#60a5fa'],
                            'Bateria' => ['bg' => '#fecaca', 'text' => '#991b1b', 'border' => '#f87171'],
                            'Teclado' => ['bg' => '#a7f3d0', 'text' => '#065f46', 'border' => '#34d399'],
                            'Baixo' => ['bg' => '#e9d5ff', 'text' => '#6b21a8', 'border' => '#c084fc'],
                        ];
                        $cor = $cores[$inst] ?? ['bg' => '#e5e7eb', 'text' => '#374151', 'border' => '#9ca3af'];
                    ?>
                        <div style="background: <?= $cor['bg'] ?>; color: <?= $cor['text'] ?>; border: 1.5px solid <?= $cor['border'] ?>; padding: 8px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                            <span><?= htmlspecialchars($inst) ?></span>
                            <span style="background: <?= $cor['text'] ?>; color: white; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800;"><?= $count ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Observações -->
        <?php if (!empty($schedule['notes'])): ?>
            <div style="padding: 24px; border-bottom: 1px solid var(--border-subtle);">
                <div style="background: rgba(250, 204, 21, 0.1); border-left: 4px solid #FACC15; padding: 16px; border-radius: 0 12px 12px 0; display: flex; gap: 12px;">
                    <i data-lucide="info" style="color: #EAB308; width: 20px; flex-shrink: 0; margin-top: 2px;"></i>
                    <div>
                        <div style="font-weight: 700; color: #EAB308; font-size: 0.8rem; text-transform: uppercase; margin-bottom: 6px;">Observações</div>
                        <div style="color: var(--text-secondary); line-height: 1.6; font-size: 0.95rem;">
                            <?= nl2br(htmlspecialchars($schedule['notes'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Ações -->
        <div style="padding: 20px; background: var(--bg-tertiary); display: flex; gap: 12px;">
            <button onclick="openEditModal()" class="ripple" style="flex: 0 0 auto; width: 50px; height: 50px; background: #FFC107; color: white; border: none; border-radius: 12px; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3); transition: all 0.2s;">
                <i data-lucide="edit-3" style="width: 20px;"></i>
            </button>

            <form method="POST" onsubmit="return confirm('Excluir esta escala?')" style="margin: 0; flex: 1;">
                <input type="hidden" name="action" value="delete_schedule">
                <button type="submit" class="ripple" style="width: 100%; background: #DC3545; color: white; border: none; padding: 14px; border-radius: 12px; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; font-weight: 700; font-size: 0.95rem; box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3); transition: all 0.2s;">
                    <i data-lucide="trash-2" style="width: 18px;"></i> Excluir Escala
                </button>
            </form>
        </div>

    </div>
</div>