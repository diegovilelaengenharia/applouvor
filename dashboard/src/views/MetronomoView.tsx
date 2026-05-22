import React, { useState, useEffect, useRef } from 'react';
import { 
  Play, 
  Square, 
  Plus, 
  Minus, 
  Clock, 
  Volume2, 
  VolumeX, 
  Activity
} from 'lucide-react';

export const MetronomoView: React.FC = () => {
  const [bpm, setBpm] = useState<number>(120);
  const [isPlaying, setIsPlaying] = useState<boolean>(false);
  const [timeSignature, setTimeSignature] = useState<number>(4); // 2, 3, 4, 6
  const [currentBeat, setCurrentBeat] = useState<number>(0);
  const [soundType, setSoundType] = useState<'classic' | 'woodblock' | 'digital'>('woodblock');
  const [isMuted, setIsMuted] = useState<boolean>(false);

  // Estados adicionais
  const [tapTimes, setTapTimes] = useState<number[]>([]);
  const [flash, setFlash] = useState<boolean>(false);

  // Referências para Web Audio API e agendamento preciso
  const audioContextRef = useRef<AudioContext | null>(null);
  const nextNoteTimeRef = useRef<number>(0);
  const currentBeatRef = useRef<number>(0);
  const timerIdRef = useRef<number | null>(null);
  const isPlayingRef = useRef<boolean>(false);
  const bpmRef = useRef<number>(bpm);
  const timeSignatureRef = useRef<number>(timeSignature);

  // Sincronizar referências mutáveis com estados do React para o loop de áudio ler sem atraso
  useEffect(() => {
    bpmRef.current = bpm;
  }, [bpm]);

  useEffect(() => {
    timeSignatureRef.current = timeSignature;
  }, [timeSignature]);

  // Limpeza ao desmontar
  useEffect(() => {
    return () => {
      stopMetronome();
    };
  }, []);

  // Pausar metrônomo se a aba perder o foco (segurança de dessincronização visual)
  useEffect(() => {
    const handleVisibilityChange = () => {
      if (document.hidden && isPlayingRef.current) {
        stopMetronome();
      }
    };

    document.addEventListener('visibilitychange', handleVisibilityChange);
    return () => {
      document.removeEventListener('visibilitychange', handleVisibilityChange);
    };
  }, []);

  // Inicializar AudioContext sob demanda
  const initAudio = () => {
    if (!audioContextRef.current) {
      audioContextRef.current = new (window.AudioContext || (window as any).webkitAudioContext)();
    }
    if (audioContextRef.current.state === 'suspended') {
      audioContextRef.current.resume();
    }
  };

  // Agendar notas individuais
  const scheduleNote = (beatNumber: number, time: number) => {
    if (!audioContextRef.current || isMuted) return;

    const osc = audioContextRef.current.createOscillator();
    const gainNode = audioContextRef.current.createGain();
    
    osc.connect(gainNode);
    gainNode.connect(audioContextRef.current.destination);

    const isFirstBeat = beatNumber === 0;

    // Configurar frequências e decaimentos conforme o tipo de som
    if (soundType === 'classic') {
      osc.type = 'sine';
      osc.frequency.setValueAtTime(isFirstBeat ? 1000 : 750, time);
      gainNode.gain.setValueAtTime(1.0, time);
      gainNode.gain.exponentialRampToValueAtTime(0.001, time + 0.05); // decaimento em 50ms
      osc.start(time);
      osc.stop(time + 0.06);
    } 
    else if (soundType === 'woodblock') {
      // Woodblock usa onda senoidal com frequências mais altas e decaimento hiper-rápido percussivo
      osc.type = 'sine';
      osc.frequency.setValueAtTime(isFirstBeat ? 1300 : 1000, time);
      gainNode.gain.setValueAtTime(1.2, time);
      gainNode.gain.exponentialRampToValueAtTime(0.001, time + 0.03); // decaimento ultra curto em 30ms
      osc.start(time);
      osc.stop(time + 0.04);
    } 
    else if (soundType === 'digital') {
      osc.type = 'triangle';
      osc.frequency.setValueAtTime(isFirstBeat ? 880 : 440, time);
      gainNode.gain.setValueAtTime(0.8, time);
      gainNode.gain.exponentialRampToValueAtTime(0.001, time + 0.06); // decaimento em 60ms
      osc.start(time);
      osc.stop(time + 0.07);
    }

    // Atualizar UI de forma síncrona com o tempo do áudio (usando setTimeout relativo ao tempo atual)
    const delay = (time - audioContextRef.current.currentTime) * 1000;
    setTimeout(() => {
      if (isPlayingRef.current) {
        setCurrentBeat(beatNumber);
        setFlash(true);
        setTimeout(() => setFlash(false), 80);
      }
    }, Math.max(0, delay));
  };

  // Loop de agendamento antecipado (Scheduling Loop)
  const scheduler = () => {
    if (!audioContextRef.current) return;

    const scheduleAheadTime = 0.1; // quanta antecedência agendamos (100ms)
    
    while (nextNoteTimeRef.current < audioContextRef.current.currentTime + scheduleAheadTime) {
      scheduleNote(currentBeatRef.current, nextNoteTimeRef.current);
      
      // Avançar para a próxima batida
      const secondsPerBeat = 60.0 / bpmRef.current;
      nextNoteTimeRef.current += secondsPerBeat;
      
      // Incrementar batida do compasso
      currentBeatRef.current = (currentBeatRef.current + 1) % timeSignatureRef.current;
    }

    // Agendar próximo ciclo do scheduler
    timerIdRef.current = window.setTimeout(scheduler, 25);
  };

  const startMetronome = () => {
    initAudio();
    isPlayingRef.current = true;
    setIsPlaying(true);
    currentBeatRef.current = 0;
    setCurrentBeat(0);
    
    if (audioContextRef.current) {
      nextNoteTimeRef.current = audioContextRef.current.currentTime + 0.05;
      scheduler();
    }
  };

  const stopMetronome = () => {
    isPlayingRef.current = false;
    setIsPlaying(false);
    setCurrentBeat(0);
    if (timerIdRef.current) {
      clearTimeout(timerIdRef.current);
      timerIdRef.current = null;
    }
  };

  const togglePlayback = () => {
    if (isPlaying) {
      stopMetronome();
    } else {
      startMetronome();
    }
  };

  const handleBpmChange = (newBpm: number) => {
    const validatedBpm = Math.max(40, Math.min(250, newBpm));
    setBpm(validatedBpm);
  };

  // Algoritmo de Tap Tempo
  const handleTap = () => {
    const now = performance.now();
    const newTapTimes = [...tapTimes, now];

    // Manter apenas os últimos 4 cliques para calcular o ritmo recente
    if (newTapTimes.length > 4) {
      newTapTimes.shift();
    }

    setTapTimes(newTapTimes);

    if (newTapTimes.length > 1) {
      const intervals = [];
      for (let i = 1; i < newTapTimes.length; i++) {
        intervals.push(newTapTimes[i] - newTapTimes[i - 1]);
      }
      
      // Média dos intervalos em milissegundos
      const averageInterval = intervals.reduce((sum, val) => sum + val, 0) / intervals.length;
      
      // Calcular BPM (60.000 ms = 1 minuto)
      const calculatedBpm = Math.round(60000 / averageInterval);
      
      if (calculatedBpm >= 40 && calculatedBpm <= 250) {
        handleBpmChange(calculatedBpm);
      }
    }

    // Piscar o visual no botão Tap
    setFlash(true);
    setTimeout(() => setFlash(false), 80);
  };

  // Obter termos clássicos de andamento musical
  const getTempoLabel = (bpmVal: number) => {
    if (bpmVal < 60) return 'Grave / Largo (Super Lento)';
    if (bpmVal < 76) return 'Adagio (Lento e Solene)';
    if (bpmVal < 108) return 'Andante / Moderato (Andamento Médio)';
    if (bpmVal < 120) return 'Allegretto (Moderadamente Rápido)';
    if (bpmVal < 156) return 'Allegro (Rápido e Alegre)';
    if (bpmVal < 176) return 'Vivace (Muito Vivo)';
    return 'Presto (Extremamente Rápido)';
  };

  // Compassos suportados
  const signatures = [
    { value: 2, label: '2/4', name: 'Binário' },
    { value: 3, label: '3/4', name: 'Ternário' },
    { value: 4, label: '4/4', name: 'Quaternário' },
    { value: 6, label: '6/8', name: 'Composto' }
  ];

  return (
    <div className="space-y-6 font-hanken">
      {/* Hero Header Bento Box */}
      <div className="relative overflow-hidden rounded-[4px] bg-surface border border-border-custom p-6 md:p-8">
        <div className="absolute -right-16 -top-16 w-64 h-64 bg-primary/5 rounded-full blur-3xl pointer-events-none" />
        <div className="relative z-10">
          <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-[4px] bg-primary/10 border border-primary/20 text-primary text-[10px] font-bold uppercase tracking-wider mb-3">
            ⚡ Ferramentas do Músico
          </span>
          <h1 className="text-2xl md:text-3xl font-extrabold tracking-tight text-text-main">
            Metrônomo <span className="text-primary font-black">Visual & Auditivo</span>
          </h1>
          <p className="text-text-muted text-xs md:text-sm mt-1 max-w-xl leading-relaxed">
            Metrônomo digital sintetizado de altíssima precisão e sem latência. Ajuste o andamento da canção, mude a fórmula de compasso e treine o tempo perfeito com feedback visual dinâmico.
          </p>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Painel Central do Metrônomo (2/3 da tela em desk) */}
        <div className="lg:col-span-2 bg-surface border border-border-custom rounded-[4px] p-6 flex flex-col items-center justify-between min-h-[380px] relative select-none">
          
          {/* Luz Indicadora Pulsação */}
          <div className="absolute top-6 right-6 flex items-center gap-2">
            <Activity className={`w-4 h-4 transition-colors ${isPlaying ? 'text-primary' : 'text-text-muted'}`} />
            <div className={`w-2.5 h-2.5 rounded-full border transition-all duration-75 ${
              flash 
                ? currentBeat === 0 
                  ? 'bg-amber-500 border-amber-400 scale-125 shadow-[0_0_12px_rgba(245,158,11,0.5)]' 
                  : 'bg-primary border-primary-light scale-125 shadow-[0_0_12px_rgba(46,126,237,0.5)]'
                : 'bg-bg-dark border-border-custom'
            }`} />
          </div>

          {/* Nome e Termo de Andamento */}
          <div className="text-center w-full mt-4">
            <span className="text-[10px] font-black text-text-muted uppercase tracking-widest block">
              Andamento Musical
            </span>
            <span className="text-xs font-bold text-primary mt-1 inline-block uppercase tracking-wider bg-primary/5 border border-primary/10 px-2.5 py-0.5 rounded-[4px]">
              {getTempoLabel(bpm)}
            </span>
          </div>

          {/* Display Central do BPM */}
          <div className="my-8 text-center flex flex-col items-center select-none">
            <div className="flex items-center gap-6">
              <button 
                onClick={() => handleBpmChange(bpm - 5)}
                className="w-10 h-10 bg-bg-dark border border-border-custom hover:border-primary/20 text-text-muted hover:text-text-main rounded-[4px] flex items-center justify-center font-bold cursor-pointer transition-all active:scale-95"
                title="Diminuir 5 BPM"
              >
                -5
              </button>
              <button 
                onClick={() => handleBpmChange(bpm - 1)}
                className="w-10 h-10 bg-bg-dark border border-border-custom hover:border-primary/20 text-text-muted hover:text-text-main rounded-[4px] flex items-center justify-center font-bold cursor-pointer transition-all active:scale-95"
                title="Diminuir 1 BPM"
              >
                <Minus className="w-4 h-4" />
              </button>

              <div className="w-36 h-36 rounded-full border border-border-custom/80 bg-bg-dark flex flex-col items-center justify-center shadow-inner relative group select-none">
                <span className="text-5xl font-black font-display text-text-main tracking-tight select-none">
                  {bpm}
                </span>
                <span className="text-[9px] font-black text-text-muted uppercase tracking-widest select-none block mt-1">
                  BPM
                </span>
              </div>

              <button 
                onClick={() => handleBpmChange(bpm + 1)}
                className="w-10 h-10 bg-bg-dark border border-border-custom hover:border-primary/20 text-text-muted hover:text-text-main rounded-[4px] flex items-center justify-center font-bold cursor-pointer transition-all active:scale-95"
                title="Aumentar 1 BPM"
              >
                <Plus className="w-4 h-4" />
              </button>
              <button 
                onClick={() => handleBpmChange(bpm + 5)}
                className="w-10 h-10 bg-bg-dark border border-border-custom hover:border-primary/20 text-text-muted hover:text-text-main rounded-[4px] flex items-center justify-center font-bold cursor-pointer transition-all active:scale-95"
                title="Aumentar 5 BPM"
              >
                +5
              </button>
            </div>

            {/* Slider de Andamento */}
            <div className="w-64 max-w-xs mt-6 select-none">
              <label 
                htmlFor="metronome-bpm-slider-full" 
                style={{ position: 'absolute', width: '1px', height: '1px', padding: '0', margin: '-1px', overflow: 'hidden', clip: 'rect(0, 0, 0, 0)', border: '0' }}
              >
                Ajustar BPM do Metrônomo
              </label>
              <input 
                id="metronome-bpm-slider-full"
                type="range" 
                min="40" 
                max="250" 
                value={bpm}
                onChange={(e) => handleBpmChange(parseInt(e.target.value))}
                className="w-full h-1.5 bg-bg-dark border border-border-custom rounded-lg appearance-none cursor-pointer accent-primary focus:outline-none"
              />
              <div className="flex justify-between text-[9px] font-bold text-text-muted uppercase tracking-wider mt-1 px-0.5">
                <span>40 Adagio</span>
                <span>120 Moderato</span>
                <span>250 Presto</span>
              </div>
            </div>
          </div>

          {/* Indicador Visual do Compasso (Bolinhas) */}
          <div className="w-full max-w-sm px-4 mb-4 select-none">
            <div className="flex items-center justify-center gap-3">
              {Array.from({ length: timeSignature }).map((_, i) => {
                const isActive = isPlaying && currentBeat === i;
                const isFirst = i === 0;
                return (
                  <div 
                    key={i}
                    className={`flex-1 max-w-[40px] h-2 rounded-[2px] border transition-all duration-100 ${
                      isActive 
                        ? isFirst
                          ? 'bg-amber-500 border-amber-400 scale-y-125 shadow-[0_0_10px_rgba(245,158,11,0.3)]' 
                          : 'bg-primary border-primary-light scale-y-125 shadow-[0_0_10px_rgba(46,126,237,0.3)]'
                        : 'bg-bg-dark border-border-custom'
                    }`}
                  />
                );
              })}
            </div>
            <div className="flex justify-between items-center text-[9px] font-black text-text-muted uppercase tracking-widest mt-1.5 px-0.5">
              <span>Forte</span>
              <span>Fraco</span>
            </div>
          </div>

          {/* Barra de Controles Principais */}
          <div className="w-full flex items-center justify-center gap-4 pt-4 border-t border-border-custom/50 mt-2 select-none">
            {/* Silenciar */}
            <button
              onClick={() => setIsMuted(!isMuted)}
              className={`w-10 h-10 rounded-[4px] border flex items-center justify-center cursor-pointer transition-all active:scale-95 ${
                isMuted 
                  ? 'bg-red-500/10 border-red-500/20 text-red-500' 
                  : 'bg-bg-dark border-border-custom text-text-muted hover:text-text-main'
              }`}
              title={isMuted ? 'Ativar Som' : 'Silenciar'}
            >
              {isMuted ? <VolumeX className="w-4 h-4" /> : <Volume2 className="w-4 h-4" />}
            </button>

            {/* Play / Stop */}
            <button
              onClick={togglePlayback}
              className={`h-11 px-8 rounded-[4px] text-xs font-black flex items-center gap-2 cursor-pointer transition-all active:scale-[0.97] shadow-sm uppercase tracking-wider ${
                isPlaying 
                  ? 'bg-red-500 text-white hover:bg-red-600' 
                  : 'bg-primary text-bg-dark hover:bg-primary-light'
              }`}
            >
              {isPlaying ? (
                <>
                  <Square className="w-4 h-4 text-white fill-white" />
                  <span>Parar</span>
                </>
              ) : (
                <>
                  <Play className="w-4 h-4 text-bg-dark fill-bg-dark" />
                  <span>Iniciar</span>
                </>
              )}
            </button>

            {/* Tap Tempo */}
            <button
              onClick={handleTap}
              className="h-10 px-4 bg-bg-dark border border-border-custom hover:border-primary/20 text-text-main rounded-[4px] text-xs font-bold uppercase tracking-wider cursor-pointer transition-all active:scale-95 flex items-center gap-1.5"
              title="Clique no ritmo para definir o andamento"
            >
              <Clock className="w-4 h-4 text-primary" />
              <span>Tap Tempo</span>
            </button>
          </div>
        </div>

        {/* Painel de Ajustes / Presets (1/3 da tela em desk) */}
        <div className="bg-surface border border-border-custom rounded-[4px] p-6 space-y-6 select-none">
          {/* Ajuste de Compasso */}
          <div className="space-y-3">
            <span className="block text-[10px] font-black text-text-muted uppercase tracking-widest pl-0.5">
              Fórmula de Compasso
            </span>
            <div className="grid grid-cols-2 gap-2">
              {signatures.map((sig) => (
                <button
                  key={sig.value}
                  onClick={() => setTimeSignature(sig.value)}
                  className={`px-3 py-2.5 rounded-[4px] border text-left cursor-pointer transition-all ${
                    timeSignature === sig.value
                      ? 'bg-primary/10 border-primary/30 text-primary'
                      : 'bg-bg-dark border-border-custom text-text-muted hover:text-text-main hover:border-border-custom/80'
                  }`}
                >
                  <div className="text-xs font-extrabold">{sig.label}</div>
                  <div className="text-[9px] font-bold text-text-muted/80 uppercase tracking-wide mt-0.5">{sig.name}</div>
                </button>
              ))}
            </div>
          </div>

          {/* Tipo de Som do Metrônomo */}
          <div className="space-y-3">
            <span className="block text-[10px] font-black text-text-muted uppercase tracking-widest pl-0.5">
              Tipo de Som
            </span>
            <div className="grid grid-cols-1 gap-2">
              <button
                onClick={() => setSoundType('woodblock')}
                className={`px-3.5 py-2.5 rounded-[4px] border text-left cursor-pointer transition-all flex items-center justify-between ${
                  soundType === 'woodblock'
                    ? 'bg-primary/10 border-primary/30 text-primary'
                    : 'bg-bg-dark border-border-custom text-text-muted hover:text-text-main'
                }`}
              >
                <div>
                  <div className="text-xs font-extrabold">🌲 Bloco de Madeira</div>
                  <div className="text-[9px] font-bold text-text-muted/80 uppercase tracking-wide mt-0.5">Som orgânico e percussivo</div>
                </div>
                {soundType === 'woodblock' && <div className="w-1.5 h-1.5 rounded-full bg-primary" />}
              </button>

              <button
                onClick={() => setSoundType('classic')}
                className={`px-3.5 py-2.5 rounded-[4px] border text-left cursor-pointer transition-all flex items-center justify-between ${
                  soundType === 'classic'
                    ? 'bg-primary/10 border-primary/30 text-primary'
                    : 'bg-bg-dark border-border-custom text-text-muted hover:text-text-main'
                }`}
              >
                <div>
                  <div className="text-xs font-extrabold">🔔 Beep Clássico</div>
                  <div className="text-[9px] font-bold text-text-muted/80 uppercase tracking-wide mt-0.5">Sintetizado de onda pura</div>
                </div>
                {soundType === 'classic' && <div className="w-1.5 h-1.5 rounded-full bg-primary" />}
              </button>

              <button
                onClick={() => setSoundType('digital')}
                className={`px-3.5 py-2.5 rounded-[4px] border text-left cursor-pointer transition-all flex items-center justify-between ${
                  soundType === 'digital'
                    ? 'bg-primary/10 border-primary/30 text-primary'
                    : 'bg-bg-dark border-border-custom text-text-muted hover:text-text-main'
                }`}
              >
                <div>
                  <div className="text-xs font-extrabold">⚡ Beep Digital</div>
                  <div className="text-[9px] font-bold text-text-muted/80 uppercase tracking-wide mt-0.5">Onda triangular vintage</div>
                </div>
                {soundType === 'digital' && <div className="w-1.5 h-1.5 rounded-full bg-primary" />}
              </button>
            </div>
          </div>

          {/* Presets Rápidos de Louvor */}
          <div className="space-y-3">
            <span className="block text-[10px] font-black text-text-muted uppercase tracking-widest pl-0.5">
              Presets de Andamento
            </span>
            <div className="grid grid-cols-2 gap-2 text-center">
              {[
                { label: 'Adoração Lenta', bpmVal: 68 },
                { label: 'Adoração Média', bpmVal: 76 },
                { label: 'Louvor Suave', bpmVal: 90 },
                { label: 'Louvor Médio', bpmVal: 112 },
                { label: 'Celebrativo', bpmVal: 125 },
                { label: 'Rápido / Júbilo', bpmVal: 140 }
              ].map((preset, index) => (
                <button
                  key={index}
                  onClick={() => handleBpmChange(preset.bpmVal)}
                  className="px-2 py-2 bg-bg-dark border border-border-custom hover:border-primary/20 hover:bg-surface/50 text-text-muted hover:text-text-main rounded-[4px] text-[10px] font-bold cursor-pointer transition-colors"
                >
                  <div className="font-extrabold">{preset.label}</div>
                  <div className="text-primary font-black mt-0.5">{preset.bpmVal} BPM</div>
                </button>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
