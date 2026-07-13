// Copyright (c) 2026 Diego Vilela — applouvor (gestão do Ministério de Louvor).
// Composer ENXUTO da gestão (equivalente ao app.js da Central, só o louvor).
// Monta o componente raiz do Alpine com o estado/métodos do módulo `louvor` (via
// registry.js/comporCentral) e injeta a view em <main.main> antes do Alpine iniciar.
function gestao() {
  const base = {
    // única "view": o centro de comando do louvor (o módulo usa x-show="view==='louvor'").
    view: 'louvor',
    // comporCentral espera estas listas para o merge de nav; aqui não há nav (app de 1 view).
    modsPessoal: [], modsCasa90: [], modsIgreja: [], modsTrabalho: [],
    iniciar() { this.aoEntrar({ id: this.view }); },
  };
  return (window.comporCentral ? window.comporCentral(base) : base);
}

// Injeta as views (templates) dos módulos registrados ANTES do Alpine varrer o DOM.
if (window.montarViews) window.montarViews();
