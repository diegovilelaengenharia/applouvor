<?php
// src/layout/modals/dashboard-modal.php
?>
<!-- Dashboard Customization Modal -->
<div id="dashboardCustomizationModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 3000; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div style="background: var(--bg-surface); padding: 24px; border-radius: 16px; width: 90%; max-width: 500px; max-height: 80vh; overflow-y: auto; box-shadow: var(--shadow-lg); animation: fadeInUp 0.2s cubic-bezier(0.16, 1, 0.3, 1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 1.25rem;">Personalizar Acesso Rápido</h3>
            <button onclick="closeDashboardCustomization()" style="background: transparent; border: none; cursor: pointer; color: var(--text-muted);">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 24px;">
            Selecione os atalhos que deseja exibir no seu painel.
        </p>
        
        <form id="dashboardCustomizationForm">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px;">
                <?php
                    // Ensure dashboard_cards is loaded
                    if (file_exists(__DIR__ . '/../dashboard_cards.php')) {
                        require_once __DIR__ . '/../dashboard_cards.php';
                    } elseif (file_exists(__DIR__ . '/../../src/layout/dashboard_cards.php')) {
                        require_once __DIR__ . '/../../src/layout/dashboard_cards.php';
                    }
                    
                    if (function_exists('getAllAvailableCards')):
                        $allCards = getAllAvailableCards();
                        
                        // Tentar buscar configurações do usuário
                        $enabledCards = [];
                        if (isset($_SESSION['user_id'])) {
                            global $pdo;
                            if ($pdo) {
                                try {
                                    $stmt = $pdo->prepare("SELECT card_id FROM user_dashboard_settings WHERE user_id = ? AND is_visible = 1");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $enabledCards = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                } catch (Exception $e) {}
                            }
                        }
                        
                        // Default if empty
                        if (empty($enabledCards)) {
                            $enabledCards = array_keys($allCards); 
                        }
                        
                        foreach($allCards as $id => $card):
                            $checked = in_array($id, $enabledCards) ? 'checked' : '';
                ?>
                <label style="
                    display: flex; align-items: center; gap: 10px; padding: 12px; 
                    border: 1px solid var(--border-color); border-radius: 12px; 
                    cursor: pointer; transition: all 0.2s; background: var(--bg-body);
                ">
                    <input type="checkbox" name="cards[]" value="<?= $id ?>" <?= $checked ?> style="width: 16px; height: 16px; accent-color: var(--primary);">
                    <div style="
                        width: 28px; height: 28px; border-radius: 8px; 
                        background: <?= $card['bg'] ?>; color: <?= $card['color'] ?>;
                        display: flex; align-items: center; justify-content: center;
                    ">
                        <i data-lucide="<?= $card['icon'] ?>" style="width: 16px;"></i>
                    </div>
                    <span style="font-size: 0.85rem; font-weight: 500; color: var(--text-main);"><?= $card['title'] ?></span>
                </label>
                <?php 
                        endforeach;
                    endif; 
                ?>
            </div>
            
            <div style="display: flex; gap: 12px; justify-content: flex-end; padding-top: 16px; border-top: 1px solid var(--border-color);">
                <button type="button" onclick="closeDashboardCustomization()" style="
                    padding: 10px 20px; border: 1px solid var(--red-300); 
                    background: var(--red-50); border-radius: 8px; cursor: pointer; 
                    color: var(--red-700); font-weight: 600; transition: all 0.2s;
                " onmouseover="this.style.background='var(--red-100)'" onmouseout="this.style.background='var(--red-50)'">Cancelar</button>
                <button type="submit" style="
                    padding: 10px 20px; background: var(--primary); 
                    color: white; border: none; border-radius: 8px; 
                    cursor: pointer; font-weight: 600; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                ">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>
