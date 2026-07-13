// Copyright (c) 2026 Vilela Engenharia. Ver LICENSE.
// Registry modular da Central — permite adicionar MÓDULOS independentes sem mexer no núcleo.
//
// Cada módulo (assets/js/modules/*.js) chama regModulo({...}) com:
//   id      : string única (casa com o `view`/`id` do nav)
//   state() : (opcional) objeto de estado Alpine que é mesclado no componente raiz
//   metodos : (opcional) objeto de métodos mesclado no componente raiz
//   aoEntrar(m): (opcional) chamado quando o usuário entra num módulo (m = módulo do nav)
//   nav     : (opcional) [{area:'pessoal'|'casa90'|..., item:{id,nome,ic,pronto}}] entradas de menu
//   template: (opcional) HTML da view (injetado em .main ANTES do Alpine iniciar)
//
// O núcleo (app.js) chama comporCentral(base) para fundir tudo, e montarViews() injeta as views.
(function () {
  window.CentralModules = window.CentralModules || [];
  window.regModulo = function (def) { window.CentralModules.push(def); };

  // Funde estado + métodos dos módulos no objeto base do Alpine e encadeia os hooks aoEntrar/nav.
  window.comporCentral = function (base) {
    const mods = window.CentralModules || [];
    mods.forEach(function (m) {
      if (m.state) Object.assign(base, m.state());
      // defineProperties (não Object.assign) para PRESERVAR getters sem invocá-los na cópia
      if (m.metodos) Object.defineProperties(base, Object.getOwnPropertyDescriptors(m.metodos));
      if (m.nav) m.nav.forEach(function (n) {
        const lista = { pessoal: base.modsPessoal, casa90: base.modsCasa90,
                        igreja: base.modsIgreja, trabalho: base.modsTrabalho }[n.area];
        if (lista && !lista.some(x => x.id === n.item.id)) lista.push(n.item);
      });
    });
    const _aoEntrar = base.aoEntrar;
    base.aoEntrar = function (m) {
      if (_aoEntrar) _aoEntrar.call(this, m);
      mods.forEach(function (mod) { if (mod.aoEntrar) mod.aoEntrar.call(this, m); }, this);
    };
    return base;
  };

  // Injeta as views (templates) dos módulos no <main>, antes do Alpine varrer o DOM.
  window.montarViews = function () {
    const host = document.querySelector('.main');
    if (!host) return;
    (window.CentralModules || []).forEach(function (m) {
      if (m.template && !document.getElementById('view-' + m.id)) {
        host.insertAdjacentHTML('beforeend', m.template);
      }
    });
  };
})();
