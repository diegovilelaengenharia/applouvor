// Copyright (c) 2026 Vilela Engenharia. Ver LICENSE.
// Módulo "Escala de louvor" (área Igreja, lado líder) — Diego, PIB Oliveira.
// Centro de comando: escala (editar/gerar mês), estatísticas (ApexCharts), setlist+metrônomo,
// repertório inteligente, equipe+jejum, treinamento. Consome /api/louvor* (SSOT louvor.db).
regModulo({
  id: 'louvor',

  state() {
    return {
      lv: { ok: false, proximo: null, proximos: [], jejum: [], equipe: [], repertorio: [],
            indisponibilidade: [], avisos: [], devocional: [], treinamento: [], config: {} },
      lvCarregado: false, lvAba: 'escala', lvMsg: '',
      lvSug: null, lvGerando: false,
      lvEdit: null, lvSalvando: false,                          // editar culto
      lvMes: '', lvSobrescrever: false, lvGerandoMes: false,    // gerar o mês
      lvEstat: null, lvEstatCarregado: false, _lvCharts: {},    // estatísticas
      lvSetData: '', lvSet: [], lvSetCarregando: false, lvSetSalvando: false,  // setlist
      lvRepBusca: '', lvRepMomento: '', lvRepTom: '', lvRepEdit: null,         // repertório
      lvPalco: null,                                            // modo palco
      lvMetBpm: 100, lvMetOn: false, _lvMet: null, _lvTap: [],  // metrônomo
      lvBuscandoLetra: false,
      lvImgGerando: false, lvImgUrl: '',                        // imagem WhatsApp
    };
  },

  aoEntrar(m) { if (m.id === 'louvor') this.carregarLouvor(); },

  metodos: {
    async carregarLouvor() {
      if (this.lvCarregado && this.lv.ok) return;
      try { this.lv = await (await fetch('/api/louvor')).json(); }
      catch (e) { this.lv = { ok: false, aviso: 'Erro de rede ao carregar o louvor.', proximo: null, proximos: [], jejum: [], equipe: [], repertorio: [], indisponibilidade: [], avisos: [], devocional: [], treinamento: [], config: {} }; }
      finally {
        this.lvCarregado = true;
        if (!this.lvMes) this.lvMes = this.lvProxMes();
        if (!this.lvSetData && this.lv.proximo) this.lvSetData = this.lv.proximo.data;
      }
    },
    lvSetAba(a) {
      this.lvAba = a;
      if (a === 'estat') this.carregarEstatisticas();
      if (a === 'setlist' && this.lvSetData) this.lvCarregarSetlist();
    },
    async gerarEquipe() {
      this.lvGerando = true; this.lvSug = null;
      const d = this.lv.proximo ? this.lv.proximo.data : '';
      try { this.lvSug = await (await fetch('/api/louvor/gerar?data=' + encodeURIComponent(d))).json(); }
      catch (e) { this.lvSug = { ok: false, aviso: 'Erro ao gerar a sugestão.' }; }
      finally { this.lvGerando = false; }
    },
    lvDataBR(iso) { if (!iso) return ''; const p = iso.split('-'); return p.length === 3 ? p[2] + '/' + p[1] : iso; },
    lvProxMes() {
      const h = this.lv && this.lv.hoje ? new Date(this.lv.hoje + 'T00:00') : new Date();
      h.setDate(1); h.setMonth(h.getMonth() + 1);
      return h.getFullYear() + '-' + String(h.getMonth() + 1).padStart(2, '0');
    },
    lvEscalados(ev) {
      if (!ev) return [];
      const map = [['voz1', 'Voz 1'], ['voz2', 'Voz 2'], ['violao', 'Violão'], ['teclado', 'Teclado'],
                   ['baixo', 'Baixo'], ['guitarra', 'Guitarra'], ['bateria', 'Bateria']];
      return map.filter(([k]) => ev[k]).map(([k, f]) => ({ f, n: ev[k] }));
    },
    async lvDriveAbrir(caminho) {
      try { await fetch('/api/drive-abrir', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ caminho }) }); }
      catch (e) { this.lvMsg = '⛔ Não consegui abrir o arquivo.'; }
    },
    lvIniciais(n) { return (n || '').split(/\s+/).slice(0, 2).map(p => p[0] || '').join('').toUpperCase(); },
    lvBadgeCls(s) { return s.semanas == null ? 'never' : (s.semanas >= 8 ? 'old' : 'ok'); },
    lvIconeArq(cam) { return /\.pdf$/i.test(cam || '') ? '📕' : (/\.pptx?$/i.test(cam || '') ? '📊' : '📘'); },
    get lvTreinoGrupos() {
      const ordem = ['Seminário', 'Treinamento', 'Literatura'];
      const g = {};
      (this.lv.treinamento || []).forEach(t => { (g[t.tag] = g[t.tag] || []).push(t); });
      const tags = Object.keys(g).sort((a, b) => (ordem.indexOf(a) + 1 || 99) - (ordem.indexOf(b) + 1 || 99));
      return tags.map(tag => ({ tag, itens: g[tag] }));
    },

    // ── opções de cada posição (membros ativos; o valor atual sempre presente) ──
    lvAtivos() { return (this.lv.equipe || []).filter(m => m.disponivel); },
    lvOpcoes(pos, atual) {
      const ativos = this.lvAtivos();
      const ehVoz = m => /voz/i.test((m.funcao || '') + ' ' + (m.instrumento || ''));
      const tem = p => m => new RegExp(p, 'i').test((m.instrumento || '') + ' ' + (m.funcao || ''));
      let lista;
      if (pos === 'voz1') lista = ativos.filter(m => ehVoz(m) && m.genero === 'F');
      else if (pos === 'voz2') lista = ativos.filter(m => ehVoz(m) && m.genero === 'M');
      else if (pos === 'violao') lista = ativos.filter(tem('viol'));
      else if (pos === 'bateria') lista = ativos.filter(tem('bater'));
      else if (pos === 'teclado') lista = ativos.filter(tem('teclad'));
      else lista = ativos;
      const nomes = lista.map(m => m.nome);
      if (atual && !nomes.includes(atual)) nomes.unshift(atual);
      return nomes;
    },

    // ── editar culto ──
    lvCamposEdit() { return ['voz1', 'voz2', 'violao', 'teclado', 'baixo', 'guitarra', 'bateria']; },
    lvLabelPos(p) { return ({ voz1: 'Voz 1', voz2: 'Voz 2', violao: 'Violão', teclado: 'Teclado', baixo: 'Baixo', guitarra: 'Guitarra', bateria: 'Bateria' })[p] || p; },
    lvEditar(ev) {
      const base = { data: '', voz1: '', voz2: '', violao: '', teclado: '', baixo: '', guitarra: '', bateria: '', evento: 'Culto de domingo', obs: '' };
      this.lvEdit = Object.assign(base, ev || {}); this.lvMsg = '';
    },
    lvCancelarEdit() { this.lvEdit = null; },
    async lvSalvarEscala() {
      if (!this.lvEdit || !this.lvEdit.data) { this.lvMsg = 'Informe a data.'; return; }
      this.lvSalvando = true;
      try {
        const r = await (await fetch('/api/louvor/escala', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(this.lvEdit) })).json();
        if (r.ok) { this.lvEdit = null; await this.lvRecarregar(); this.lvMsg = '✅ Escala salva.'; }
        else { this.lvMsg = '⛔ ' + (r.erro || 'Falha ao salvar.'); }
      } catch (e) { this.lvMsg = '⛔ Erro de rede ao salvar.'; }
      finally { this.lvSalvando = false; }
    },
    async lvRecarregar() { this.lvCarregado = false; this.lv.ok = false; await this.carregarLouvor(); this.lvEstatCarregado = false; },

    // ── gerar o mês ──
    async lvGerarMes() {
      if (!this.lvMes) { this.lvMsg = 'Escolha o mês.'; return; }
      this.lvGerandoMes = true; this.lvMsg = '';
      try {
        const r = await (await fetch('/api/louvor/gerar-mes', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ mes: this.lvMes, sobrescrever: this.lvSobrescrever }) })).json();
        if (r.ok) { await this.lvRecarregar(); this.lvMsg = `✅ ${r.domingos} domingo(s) de ${this.lvMes} ${this.lvSobrescrever ? 'regerados' : 'preenchidos'}.`; }
        else { this.lvMsg = '⛔ ' + (r.erro || 'Falha ao gerar.'); }
      } catch (e) { this.lvMsg = '⛔ Erro de rede ao gerar o mês.'; }
      finally { this.lvGerandoMes = false; }
    },

    // ── imagem da escala para o WhatsApp ──
    async lvGerarImagem() {
      const d = this.lv.proximo ? this.lv.proximo.data : '';
      this.lvImgGerando = true; this.lvImgUrl = ''; this.lvMsg = '';
      try {
        const r = await (await fetch('/api/louvor/gerar-imagem', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ data: d }) })).json();
        if (r.ok) { this.lvImgUrl = r.url + '&t=' + Date.now(); this.lvMsg = '✅ Imagem da semana gerada.'; }
        else { this.lvMsg = '⛔ ' + (r.erro || 'Falha ao gerar a imagem.'); }
      } catch (e) { this.lvMsg = '⛔ Erro de rede ao gerar a imagem.'; }
      finally { this.lvImgGerando = false; }
    },
    lvFecharImagem() { this.lvImgUrl = ''; },

    // ── estatísticas + gráficos ──
    async carregarEstatisticas() {
      if (!this.lvEstatCarregado) {
        try { this.lvEstat = await (await fetch('/api/louvor/estatisticas')).json(); }
        catch (e) { this.lvEstat = { ok: false }; }
        finally { this.lvEstatCarregado = true; }
      }
      this.$nextTick(() => this.renderLouvorCharts());
    },
    _lvChart(id, opt) {
      const el = document.getElementById(id); if (!el || typeof ApexCharts === 'undefined') return;
      if (this._lvCharts[id]) this._lvCharts[id].destroy();
      // F8: tema/fonte/grid/tooltip vêm do CentralCharts (tokens resolvidos no render)
      this._lvCharts[id] = new ApexCharts(el, CentralCharts.base(opt));
      this._lvCharts[id].render();
    },
    renderLouvorCharts() {
      const e = this.lvEstat; if (!e || !e.ok) return;
      const T = CentralCharts.tokens();
      const AREA = T.area || '#79738f';
      const part = e.participacao.filter(p => p.ativo);
      this._lvChart('lv-chart-part', {
        chart: { type: 'bar', height: 300 },
        series: [{ name: 'Vezes', data: part.map(p => p.vezes) }],
        xaxis: { categories: part.map(p => p.nome) }, colors: [AREA],
        plotOptions: { bar: { horizontal: true, borderRadius: 5 } }, dataLabels: { enabled: true }, legend: { show: false },
      });
      this._lvChart('lv-chart-func', {
        chart: { type: 'donut', height: 300 },
        series: e.por_funcao.map(f => f.n), labels: e.por_funcao.map(f => f.funcao),
        colors: CentralCharts.palette(e.por_funcao.length),
        legend: { position: 'bottom' }, dataLabels: { enabled: true, formatter: v => v.toFixed(0) + '%' },
      });
      this._lvChart('lv-chart-mes', {
        chart: { type: 'area', height: 280 },
        series: [{ name: 'Cultos', data: e.carga_mensal.map(m => m.cultos) }],
        xaxis: { categories: e.carga_mensal.map(m => m.mes) }, colors: [AREA],
        stroke: { curve: 'smooth', width: 2 }, fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
        dataLabels: { enabled: false }, yaxis: { min: 0, forceNiceScale: true },
      });
      this._lvChart('lv-chart-equil', {
        chart: { type: 'radialBar', height: 300 },
        series: [e.equilibrio.feminino, e.equilibrio.masculino],
        // ♀/♂ é código SEMÂNTICO consagrado (rosa/azul), não série categórica — fixo de propósito
        labels: ['Vozes ♀', 'Vozes ♂'], colors: ['#ec4899', '#3b82f6'],
        plotOptions: { radialBar: { dataLabels: { value: { formatter: v => v + '%' }, total: { show: true, label: 'Equilíbrio', formatter: () => Math.round((e.equilibrio.feminino + e.equilibrio.masculino) / 2) + '%' } } } },
      });
      const disp = e.disponibilidade;
      this._lvChart('lv-chart-disp', {
        chart: { type: 'donut', height: 280 },
        series: [disp.ativos, ...disp.motivos.map(m => m.n)],
        labels: ['Ativos', ...disp.motivos.map(m => m.motivo)],
        // Ativos = semântico (--pos); motivos de ausência = categórica a partir do âmbar
        colors: [T.pos, ...T.viz.slice(2, 2 + disp.motivos.length)], legend: { position: 'bottom' },
      });
      const ta = e.repertorio.top_artistas;
      this._lvChart('lv-chart-rep', {
        chart: { type: 'bar', height: 300 },
        series: [{ name: 'Músicas', data: ta.map(a => a.n) }],
        xaxis: { categories: ta.map(a => a.artista) }, colors: [T.viz[1]],
        plotOptions: { bar: { horizontal: true, borderRadius: 5 } }, dataLabels: { enabled: true }, legend: { show: false },
      });
    },

    // ── repertório inteligente ──
    get lvMomentos() { const c = this.lv.config || {}; return c.momentos_padrao || ['Abertura', 'Adoração', 'Comunhão/Ceia', 'Ofertório', 'Final']; },
    get lvToms() { return [...new Set((this.lv.repertorio || []).map(s => s.tom).filter(Boolean))].sort(); },
    get lvRepFiltrado() {
      const q = (this.lvRepBusca || '').toLowerCase();
      return (this.lv.repertorio || []).filter(s => {
        if (this.lvRepMomento && s.momento !== this.lvRepMomento) return false;
        if (this.lvRepTom && s.tom !== this.lvRepTom) return false;
        if (q && !((s.musica || '') + ' ' + (s.artista || '')).toLowerCase().includes(q)) return false;
        return true;
      });
    },
    lvSemanasTxt(s) { if (s.semanas == null) return 'nunca'; if (s.semanas === 0) return 'esta semana'; return 'há ' + s.semanas + ' sem'; },
    lvEditarMusica(s) { this.lvRepEdit = Object.assign({ musica: '', artista: '', tom: '', bpm: '', momento: '', letra: '', cifra: '', audio: '', video: '', tags: '', obs: '' }, s || {}); this.lvMsg = ''; },
    async lvSalvarMusica() {
      if (!this.lvRepEdit.musica) { this.lvMsg = 'Informe o título.'; return; }
      try {
        const r = await (await fetch('/api/louvor/repertorio', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(this.lvRepEdit) })).json();
        if (r.ok) { this.lvRepEdit = null; await this.lvRecarregar(); this.lvMsg = '✅ Música salva.'; }
        else { this.lvMsg = '⛔ ' + (r.erro || 'Falha.'); }
      } catch (e) { this.lvMsg = '⛔ Erro de rede.'; }
    },
    // ── edição inline rápida (preencher momento/tom em lote, sem abrir o modal) ──
    async lvSalvarCampo(musica, campo, valor) {
      if (!musica) return;
      // otimista: reflete já na lista local
      const s = (this.lv.repertorio || []).find(m => m.musica === musica);
      if (s) s[campo] = valor;
      try {
        const r = await (await fetch('/api/louvor/repertorio', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ musica, [campo]: valor }) })).json();
        if (!r.ok) this.lvMsg = '⛔ ' + (r.erro || 'Falha ao salvar.');
      } catch (e) { this.lvMsg = '⛔ Erro de rede ao salvar.'; }
    },
    get lvRepFaltando() {
      // quantas músicas ainda sem momento/tom (para o aviso "preencha para ativar o filtro")
      const r = this.lv.repertorio || [];
      return { semMomento: r.filter(s => !s.momento).length, semTom: r.filter(s => !s.tom).length, total: r.length };
    },

    async lvBuscarLetraInternet() {
      const mus = this.lvRepEdit.musica;
      const art = this.lvRepEdit.artista;
      if (!mus) { this.lvMsg = '⛔ Informe o título da música para buscar.'; return; }
      this.lvBuscandoLetra = true;
      this.lvMsg = '🔎 Buscando letra na internet...';
      try {
        const url = `/api/louvor/buscar-letra?musica=${encodeURIComponent(mus)}&artista=${encodeURIComponent(art)}`;
        const r = await (await fetch(url)).json();
        if (r.ok && r.letra) {
          this.lvRepEdit.letra = r.letra;
          this.lvMsg = '✅ Letra preenchida!';
        } else {
          this.lvMsg = '⛔ ' + (r.erro || 'Letra não encontrada.');
        }
      } catch (e) {
        this.lvMsg = '⛔ Erro de rede ao buscar letra.';
      } finally {
        this.lvBuscandoLetra = false;
      }
    },

    // ── setlist ──
    async lvCarregarSetlist() {
      if (!this.lvSetData) return;
      this.lvSetCarregando = true;
      try { const d = await (await fetch('/api/louvor/setlist?data=' + encodeURIComponent(this.lvSetData))).json(); this.lvSet = (d.itens || []).map(it => ({ musica: it.musica, momento: it.momento, obs: it.obs })); }
      catch (e) { this.lvSet = []; }
      finally { this.lvSetCarregando = false; }
    },
    lvSetAdd(musica) { this.lvSet.push({ musica: musica || '', momento: this.lvMomentos[0] || '', obs: '' }); },
    lvSetRem(i) { this.lvSet.splice(i, 1); },
    lvSetMove(i, d) { const j = i + d; if (j < 0 || j >= this.lvSet.length) return; const t = this.lvSet[i]; this.lvSet[i] = this.lvSet[j]; this.lvSet[j] = t; },
    async lvSalvarSetlist() {
      if (!this.lvSetData) { this.lvMsg = 'Escolha a data do culto.'; return; }
      this.lvSetSalvando = true;
      try {
        const itens = this.lvSet.filter(x => x.musica).map((x, i) => ({ musica: x.musica, momento: x.momento, obs: x.obs, ordem: i }));
        const r = await (await fetch('/api/louvor/setlist', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ data: this.lvSetData, itens }) })).json();
        if (r.ok) { await this.lvRecarregar(); this.lvMsg = `✅ Setlist de ${this.lvDataBR(this.lvSetData)} salva (${r.itens} músicas).`; }
        else { this.lvMsg = '⛔ ' + (r.erro || 'Falha.'); }
      } catch (e) { this.lvMsg = '⛔ Erro de rede.'; }
      finally { this.lvSetSalvando = false; }
    },

    // ── modo palco ──
    lvAbrirPalco(s) { this.lvPalco = s; if (s && s.bpm) this.lvMetBpm = parseInt(s.bpm) || this.lvMetBpm; },
    lvFecharPalco() { this.lvFecharMet(); this.lvPalco = null; },

    // ── metrônomo (Web Audio) ──
    lvTap() {
      const now = performance.now(); this._lvTap.push(now); this._lvTap = this._lvTap.filter(t => now - t < 3000);
      if (this._lvTap.length >= 2) {
        const difs = []; for (let i = 1; i < this._lvTap.length; i++) difs.push(this._lvTap[i] - this._lvTap[i - 1]);
        const med = difs.reduce((a, b) => a + b, 0) / difs.length;
        this.lvMetBpm = Math.max(30, Math.min(240, Math.round(60000 / med)));
        this.lvMetReinicia();
      }
    },
    _lvClick() {
      try {
        const Ctx = window.AudioContext || window.webkitAudioContext; if (!Ctx) return;
        this._lvAudio = this._lvAudio || new Ctx();
        const o = this._lvAudio.createOscillator(), g = this._lvAudio.createGain();
        o.frequency.value = 1000; g.gain.value = 0.25; o.connect(g); g.connect(this._lvAudio.destination);
        const t = this._lvAudio.currentTime; o.start(t); g.gain.exponentialRampToValueAtTime(0.001, t + 0.05); o.stop(t + 0.05);
      } catch (e) {}
    },
    lvMetToggle() {
      if (this.lvMetOn) { this.lvFecharMet(); return; }
      this.lvMetOn = true; this._lvClick();
      this._lvMet = setInterval(() => this._lvClick(), 60000 / this.lvMetBpm);
    },
    lvMetReinicia() { if (this.lvMetOn) { clearInterval(this._lvMet); this._lvMet = setInterval(() => this._lvClick(), 60000 / this.lvMetBpm); } },
    lvFecharMet() { this.lvMetOn = false; if (this._lvMet) { clearInterval(this._lvMet); this._lvMet = null; } },
  },

  template: `
  <div id="view-louvor" x-show="view==='louvor'">
    <h1 class="titulo">🎵 Ministério de Louvor <span class="area-pill">PIB Oliveira</span></h1>
    <div class="spin" x-show="!lvCarregado">Carregando o louvor…</div>
    <div class="aviso-box bad" x-show="lvCarregado && !lv.ok"><h3>⛔ <span x-text="lv.aviso"></span></h3></div>

    <template x-if="lvCarregado && lv.ok">
      <div>
        <div class="lv-tabs">
          <button class="lv-tab" :class="{on:lvAba==='escala'}"     @click="lvSetAba('escala')">🗓️ Escala</button>
          <button class="lv-tab" :class="{on:lvAba==='estat'}"      @click="lvSetAba('estat')">📊 Estatísticas</button>
          <button class="lv-tab" :class="{on:lvAba==='setlist'}"    @click="lvSetAba('setlist')">🎼 Setlist</button>
          <button class="lv-tab" :class="{on:lvAba==='repertorio'}" @click="lvSetAba('repertorio')">🎵 Repertório</button>
          <button class="lv-tab" :class="{on:lvAba==='equipe'}"     @click="lvSetAba('equipe')">👥 Equipe</button>
          <button class="lv-tab" :class="{on:lvAba==='treino'}"     @click="lvSetAba('treino')">🎓 Treino</button>
        </div>

        <div class="lv-flash" x-show="lvMsg" x-text="lvMsg"></div>

        <!-- ═══════════════ ESCALA ═══════════════ -->
        <div x-show="lvAba==='escala'">
          <template x-if="lv.proximo">
            <div class="lv-hero">
              <div style="min-width:200px">
                <div class="lbl">Próximo culto</div>
                <div class="data" x-text="lvDataBR(lv.proximo.data)"></div>
                <div class="obs" x-show="lv.proximo.obs" x-text="lv.proximo.obs"></div>
                <div class="lv-slots">
                  <template x-for="r in lvEscalados(lv.proximo)" :key="r.f+r.n">
                    <div class="lv-slot"><b x-text="r.f"></b><span x-text="r.n"></span></div>
                  </template>
                </div>
              </div>
              <div class="acts">
                <button class="lv-btn ghost" @click="gerarEquipe()" :disabled="lvGerando" x-text="lvGerando ? 'Gerando…' : '🤖 Sugerir equipe'"></button>
                <button class="lv-btn ghost" @click="lvEditar(lv.proximo)">✏️ Editar culto</button>
                <button class="lv-btn ghost" @click="lvSetData=lv.proximo.data; lvSetAba('setlist')">🎼 Montar setlist</button>
                <button class="lv-btn ghost" @click="lvGerarImagem()" :disabled="lvImgGerando" x-text="lvImgGerando ? 'Gerando…' : '🖼️ Imagem WhatsApp'"></button>
              </div>
            </div>
          </template>
          <template x-if="!lv.proximo"><div class="lv-empty"><div class="big">🗓️</div>Nenhum culto futuro preenchido. Gere o mês abaixo ou edite um domingo.</div></template>

          <div class="lv-kpis">
            <div class="lv-kpi"><div class="n" x-text="(lv.meta?lv.meta.futuros:0)"></div><div class="l">cultos à frente</div></div>
            <div class="lv-kpi"><div class="n" x-text="(lv.meta?lv.meta.equipe_ativa:0)"></div><div class="l">na equipe ativa</div></div>
            <div class="lv-kpi"><div class="n" x-text="lv.repertorio.length"></div><div class="l">músicas no repertório</div></div>
            <div class="lv-kpi"><div class="n" style="font-size:1.05rem;font-weight:700" x-text="lv.jejum.length?lv.jejum[0].pessoas:'—'"></div><div class="l">jejum desta semana</div></div>
          </div>

          <div class="lv-grid2 wide">
            <div class="lv-sec">
              <h3>⚙️ Gerar o mês <span class="c">rodízio justo</span></h3>
              <div class="lv-toolbar">
                <input type="month" x-model="lvMes" class="lv-in">
                <label class="lv-hint" style="display:flex;align-items:center;gap:5px"><input type="checkbox" x-model="lvSobrescrever"> regerar tudo</label>
                <button class="lv-btn solid" @click="lvGerarMes()" :disabled="lvGerandoMes" x-text="lvGerandoMes ? 'Gerando…' : '🤖 Gerar'"></button>
              </div>
              <div class="lv-hint" style="margin-top:8px">Preenche as lacunas respeitando fixos, folgas e o revezamento. "Regerar" sobrescreve o mês inteiro.</div>
            </div>
            <div class="lv-sec" x-show="lvSug">
              <h3>🤖 Sugestão <span class="c" x-show="lvSug && lvSug.ok" x-text="lvSug?.data"></span></h3>
              <template x-if="lvSug && lvSug.ok">
                <div>
                  <template x-for="f in lvSug.sugestao" :key="f.funcao">
                    <div style="margin-bottom:7px">
                      <div class="lv-hint" style="font-weight:700;color:var(--ink)" x-text="f.funcao"></div>
                      <template x-if="!f.candidatos.length"><span class="lv-hint">— sem candidato</span></template>
                      <template x-for="(c,i) in f.candidatos.slice(0,3)" :key="c.nome">
                        <span class="lv-chip" :style="i===0?'background:#dcfce7;color:#15803d':''" x-text="c.nome+' · '+c.vezes+'x'" style="margin:2px 4px 2px 0;display:inline-block"></span>
                      </template>
                    </div>
                  </template>
                </div>
              </template>
            </div>
          </div>

          <template x-if="lvEdit">
            <div class="lv-sec" style="margin-top:16px;border:1px solid var(--area)">
              <h3>✏️ Editar culto <span class="c" x-text="lvDataBR(lvEdit.data)||'(nova data)'"></span></h3>
              <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px 14px" class="lv-people">
                <label class="lv-hint" x-show="!lvEdit.data" style="grid-column:1/-1">Data <input type="date" x-model="lvEdit.data" class="lv-in" style="width:100%"></label>
                <template x-for="pos in lvCamposEdit()" :key="pos">
                  <label class="lv-hint"><span x-text="lvLabelPos(pos)"></span>
                    <select x-model="lvEdit[pos]" class="lv-in" style="width:100%"><option value="">—</option>
                      <template x-for="n in lvOpcoes(pos, lvEdit[pos])" :key="n"><option :value="n" x-text="n"></option></template>
                    </select>
                  </label>
                </template>
                <label class="lv-hint" style="grid-column:1/-1">Observação <input type="text" x-model="lvEdit.obs" class="lv-in" style="width:100%"></label>
              </div>
              <div style="margin-top:14px;display:flex;gap:8px">
                <button class="lv-btn solid" @click="lvSalvarEscala()" :disabled="lvSalvando" x-text="lvSalvando?'Salvando…':'💾 Salvar'"></button>
                <button class="lv-btn line" @click="lvCancelarEdit()">Cancelar</button>
              </div>
            </div>
          </template>

          <div class="lv-sec" style="margin-top:16px" x-show="lv.proximos.length>1">
            <h3>📋 Próximos cultos <span class="c">clique para editar</span></h3>
            <div class="lv-songs">
              <template x-for="ev in lv.proximos" :key="ev.data">
                <div class="lv-song" @click="lvEditar(ev)" style="cursor:pointer">
                  <div class="t" x-text="lvDataBR(ev.data)"></div>
                  <div class="a" x-text="lvEscalados(ev).map(r=>r.n).join(' · ')"></div>
                  <div class="lv-row2" x-show="ev.obs"><span class="lv-chip" x-text="ev.obs"></span></div>
                </div>
              </template>
            </div>
          </div>
        </div>

        <!-- ═══════════════ ESTATÍSTICAS ═══════════════ -->
        <div x-show="lvAba==='estat'">
          <template x-if="lvEstat && lvEstat.ok">
            <div class="lv-charts">
              <div class="lv-chartcard"><h3>🏆 Participação no ano (ativos)</h3><div id="lv-chart-part"></div></div>
              <div class="lv-chartcard"><h3>⚖️ Equilíbrio do rodízio de vozes</h3><div id="lv-chart-equil"></div></div>
              <div class="lv-chartcard"><h3>🎚️ Slots por função</h3><div id="lv-chart-func"></div></div>
              <div class="lv-chartcard"><h3>👥 Disponibilidade da equipe</h3><div id="lv-chart-disp"></div></div>
              <div class="lv-chartcard"><h3>📅 Carga de cultos por mês</h3><div id="lv-chart-mes"></div></div>
              <div class="lv-chartcard"><h3 x-text="'🎤 Top artistas · '+lvEstat.repertorio.total+' músicas ('+lvEstat.repertorio.com_link+' c/ vídeo)'"></h3><div id="lv-chart-rep"></div></div>
            </div>
          </template>
          <template x-if="lvEstat && !lvEstat.ok"><div class="lv-empty"><div class="big">📊</div>Sem dados de estatística.</div></template>
        </div>

        <!-- ═══════════════ SETLIST ═══════════════ -->
        <div x-show="lvAba==='setlist'">
          <div class="lv-sec">
            <div class="lv-toolbar">
              <span class="lbl" style="font-weight:700;display:flex;align-items:center;gap:7px">🎼 Setlist do culto</span>
              <input type="date" x-model="lvSetData" @change="lvCarregarSetlist()" class="lv-in">
              <button class="lv-btn line" @click="lvSetAdd('')">＋ Música</button>
              <button class="lv-btn solid" @click="lvSalvarSetlist()" :disabled="lvSetSalvando" x-text="lvSetSalvando?'Salvando…':'💾 Salvar setlist'"></button>
              <span class="lv-hint">Salvar registra o "já cantada".</span>
            </div>
          </div>

          <div class="lv-grid2 wide" style="margin-top:16px">
            <div class="lv-sec">
              <h3>Ordem do culto <span class="c" x-text="lvSet.length+' músicas'"></span></h3>
              <template x-if="!lvSet.length"><div class="lv-hint">Vazio — adicione (＋) ou clique numa música do repertório abaixo.</div></template>
              <template x-for="(it,i) in lvSet" :key="i">
                <div class="lv-setrow">
                  <div class="ord" x-text="i+1"></div>
                  <input type="text" x-model="it.musica" class="lv-in" style="flex:1" placeholder="música">
                  <select x-model="it.momento" class="lv-in"><template x-for="m in lvMomentos" :key="m"><option :value="m" x-text="m"></option></template></select>
                  <button class="lv-btn line" @click="lvSetMove(i,-1)" title="subir">▲</button>
                  <button class="lv-btn line" @click="lvSetMove(i,1)" title="descer">▼</button>
                  <button class="lv-btn line" @click="lvSetRem(i)" title="remover">✕</button>
                </div>
              </template>
            </div>
            <div class="lv-sec">
              <h3>🥁 Metrônomo</h3>
              <div class="lv-met">
                <div class="lv-bpm" x-text="lvMetBpm"></div>
                <div class="lv-hint" style="margin-top:-6px">BPM</div>
                <input type="range" min="40" max="200" x-model.number="lvMetBpm" @input="lvMetReinicia()" style="width:100%">
                <div style="display:flex;gap:8px;justify-content:center;margin-top:6px">
                  <button class="lv-btn solid" @click="lvMetToggle()" x-text="lvMetOn?'⏸ Parar':'▶ Iniciar'"></button>
                  <button class="lv-btn line" @click="lvTap()">👆 Tap tempo</button>
                </div>
              </div>
            </div>
          </div>

          <div class="lv-sec" style="margin-top:16px">
            <h3>＋ Adicionar do repertório</h3>
            <input type="text" x-model="lvRepBusca" class="lv-in" style="width:100%;margin-bottom:10px" placeholder="🔎 buscar música ou artista…">
            <div class="lv-songs" style="max-height:340px;overflow:auto">
              <template x-for="s in lvRepFiltrado.slice(0,90)" :key="s.musica">
                <div class="lv-song" @click="lvSetAdd(s.musica)" style="cursor:pointer">
                  <div class="t" x-text="s.musica"></div>
                  <div class="a" x-show="s.artista" x-text="s.artista"></div>
                </div>
              </template>
            </div>
          </div>
        </div>

        <!-- ═══════════════ REPERTÓRIO ═══════════════ -->
        <div x-show="lvAba==='repertorio'">
          <div class="lv-sec">
            <div class="lv-toolbar">
              <input type="text" x-model="lvRepBusca" class="lv-in" style="flex:1;min-width:160px" placeholder="🔎 buscar música ou artista…">
              <select x-model="lvRepMomento" class="lv-in"><option value="">Momento (todos)</option><template x-for="m in lvMomentos" :key="m"><option :value="m" x-text="m"></option></template></select>
              <select x-model="lvRepTom" class="lv-in"><option value="">Tom (todos)</option><template x-for="t in lvToms" :key="t"><option :value="t" x-text="t"></option></template></select>
              <button class="lv-btn solid" @click="lvEditarMusica(null)">＋ Música</button>
            </div>
            <div class="lv-hint" style="margin-top:10px;display:flex;align-items:center;gap:6px"
                 x-show="lvRepFaltando.semMomento || lvRepFaltando.semTom">
              💡 Dica: <span x-text="lvRepFaltando.semMomento"></span> música(s) sem <b>momento</b> e
              <span x-text="lvRepFaltando.semTom"></span> sem <b>tom</b>. Toque nos campos pontilhados de cada
              música abaixo para preencher — assim os filtros "o que cantar" ficam afiados.
            </div>
          </div>

          <template x-if="lvRepEdit">
            <div class="lv-sec" style="margin-top:16px;border:1px solid var(--area)">
              <h3>🎵 <span x-text="lvRepEdit.musica?'Editar música':'Nova música'"></span></h3>
              <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px 14px">
                <label class="lv-hint" style="grid-column:1/-1">Título <input type="text" x-model="lvRepEdit.musica" class="lv-in" style="width:100%"></label>
                <label class="lv-hint">Artista <input type="text" x-model="lvRepEdit.artista" class="lv-in" style="width:100%"></label>
                <label class="lv-hint">Tom <input type="text" x-model="lvRepEdit.tom" class="lv-in" style="width:100%"></label>
                <label class="lv-hint">BPM <input type="text" x-model="lvRepEdit.bpm" class="lv-in" style="width:100%"></label>
                <label class="lv-hint">Momento <select x-model="lvRepEdit.momento" class="lv-in" style="width:100%"><option value="">—</option><template x-for="m in lvMomentos" :key="m"><option :value="m" x-text="m"></option></template></select></label>
                <label class="lv-hint">Cifra (link) <input type="text" x-model="lvRepEdit.cifra" class="lv-in" style="width:100%"></label>
                <label class="lv-hint">Vídeo (link) <input type="text" x-model="lvRepEdit.video" class="lv-in" style="width:100%"></label>
                <label class="lv-hint">Áudio (link) <input type="text" x-model="lvRepEdit.audio" class="lv-in" style="width:100%"></label>
                <label class="lv-hint">Tags <input type="text" x-model="lvRepEdit.tags" class="lv-in" style="width:100%" placeholder="ex: ceia, celebração"></label>
                <label class="lv-hint" style="grid-column:1/-1">Observação <input type="text" x-model="lvRepEdit.obs" class="lv-in" style="width:100%"></label>
                <label class="lv-hint" style="grid-column:1/-1">
                  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                    <span>Letra</span>
                    <button type="button" class="lv-btn line" style="padding:2px 8px;font-size:0.75rem" @click="lvBuscarLetraInternet()" :disabled="lvBuscandoLetra" x-text="lvBuscandoLetra ? 'Buscando…' : '🔍 Buscar na Internet'"></button>
                  </div>
                  <textarea x-model="lvRepEdit.letra" class="lv-in" style="width:100%;height:130px;font-family:monospace;line-height:1.4" placeholder="Letra da canção..."></textarea>
                </label>
              </div>
              <div style="margin-top:14px;display:flex;gap:8px">
                <button class="lv-btn solid" @click="lvSalvarMusica()">💾 Salvar</button>
                <button class="lv-btn line" @click="lvRepEdit=null">Cancelar</button>
              </div>
            </div>
          </template>

          <div class="lv-sec" style="margin-top:16px">
            <h3>🎵 Repertório <span class="c" x-text="lvRepFiltrado.length+' de '+lv.repertorio.length"></span></h3>
            <div class="lv-songs">
              <template x-for="s in lvRepFiltrado" :key="s.musica">
                <div class="lv-song">
                  <div class="t" @click="lvAbrirPalco(s)" x-text="s.musica"></div>
                  <div class="a" x-show="s.artista" x-text="s.artista"></div>
                  <div class="lv-row2">
                    <!-- edição inline: momento + tom (preencher em lote, sem abrir o modal) -->
                    <select class="lv-inline" :class="{vazio:!s.momento}" x-model="s.momento" @change="lvSalvarCampo(s.musica,'momento',s.momento)" title="Momento do culto">
                      <option value="">+ momento</option>
                      <template x-for="m in lvMomentos" :key="m"><option :value="m" x-text="m"></option></template>
                    </select>
                    <input class="lv-inline tom" :class="{vazio:!s.tom}" x-model="s.tom" @change="lvSalvarCampo(s.musica,'tom',s.tom)" placeholder="+ tom" title="Tom" style="width:62px">
                    <span class="lv-chip bpm" x-show="s.bpm" x-text="s.bpm+' BPM'"></span>
                    <span class="lv-badge" :class="lvBadgeCls(s)" x-show="s.semanas != null" x-text="lvSemanasTxt(s)"></span>
                    <template x-if="s.tags">
                      <span class="lv-chip" style="background:#f1f5f9;color:#475569" x-text="s.tags"></span>
                    </template>
                    <span class="lv-ico">
                      <a x-show="s.audio" :href="s.audio" target="_blank" rel="noopener" title="Áudio">🎧</a>
                      <a x-show="s.cifra" :href="s.cifra" target="_blank" rel="noopener" title="Cifra">🎸</a>
                      <a x-show="s.video" :href="s.video" target="_blank" rel="noopener" title="Vídeo">▶️</a>
                      <span class="ib" @click="lvSetAdd(s.musica); lvSetAba('setlist')" title="setlist">＋</span>
                      <span class="ib" @click="lvEditarMusica(s)" title="editar">✏️</span>
                    </span>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>

        <!-- ═══════════════ EQUIPE + JEJUM ═══════════════ -->
        <div x-show="lvAba==='equipe'">
          <div class="lv-grid2 wide">
            <div class="lv-sec">
              <h3>👥 Equipe <span class="c" x-text="(lv.meta?lv.meta.equipe_ativa:0)+' ativos de '+lv.equipe.length"></span></h3>
              <div class="lv-people">
                <template x-for="m in lv.equipe" :key="m.nome">
                  <div class="lv-person" :class="{off:!m.disponivel}">
                    <div class="lv-ava" x-text="lvIniciais(m.nome)"></div>
                    <div style="min-width:0">
                      <div class="pn" x-text="m.nome"></div>
                      <div class="pf" x-text="m.instrumento||m.funcao"></div>
                    </div>
                    <div class="st" x-show="!m.disponivel" :title="m.disponibilidade" x-text="'⏸ '+m.disponibilidade"></div>
                  </div>
                </template>
              </div>
            </div>
            <div class="lv-sec">
              <h3>🙏 Jejum <span class="c">revezamento</span></h3>
              <template x-if="!lv.jejum.length"><div class="lv-hint">Sem semanas à frente.</div></template>
              <template x-for="(j,i) in lv.jejum" :key="j.inicio">
                <div class="lv-jejum" :class="{now:i===0}">
                  <span class="who" x-text="j.pessoas"></span>
                  <span class="wk" x-text="lvDataBR(j.inicio)+'–'+lvDataBR(j.fim)+(i===0?' · agora':'')"></span>
                </div>
              </template>
            </div>
          </div>
        </div>

        <!-- ═══════════════ TREINO ═══════════════ -->
        <div x-show="lvAba==='treino'">
          <template x-if="!lv.treinamento.length"><div class="lv-empty"><div class="big">🎓</div>Sem material indexado. Rode <code>py louvor_db.py --ingerir-treinamento</code>.</div></template>
          <div class="lv-sec" x-show="lv.treinamento.length">
            <h3>🎓 Biblioteca de treinamento <span class="c" x-text="lv.treinamento.length+' itens · abre no desktop'"></span></h3>
            <template x-for="g in lvTreinoGrupos" :key="g.tag">
              <div>
                <div class="lv-grp-h" x-text="g.tag"></div>
                <div class="lv-files">
                  <template x-for="t in g.itens" :key="t.id">
                    <div class="lv-file" @click="lvDriveAbrir(t.caminho)">
                      <span class="fi" x-text="lvIconeArq(t.caminho)"></span>
                      <span class="ft" x-text="t.titulo"></span>
                    </div>
                  </template>
                </div>
              </div>
            </template>
          </div>
        </div>

      </div>
    </template>

    <!-- preview da imagem do WhatsApp (overlay) -->
    <template x-if="lvImgUrl">
      <div class="lv-palco" @click.self="lvFecharImagem()">
        <div style="text-align:center;max-width:420px;width:100%;display:flex;flex-direction:column;gap:14px;align-items:center">
          <img :src="lvImgUrl" alt="Escala da semana" style="max-width:100%;max-height:74vh;border-radius:14px;box-shadow:0 10px 40px rgba(0,0,0,.4)">
          <div class="pchips">
            <a :href="lvImgUrl" download class="lv-btn solid">⬇️ Baixar</a>
            <button class="lv-btn ghost" @click="lvFecharImagem()">✕ Fechar</button>
          </div>
          <div class="lv-hint" style="color:rgba(255,255,255,.7)">Baixe e envie no grupo do WhatsApp.</div>
        </div>
      </div>
    </template>

    <!-- modo palco (overlay) -->
    <template x-if="lvPalco">
      <div class="lv-palco" @click.self="lvFecharPalco()">
        <div style="text-align:center;max-width:720px;width:100%;display:flex;flex-direction:column;max-height:92vh">
          <div class="pt" x-text="lvPalco.musica"></div>
          <div class="pa" x-text="lvPalco.artista"></div>
          <div class="pchips">
            <span class="pchip" style="background:#7c3aed" x-show="lvPalco.tom" x-text="'Tom '+lvPalco.tom"></span>
            <span class="pchip" style="background:#0ea5e9" x-text="lvMetBpm+' BPM'"></span>
          </div>
          
          <div class="lv-palco-letra" x-show="lvPalco.letra" x-text="lvPalco.letra"></div>
          <div x-show="!lvPalco.letra" style="margin-top:24px;opacity:0.4;font-style:italic">Sem letra cadastrada no repertório.</div>

          <div class="pchips" style="margin-top:auto;padding-top:16px">
            <button class="lv-btn solid" @click="lvMetToggle()" x-text="lvMetOn?'⏸ Metrônomo':'▶ Metrônomo'"></button>
            <button class="lv-btn ghost" @click="lvTap()">👆 Tap</button>
            <a x-show="lvPalco.cifra" :href="lvPalco.cifra" target="_blank" rel="noopener" class="lv-btn ghost">🎸 Cifra</a>
            <a x-show="lvPalco.video" :href="lvPalco.video" target="_blank" rel="noopener" class="lv-btn ghost">▶️ Vídeo</a>
            <button class="lv-btn ghost" @click="lvFecharPalco()">✕ Fechar</button>
          </div>
          <input type="range" min="40" max="200" x-model.number="lvMetBpm" @input="lvMetReinicia()" style="width:60%;margin-top:16px;align-self:center">
        </div>
      </div>
    </template>
  </div>`,
});
