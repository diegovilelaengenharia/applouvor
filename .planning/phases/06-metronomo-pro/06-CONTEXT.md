# Phase 6 Context — Metrônomo Pro

## Goal
Metrônomo funcional com Tap BPM, clique audível via Web Audio API, slider vertical, e integração com BPM da música selecionada — disponível offline no ensaio.

## Requirements
- MET-01: Tap BPM calcula média dos últimos 4+ toques e exibe BPM em tempo real
- MET-02: Clique audível (Web Audio API) — metrônomo funciona no ensaio sem fones
- MET-03: Slider vertical permite ajuste fino do BPM (40–220) com arraste; integração com BPM da música via `?bpm=X`

## Estado Atual — O que JÁ EXISTE

**Não existe:**
- `admin/metronomo.php` — não existe, criar do zero
- CSS dedicado para metrônomo — não existe
- Card "Metrônomo" no dashboard_cards.php — não existe
- `admin/metronomo.php` no cache do SW — não existe

**Existe:**
- `includes/dashboard_cards.php` — sistema de cards do dashboard (adicionar card metrônomo)
- `admin/index.php` — lista de cards por categoria (`gestao`)
- `sw.js` — service worker com `urlsToCache` (adicionar metronomo.php)
- `admin/musica_detalhe.php` — já tem BPM da música (campo `$song['bpm']`); pode linkar para metronomo

## Técnicas a Usar

### Web Audio API (sem dependências)
```js
const ctx = new AudioContext();
function tick() {
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain); gain.connect(ctx.destination);
    osc.type = 'sine'; osc.frequency.value = 880;
    gain.gain.setValueAtTime(0.3, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.08);
    osc.start(ctx.currentTime); osc.stop(ctx.currentTime + 0.1);
}
```

### Tap BPM (média das últimas 4+ batidas)
```js
let taps = [];
function tap() {
    const now = Date.now();
    if (taps.length && (now - taps[taps.length - 1]) > 3000) taps = [];
    taps.push(now);
    if (taps.length >= 2) {
        const intervals = taps.slice(1).map((t, i) => t - taps[i]);
        const avg = intervals.reduce((a, b) => a + b) / intervals.length;
        bpm = Math.round(60000 / avg);
    }
}
```

### Integração BPM de URL
```js
const urlBpm = new URLSearchParams(location.search).get('bpm');
if (urlBpm) bpm = Math.min(220, Math.max(40, parseInt(urlBpm)));
```

## Estrutura da Página

```
[Header com back para index.php]
[Display BPM grande — clicável para Tap]
[Botões: − | TAP | +]
[Slider horizontal (mobile) ou vertical]
[Botão Start/Stop central]
[Card de referência: X BPM = Y tempo (1 beat)]
```

## Integração no Dashboard e Página de Música

1. `includes/dashboard_cards.php` — adicionar card 'metronomo' na categoria 'gestao'
2. `admin/index.php` — adicionar 'metronomo' no array $groupedCards['gestao']
3. `admin/musica_detalhe.php` — adicionar link "Usar no metrônomo" se `$song['bpm']` existe
4. `sw.js` — adicionar `/admin/metronomo.php` em `urlsToCache`
