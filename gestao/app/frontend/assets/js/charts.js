/* Copyright (c) 2026 Vilela Engenharia. Ver LICENSE. */
/* ============================================================================
   CENTRALCHARTS — tema único dos gráficos ApexCharts (PLANO-VISUAL F8).
   Generaliza o padrão que nasceu em louvor.js: Apex não lê var() do CSS,
   então os tokens são RESOLVIDOS aqui (getComputedStyle) na hora do render —
   e o app re-renderiza os gráficos da view aberta ao alternar o tema.
   Regras (skill dataviz): paleta categórica em ORDEM FIXA (nunca ciclar);
   série semântica de dinheiro/erro usa pos/neg, não a categórica.
   ========================================================================== */
window.CentralCharts = (function () {
  const v = (nome) => getComputedStyle(document.documentElement).getPropertyValue(nome).trim();

  /** Tokens resolvidos AGORA (muda junto com o tema). */
  function tokens() {
    return {
      area: v('--area'), ink: v('--ink'), ink2: v('--ink-2'), muted: v('--muted'),
      line: v('--line'), line2: v('--line-2'), card: v('--card'), card2: v('--card-2'),
      pos: v('--pos'), neg: v('--neg'),
      viz: Array.from({ length: 8 }, (_, i) => v('--viz-' + (i + 1))),
    };
  }

  /** Primeiras n cores da categórica (ordem fixa). >8 séries = repense o gráfico. */
  function palette(n) {
    const p = tokens().viz;
    return n ? p.slice(0, Math.min(n, p.length)) : p.slice();
  }

  const modo = () => (document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light');

  /** Funde as opções do gráfico sobre a base temática — merge em DOIS níveis
      (Object.assign raso perderia chart.type quando a base define chart.*). */
  function base(opt) {
    const t = tokens();
    const b = {
      chart: { fontFamily: 'Outfit, sans-serif', foreColor: t.ink2, background: 'transparent', toolbar: { show: false } },
      grid: { borderColor: t.line },
      theme: { mode: modo() },
      tooltip: { theme: modo() },
      stroke: {},
      legend: { labels: { colors: t.ink2 } },
    };
    const saida = Object.assign({}, b, opt);
    for (const k of Object.keys(b)) {
      const bo = b[k], oo = opt ? opt[k] : undefined;
      if (oo && typeof oo === 'object' && !Array.isArray(oo) && typeof bo === 'object' && !Array.isArray(bo)) {
        saida[k] = Object.assign({}, bo, oo);
        // 2º nível (ex.: chart:{toolbar}, legend:{labels}) — opt vence campo a campo
        for (const k2 of Object.keys(bo)) {
          const b2 = bo[k2], o2 = oo[k2];
          if (o2 && typeof o2 === 'object' && !Array.isArray(o2) && typeof b2 === 'object' && !Array.isArray(b2)) {
            saida[k][k2] = Object.assign({}, b2, o2);
          }
        }
      }
    }
    return saida;
  }

  return { tokens, palette, base, modo };
})();
