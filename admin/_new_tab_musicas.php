<!-- NOVO DESIGN: ABA MÚSICAS -->
<div id="repertorio" class="tab-content <?= $activeTab === 'repertorio' ? 'active' : '' ?>">
    <?php
    $totalMusicas = count($currentSongs);
    $duracaoTotal = $totalMusicas * 5; // 5 min por música (estimativa)
    ?>

    <?php if (empty($currentSongs)): ?>
        <!-- Empty State -->
        <div style="text-align: center; padding: 60px 20px;">
            <div style="background: var(--bg-tertiary); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i data-lucide="music" style="color: var(--text-muted); width: 40px; height: 40px;"></i>
            </div>
            <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">Repertório Vazio</h3>
            <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 24px;">Selecione as músicas para esta escala.</p>
            <button onclick="openModal('modalSongs')" class="btn-action-add ripple">
                <i data-lucide="plus" style="width: 18px;"></i> Adicionar Músicas
            </button>
        </div>
    <?php else: ?>

        <!-- Header com Contador e Duração -->
        <div style="background: var(--bg-secondary); border-radius: 16px; padding: 20px; margin-bottom: 20px; border: 1px solid var(--border-subtle); box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 10px;">
                        <i data-lucide="music-2" style="width: 22px; color: #3b82f6;"></i>
                        Repertório
                    </h3>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 4px 0 0 32px;">
                        <?= $totalMusicas ?> <?= $totalMusicas == 1 ? 'música' : 'músicas' ?> • ~<?= $duracaoTotal ?>min
                    </p>
                </div>
                <button onclick="openModal('modalSongs')" class="ripple" style="background: #3b82f6; color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);">
                    <i data-lucide="plus" style="width: 16px;"></i> Adicionar
                </button>
            </div>
        </div>

        <!-- Lista de Músicas -->
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($currentSongs as $index => $song):
                $ordem = $index + 1;
                $duracaoEstimada = 5; // minutos
            ?>
                <div style="background: var(--bg-secondary); border: 1.5px solid var(--border-subtle); border-radius: 16px; overflow: hidden; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.05);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(0,0,0,0.1)'; this.style.borderColor='#3b82f6';" onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.05)'; this.style.borderColor='var(--border-subtle)';">

                    <!-- Header da Música -->
                    <div style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); padding: 14px 18px; border-bottom: 1px solid #93c5fd;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <!-- Número da Ordem -->
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1rem; color: white; flex-shrink: 0; box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);">
                                <?= $ordem ?>
                            </div>

                            <!-- Título -->
                            <div style="flex: 1; min-width: 0;">
                                <h4 style="font-size: 1.05rem; font-weight: 800; color: #1e3a8a; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= htmlspecialchars($song['title']) ?>
                                </h4>
                            </div>
                        </div>
                    </div>

                    <!-- Corpo da Música -->
                    <div style="padding: 16px 18px;">
                        <!-- Artista -->
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                            <i data-lucide="user" style="width: 16px; color: #6b7280;"></i>
                            <span style="font-size: 0.9rem; color: var(--text-secondary); font-weight: 500;">
                                <?= htmlspecialchars($song['artist'] ?: 'Artista não informado') ?>
                            </span>
                        </div>

                        <!-- Informações -->
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px;">
                            <!-- Tom -->
                            <?php if (!empty($song['tone'])): ?>
                                <div style="background: #fef3c7; color: #92400e; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; display: flex; align-items: center; gap: 6px; border: 1px solid #fcd34d;">
                                    <i data-lucide="music" style="width: 14px;"></i>
                                    Tom: <?= htmlspecialchars($song['tone']) ?>
                                </div>
                            <?php endif; ?>

                            <!-- Duração -->
                            <div style="background: #e0e7ff; color: #3730a3; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; display: flex; align-items: center; gap: 6px; border: 1px solid #c7d2fe;">
                                <i data-lucide="clock" style="width: 14px;"></i>
                                ~<?= $duracaoEstimada ?>min
                            </div>
                        </div>

                        <!-- Ações -->
                        <div style="display: flex; gap: 8px; padding-top: 12px; border-top: 1px solid var(--border-subtle);">
                            <!-- Link Cifra -->
                            <?php if (!empty($song['link'])): ?>
                                <a href="<?= htmlspecialchars($song['link']) ?>" target="_blank" class="ripple" style="flex: 1; background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; padding: 10px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s;" onmouseover="this.style.background='#e5e7eb';" onmouseout="this.style.background='#f3f4f6';">
                                    <i data-lucide="link" style="width: 16px;"></i>
                                    Cifra
                                </a>
                            <?php endif; ?>

                            <!-- Botão Ver Detalhes -->
                            <a href="musica_detalhe.php?id=<?= $song['id'] ?>" class="ripple" style="flex: 1; background: #3b82f6; color: white; border: none; padding: 10px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s; box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3);" onmouseover="this.style.background='#2563eb';" onmouseout="this.style.background='#3b82f6';">
                                <i data-lucide="eye" style="width: 16px;"></i>
                                Ver Detalhes
                            </a>

                            <!-- Botão Remover -->
                            <form method="POST" onsubmit="return confirm('Remover <?= htmlspecialchars($song['title']) ?>?');" style="margin: 0;">
                                <input type="hidden" name="action" value="remove_song">
                                <input type="hidden" name="song_id" value="<?= $song['id'] ?>">
                                <input type="hidden" name="current_tab" value="repertorio">
                                <button type="submit" class="ripple" style="background: transparent; border: 1px solid #fca5a5; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #dc2626; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#fee2e2';" onmouseout="this.style.background='transparent';">
                                    <i data-lucide="trash-2" style="width: 18px;"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Dica de Reordenação -->
        <div style="margin-top: 20px; padding: 14px; background: rgba(59, 130, 246, 0.1); border-left: 3px solid #3b82f6; border-radius: 0 10px 10px 0; display: flex; align-items: center; gap: 10px;">
            <i data-lucide="info" style="width: 18px; color: #3b82f6; flex-shrink: 0;"></i>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0; line-height: 1.5;">
                <strong style="color: #1e40af;">Dica:</strong> As músicas são exibidas na ordem em que foram adicionadas.
            </p>
        </div>

    <?php endif; ?>
</div>