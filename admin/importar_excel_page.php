<?php
// admin/importar_excel_page.php
// Página para importar músicas do Excel

require_once '../includes/db.php';
require_once '../includes/layout.php';

renderAppHeader('Importar Músicas');
?>

<style>
    .step-box {
        background: var(--bg-secondary);
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
    }

    .step-number {
        width: 32px;
        height: 32px;
        background: var(--accent-interactive);
        color: white;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        margin-right: 12px;
    }
</style>

<div style="max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
        <a href="repertorio.php" class="btn-icon ripple">
            <i data-lucide="arrow-left"></i>
        </a>
        <h1 style="font-size: 1.3rem; font-weight: 800; color: var(--text-primary); margin: 0;">Importar Músicas</h1>
    </div>

    <div class="step-box">
        <h3 style="font-weight: 700; margin-bottom: 16px;">
            <span class="step-number">1</span>
            Baixe o modelo
        </h3>
        <p style="color: var(--text-secondary); margin-bottom: 16px;">
            Faça o download do modelo de importação. As músicas presentes no modelo são apenas para exemplo.
        </p>
        <a href="../banco de dados/Musicas_Louveapp_1768828036289.xlsx" download class="btn-outline ripple" style="width: 100%; justify-content: center; text-decoration: none;">
            <i data-lucide="download"></i> Baixar modelo (.xlsx)
        </a>
    </div>

    <div class="step-box">
        <h3 style="font-weight: 700; margin-bottom: 16px;">
            <span class="step-number">2</span>
            Preencha as colunas
        </h3>
        <p style="color: var(--text-secondary); margin-bottom: 12px;">
            Preencha as informações das músicas:
        </p>
        <ul style="color: var(--text-secondary); padding-left: 20px; line-height: 1.8;">
            <li><strong>nomeMusica</strong> e <strong>nomeArtista</strong> são obrigatórios</li>
            <li>Não renomeie ou altere a ordem das colunas</li>
            <li>Para adicionar referências customizadas, use o padrão: [descrição](https://link.com)</li>
        </ul>
    </div>

    <div class="step-box">
        <h3 style="font-weight: 700; margin-bottom: 16px;">
            <span class="step-number">3</span>
            Importe o arquivo
        </h3>

        <form action="processar_importacao.php" method="POST" enctype="multipart/form-data">
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Selecione o arquivo Excel</label>
                <input type="file" name="excel_file" accept=".xlsx,.xls" class="form-input" required>
            </div>

            <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px; cursor: pointer;">
                <input type="checkbox" name="auto_fill" value="1" checked>
                <span style="font-size: 0.9rem; color: var(--text-secondary);">
                    Permitir que o sistema preencha os campos em branco automaticamente
                </span>
            </label>

            <button type="submit" class="btn-primary ripple" style="width: 100%; justify-content: center;">
                <i data-lucide="upload"></i> Importar Músicas
            </button>
        </form>
    </div>
</div>

<?php renderAppFooter(); ?>