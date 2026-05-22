import React, { useState, useEffect, useRef } from 'react';
import { Play, Square, Plus, Minus, Disc, Clock } from 'lucide-react';

export const MetronomeWidget: React.FC = () => {
  const [bpm, setBpm] = useState<number>(120);
  const [isPlaying, setIsPlaying] = useState<boolean>(false);
  const [pulse, setPulse] = useState<boolean>(false);
  
  const timerRef = useRef<number | null>(null);
  const audioCtxRef = useRef<AudioContext | null>(null);
  const nextClickTimeRef = useRef<number>(0);
  const tapTimesRef = useRef<number[]>([]);

  // Limpeza na desmontagem do componente
  useEffect(() => {
    return () => {
      if (timerRef.current) window.clearInterval(timerRef.current);
      if (audioCtxRef.current) audioCtxRef.current.close();
    };
  }, []);

  // Tocar o clique audível usando a Web Audio API nativa
  const playClickSound = (time: number, isFirstBeat: boolean = false) => {
    try {
      if (!audioCtxRef.current) {
        audioCtxRef.current = new (window.AudioContext || (window as any).webkitAudioContext)();
      }

      const ctx = audioCtxRef.current;
      
      // Retomar se estiver suspenso por políticas de interação do navegador
      if (ctx.state === 'suspended') {
        ctx.resume();
      }

      const osc = ctx.createOscillator();
      const envelope = ctx.createGain();

      osc.connect(envelope);
      envelope.connect(ctx.destination);

      // Frequência diferente para o primeiro tempo de compassos, mas como é metrônomo rápido de púlpito
      // vamos usar uma frequência de clique limpo e agradável de 1000Hz (e 1400Hz para acentuar se desejado)
      osc.frequency.setValueAtTime(isFirstBeat ? 1320 : 880, time);
      
      // Envelope suave para evitar estalos (cliques) no início/fim
      envelope.gain.setValueAtTime(1, time);
      envelope.gain.exponentialRampToValueAtTime(0.001, time + 0.04);

      osc.start(time);
      osc.stop(time + 0.05);

      // Feedback visual reativo
      setTimeout(() => {
        setPulse(true);
        setTimeout(() => setPulse(false), 80);
      }, (time - ctx.currentTime) * 1000);

    } catch (err) {
      console.error("Erro ao reproduzir clique do metrônomo:", err);
    }
  };

  // Scheduler do metrônomo
  const togglePlay = () => {
    if (isPlaying) {
      if (timerRef.current) {
        window.clearInterval(timerRef.current);
        timerRef.current = null;
      }
      setIsPlaying(false);
    } else {
      if (!audioCtxRef.current) {
        audioCtxRef.current = new (window.AudioContext || (window as any).webkitAudioContext)();
      }

      const ctx = audioCtxRef.current;
      if (ctx.state === 'suspended') {
        ctx.resume();
      }

      setIsPlaying(true);
      
      // Agendar o primeiro clique imediatamente
      nextClickTimeRef.current = ctx.currentTime + 0.05;
      
      // Timer simples e estável para disparar os cliques
      let beatCounter = 0;
      timerRef.current = window.setInterval(() => {
        const time = ctx.currentTime;
        // Se o tempo atual estiver alcançando o agendamento
        if (nextClickTimeRef.current < time + 0.1) {
          const isFirstBeat = beatCounter % 4 === 0;
          playClickSound(nextClickTimeRef.current, isFirstBeat);
          nextClickTimeRef.current += 60 / bpm;
          beatCounter++;
        }
      }, 25);
    }
  };

  // Reiniciar metrônomo se o BPM mudar durante a execução
  useEffect(() => {
    if (isPlaying) {
      // Para e inicia de novo com o novo ritmo
      togglePlay();
      togglePlay();
    }
  }, [bpm]);

  // Função TAP BPM
  const handleTap = () => {
    const now = performance.now();
    const tapTimes = tapTimesRef.current;

    // Limpar histórico se a última batida foi há mais de 2.5 segundos
    if (tapTimes.length > 0 && now - tapTimes[tapTimes.length - 1] > 2500) {
      tapTimesRef.current = [];
    }

    tapTimesRef.current.push(now);

    if (tapTimesRef.current.length > 1) {
      // Calcular intervalos entre batidas
      const intervals: number[] = [];
      for (let i = 1; i < tapTimesRef.current.length; i++) {
        intervals.push(tapTimesRef.current[i] - tapTimesRef.current[i - 1]);
      }
      
      // Média dos intervalos
      const averageInterval = intervals.reduce((sum, val) => sum + val, 0) / intervals.length;
      
      // Converter para BPM
      const calculatedBpm = Math.round(60000 / averageInterval);
      
      // Limitar BPM razoável
      if (calculatedBpm >= 40 && calculatedBpm <= 240) {
        setBpm(calculatedBpm);
      }
    }

    // Feedback visual do toque do botão TAP
    setPulse(true);
    setTimeout(() => setPulse(false), 80);
  };

  const handleSliderChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setBpm(parseInt(e.target.value, 10));
  };

  const adjustBpm = (amount: number) => {
    setBpm((prev) => Math.min(240, Math.max(40, prev + amount)));
  };

  return (
    <div className="bg-surface border border-border-custom rounded-[4px] p-6 min-h-[220px] flex flex-col justify-between transition-all hover:border-primary/40 group relative overflow-hidden shadow-xs">
      
      {/* Header do Widget */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <div className="bg-primary/10 text-primary p-1.5 rounded-[4px] border border-primary/20">
            <Clock className="w-4 h-4" />
          </div>
          <h3 className="text-xs font-black text-text-main font-hanken uppercase tracking-wider">
            Metrônomo de Bolso
          </h3>
        </div>
        
        {/* Indicador Visual Pulsante */}
        <div className="flex items-center gap-1.5 select-none">
          <span className="text-[10px] font-bold text-text-muted">Ritmo</span>
          <div 
            className={`w-3.5 h-3.5 rounded-full border border-border-custom transition-all duration-75 ${
              pulse 
                ? 'bg-accent border-accent shadow-[0_0_8px_rgba(255,193,7,0.5)] scale-110' 
                : isPlaying 
                  ? 'bg-primary/20 border-primary/30' 
                  : 'bg-bg-dark'
            }`} 
          />
        </div>
      </div>

      {/* Exibição Central do BPM */}
      <div className="my-4 flex items-center justify-center gap-6 select-none">
        
        {/* Botão de Menos */}
        <button
          onClick={() => adjustBpm(-1)}
          className="p-2 border border-border-custom hover:bg-bg-dark text-text-muted hover:text-text-main rounded-[4px] cursor-pointer transition-colors active:scale-95"
          title="Diminuir 1 BPM"
        >
          <Minus className="w-4 h-4" />
        </button>

        {/* BPM Número Gigante */}
        <div className="text-center">
          <div className="text-4xl font-black text-text-main font-hanken leading-none tracking-tight">
            {bpm}
          </div>
          <span className="text-[9px] font-black text-text-muted uppercase tracking-widest block mt-1">
            Batidas por Minuto
          </span>
        </div>

        {/* Botão de Mais */}
        <button
          onClick={() => adjustBpm(1)}
          className="p-2 border border-border-custom hover:bg-bg-dark text-text-muted hover:text-text-main rounded-[4px] cursor-pointer transition-colors active:scale-95"
          title="Aumentar 1 BPM"
        >
          <Plus className="w-4 h-4" />
        </button>

      </div>

      {/* Controle Deslizante */}
      <div className="mb-4">
        <input
          type="range"
          min="40"
          max="240"
          value={bpm}
          onChange={handleSliderChange}
          className="w-full h-1 bg-bg-dark rounded-lg appearance-none cursor-pointer accent-primary"
        />
        <div className="flex justify-between text-[9px] font-bold text-text-muted mt-1 select-none">
          <span>40 BPM (Lento)</span>
          <span>240 BPM (Rápido)</span>
        </div>
      </div>

      {/* Botões de Ação Inferiores */}
      <div className="flex gap-3">
        
        {/* Botão Play / Stop */}
        <button
          onClick={togglePlay}
          className={`flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-[4px] text-xs font-black uppercase tracking-wider transition-all cursor-pointer select-none active:scale-[0.98] border ${
            isPlaying
              ? 'bg-rose-500/10 hover:bg-rose-500/20 border-rose-500/20 text-rose-500'
              : 'bg-primary hover:bg-primary-hover border-primary/20 text-white shadow-xs'
          }`}
        >
          {isPlaying ? (
            <>
              <Square className="w-3.5 h-3.5 fill-current" />
              <span>Parar</span>
            </>
          ) : (
            <>
              <Play className="w-3.5 h-3.5 fill-current" />
              <span>Iniciar</span>
            </>
          )}
        </button>

        {/* Botão TAP BPM */}
        <button
          onClick={handleTap}
          className="px-4 bg-surface hover:bg-bg-dark border border-border-custom hover:border-text-muted/30 text-text-main text-xs font-black uppercase tracking-wider rounded-[4px] transition-colors cursor-pointer select-none active:scale-[0.98] flex items-center gap-1.5"
          title="Toque no ritmo da música para calcular o BPM"
        >
          <Disc className={`w-3.5 h-3.5 ${isPlaying ? 'animate-spin-slow text-primary' : 'text-text-muted'}`} />
          <span>Tap</span>
        </button>

      </div>

    </div>
  );
};
