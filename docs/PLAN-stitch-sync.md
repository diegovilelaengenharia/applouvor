# Plano de Sincronização e Refinamento Visual com Stitch MCP

Este plano detalha as ações para alinhar a interface local do aplicativo com o design system unificado "Sacred Minimalist" extraído do servidor **Stitch MCP**, resolvendo o contraste agressivo na versão mobile e garantindo fidelidade absoluta ao design refinado que o usuário aprovou.

## Contexto e Problema

A interface mobile local atualmente diverge das cores limpas e da geometria purista do projeto do Stitch. O tema local introduziu tons de areia/linho quentes (`#F8F7F4`, `#F3F1EC`, `#C29000`) e bordas que criam divisões visuais muito acentuadas. 

Além disso, o banner de boas-vindas do topo, embora sofisticado, cria uma quebra de alto contraste e peso visual excessivo em telas de celulares (mobile PWA), dividindo abruptamente a tela clara do aplicativo.

## Mudanças Propostas

O objetivo é restaurar o design de alta fidelidade "Sacred Minimalist" do Stitch MCP (conforme imagem de referência):
1. **Fundo Claro Purista:** Fundo limpo em `#f9f9f9`, cards em `#ffffff` puro e bordas de separação finas em `#eeeeee` (em vez de tons amarelados/areia).
2. **Cores da Marca Harmoniosas:** Azul Worship original (`#2E7EED`), Azul Primário profissional (`#0059b8`), Ouro do Altar suave (`#FFC107`) e Cinza Fantasma (`#F4F4F5`) como tons base.
3. **Banner Superior Suavizado no Mobile:** Calibrar a opacidade e o gradiente do banner superior no `admin/index.php` para integrar-se suavemente ao layout mobile, reduzindo o contraste agressivo.
4. **Precisão Geométrica:** Cantos arredondados padronizados em `0.5rem` (8px) para botões/inputs e `1.0rem` (16px) para cards grandes de eventos e avisos.

---

## User Review Required

> [!IMPORTANT]
> **Suavização do Banner no Mobile:** Propomos manter o gradiente escuro premium de marinho e ouro para telas desktop, mas suavizar sua intensidade (ou aplicar um fundo translúcido e integrado) em telas mobile para evitar a quebra visual agressiva com a barra de navegação clara superior.
> 
> **Substituição do Tema "Aurora do Altar":** O plano prevê remover completamente a paleta areia/linho e adotar a paleta fria e limpa do Stitch (Sacred Minimalist) de forma unificada no `stitch-theme.css`.

---

## Open Questions

> [!WARNING]
> 1. **Comportamento do Banner no Modo Escuro:** Quando o modo escuro estiver ativado, o banner de boas-vindas deve manter o gradiente marinho profundo ou se integrar como um card translúcido no fundo escuro `#0F1012`?
> 2. **Sincronização dos Ícones:** O Stitch utiliza ícones com a cor Worship Blue (`#2E7EED`) nas barras de topo e rodapé. Deseja que padronizemos TODOS os ícones ativos e de navegação estritamente sob este tom em todo o aplicativo?

---

## Proposed Changes

### [CSS Theme & Variables]

Ajuste completo do sistema de design para coincidir com a especificação do Stitch MCP.

#### [MODIFY] [stitch-theme.css](file:///c:/Users/diego/Meu%20Drive/02.%20Trabalho/04.%20Vilela.eng%20Site/APP%20Louvor%20(COM%20STICH)/assets/css/stitch-theme.css)
* Atualizar a raiz `:root` para os tokens do Stitch:
  ```css
  --surface: #f9f9f9;
  --surface-dim: #dadada;
  --surface-bright: #f9f9f9;
  --surface-container-lowest: #ffffff;
  --surface-container-low: #f3f3f3;
  --surface-container: #eeeeee;
  --surface-container-high: #e8e8e8;
  --surface-container-highest: #e2e2e2;
  --on-surface: #1a1c1c;
  --on-surface-variant: #414753;
  --outline: #727785;
  --outline-variant: #c1c6d6;
  --primary: #0059b8;
  --worship-blue: #2E7EED;
  --altar-gold: #FFC107;
  --ghost-gray: #F4F4F5;
  ```
* Calibrar o `.dark` mode com tons escuros mais puros e suaves baseados em `#0F1012`.

---

### [Dashboard Interface]

Suavização e adaptação do banner e cards no celular.

#### [MODIFY] [index.php](file:///c:/Users/diego/Meu%20Drive/02.%20Trabalho/04.%20Vilela.eng%20Site/APP%20Louvor%20(COM%20STICH)/admin/index.php)
* Ajustar o container do banner superior para ter um gradiente mais suave no mobile, ou usar classes condicionais de tela para suavizar o peso visual.
* Garantir que as bordas e fundos dos cards internos (como o card "Próxima Escala") e dos cards externos ("Avisos" e "Aniversariantes") sigam estritamente as novas variáveis CSS do Stitch.

---

## Verification Plan

### Automated Tests
- Execução do script unificado de auditoria do projeto:
  `python .agent/scripts/checklist.py .`

### Manual Verification
- Visualização das alterações no navegador simulando telas de celular (mobile touch emulator).
- Verificação do contraste de cores do banner de topo em dispositivos mobile reais.
- Teste de alternância entre o modo claro e escuro.
