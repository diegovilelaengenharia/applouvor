# Design System Master — App Louvor Premium 2026

> **ESTRATÉGIA:** Este sistema é otimizado para celulares (PWA), focado em músicos e na agilidade de tomada de decisão no altar.

## 🎨 Paleta de Cores & Variáveis (assets/css/core/variables.css)
- **Primary:** `#3B82F6` (Azul Vibrante) - Uso em links e destaques.
- **CTA:** `#F97316` (Laranja) - Uso em ações críticas e avisos hoje.
- **Surface:** `#FFFFFF` (Cards) | `#F8FAFC` (Fundo)
- **Dark Mode:** Ativado via classe `.dark-mode` no body.

## 🏗️ Estrutura de Layout
1. **Bottom Navigation Bar:** Principal forma de navegação mobile (Início, Escalas, Repertório, Bíblia, Menu).
2. **Sidebar Drawer:** Menu lateral deslizante (acesso via botão "Menu" ou swipe) para funções administrativas e perfil.
3. **Glassmorphism:** Cabeçalhos e modais devem usar `backdrop-filter: blur(16px)`.

## 🎴 Componente Core: PIB Card
O `pib-card` é a unidade básica de informação.
- **Bordas:** `radius-lg` (12px).
- **Sombras:** `shadow-sm` em repouso, `shadow-md` no hover.
- **Interação:** `scale(0.97)` ao ser pressionado (Active state).
- **Animação:** Deve usar a classe `.animate-card` para entrada suave (staggered).

## ✨ Micro-interações (Make Interfaces Feel Better)
- **Scale on Press:** Todo botão ou card clicável deve reduzir levemente de tamanho ao toque.
- **Staggered Entry:** Listas não aparecem de vez; os itens surgem de baixo para cima com pequenos atrasos (0.1s, 0.15s, 0.2s...).
- **Haptic Feedback Visual:** Usar badges coloridas (`pib-badge`) para indicar status imediatos.

## 📱 Mobile-First Checklist
- [ ] Mínimo de 44x44px para áreas de clique.
- [ ] Sem scroll horizontal.
- [ ] Fontes: 'Inter Tight' para títulos (800 weight), 'Inter' para corpo.
- [ ] Números dinâmicos devem usar `tabular-nums`.
