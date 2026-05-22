import React from 'react';
import { Calendar, UserCheck, Clock, AlertCircle, ChevronRight } from 'lucide-react';

interface ScheduleData {
  id?: number;
  event_date: string;
  event_time?: string;
  event_type: string;
  my_status: string; // 'confirmed', 'pending', 'declined'
  my_role?: string;
}

interface ScheduleWidgetProps {
  schedule: ScheduleData | null;
  totalSchedules: number;
}

export const ScheduleWidget: React.FC<ScheduleWidgetProps> = ({ schedule, totalSchedules }) => {
  
  // Formatador de data elegante
  const formatDate = (dateStr: string) => {
    try {
      // Ajustar para evitar problemas de fuso horário local
      const [year, month, day] = dateStr.split('-').map(Number);
      const date = new Date(year, month - 1, day);
      
      const weekday = date.toLocaleDateString('pt-BR', { weekday: 'long' });
      const dayNum = date.getDate();
      const monthName = date.toLocaleDateString('pt-BR', { month: 'short' }).replace('.', '');
      
      return {
        weekday: weekday.charAt(0).toUpperCase() + weekday.slice(1),
        dayNum,
        monthName: monthName.charAt(0).toUpperCase() + monthName.slice(1),
      };
    } catch (e) {
      return { weekday: 'Próxima Escala', dayNum: '?', monthName: 'Mês' };
    }
  };

  if (!schedule) {
    return (
      <div className="bg-surface border border-border-custom rounded-[4px] p-6 flex flex-col justify-between min-h-[220px] transition-all hover:border-primary/40 group relative overflow-hidden">
        {/* Fundo decorativo minimalista */}
        <div className="absolute top-0 right-0 w-32 h-32 bg-primary/2 rounded-full blur-2xl -mr-8 -mt-8 transition-all group-hover:bg-primary/5" />
        
        <div className="flex items-start justify-between relative z-10">
          <div className="bg-surface border border-border-custom p-2 rounded-[4px] text-text-muted">
            <Calendar className="w-5 h-5" />
          </div>
          <span className="text-[10px] font-bold bg-border-custom px-2 py-0.5 rounded-[4px] text-text-muted">
            Sem Escala Ativa
          </span>
        </div>

        <div className="my-5 relative z-10">
          <h3 className="text-sm font-bold text-text-main font-hanken">Você está livre de escala</h3>
          <p className="text-xs text-text-muted mt-1 leading-relaxed">
            Não há escalas de culto associadas a você nos próximos dias. Aproveite para descansar ou apoiar a equipe!
          </p>
        </div>

        <div className="border-t border-border-custom pt-4 flex items-center justify-between text-[10px] font-bold text-text-muted uppercase tracking-wider relative z-10">
          <span>PIB Oliveira</span>
          <a href="../admin/escalas.php" className="flex items-center gap-1 text-primary hover:underline">
            <span>Ver Escalas Gerais</span>
            <ChevronRight className="w-3.5 h-3.5" />
          </a>
        </div>
      </div>
    );
  }

  const dateInfo = formatDate(schedule.event_date);
  const eventTime = schedule.event_time ? schedule.event_time.substring(0, 5) : '19:00';

  // Status mapping
  const statusConfig = {
    confirmed: { label: 'Confirmado', color: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' },
    pending: { label: 'Pendente', color: 'bg-amber-500/10 text-amber-500 border-amber-500/20' },
    declined: { label: 'Recusado', color: 'bg-rose-500/10 text-rose-500 border-rose-500/20' },
  };

  const currentStatus = (schedule.my_status as keyof typeof statusConfig) in statusConfig 
    ? statusConfig[schedule.my_status as keyof typeof statusConfig] 
    : statusConfig.pending;

  return (
    <div className="bg-surface border border-border-custom rounded-[4px] p-6 flex flex-col justify-between min-h-[220px] transition-all hover:border-primary/40 group relative overflow-hidden shadow-xs">
      
      {/* Fundo decorativo */}
      <div className="absolute top-0 right-0 w-36 h-36 bg-primary/3 rounded-full blur-3xl -mr-10 -mt-10 transition-all group-hover:bg-primary/6" />

      {/* Header do painel */}
      <div className="flex items-start justify-between relative z-10">
        <div className="flex gap-4">
          {/* Data Badge de Alto Impacto */}
          <div className="bg-bg-dark border border-border-custom flex flex-col items-center justify-center w-12 h-14 rounded-[4px] shadow-sm select-none">
            <span className="text-[10px] font-black text-text-muted uppercase tracking-wider leading-none mb-1">
              {dateInfo.monthName}
            </span>
            <span className="text-xl font-black text-primary font-hanken leading-none">
              {dateInfo.dayNum}
            </span>
          </div>
          <div>
            <span className="text-[9px] font-black text-primary uppercase tracking-widest leading-none block mb-1">
              {dateInfo.weekday}
            </span>
            <h3 className="text-sm font-bold text-text-main font-hanken truncate max-w-[170px] leading-tight">
              {schedule.event_type}
            </h3>
            <div className="flex items-center gap-1 text-[10px] font-semibold text-text-muted mt-1">
              <Clock className="w-3.5 h-3.5 opacity-75" />
              <span>{eventTime}h</span>
            </div>
          </div>
        </div>

        {/* Status Badge */}
        <span className={`text-[9px] font-black border px-2 py-0.5 rounded-[4px] uppercase tracking-wider ${currentStatus.color}`}>
          {currentStatus.label}
        </span>
      </div>

      {/* Meio: Detalhes da Escala do Líder */}
      <div className="my-5 flex gap-6 border-t border-b border-border-custom/50 py-4 relative z-10">
        <div className="flex-1">
          <span className="text-[9px] font-black text-text-muted uppercase tracking-widest block mb-1">
            Sua Função
          </span>
          <p className="text-xs font-extrabold text-text-main flex items-center gap-1.5">
            <UserCheck className="w-4 h-4 text-primary" />
            {schedule.my_role || 'Líder / Ministro'}
          </p>
        </div>
        <div className="w-px bg-border-custom" />
        <div className="flex-1">
          <span className="text-[9px] font-black text-text-muted uppercase tracking-widest block mb-1">
            Total de Escalas
          </span>
          <p className="text-xs font-extrabold text-text-main">
            {totalSchedules} {totalSchedules === 1 ? 'escala ativa' : 'escalas ativas'}
          </p>
        </div>
      </div>

      {/* Footer: Links */}
      <div className="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider relative z-10 mt-auto">
        <span className="text-text-muted font-semibold flex items-center gap-1">
          <AlertCircle className="w-3.5 h-3.5 text-accent animate-pulse" />
          Escala Oficial
        </span>
        <a 
          href={`../admin/escala_detalhe.php?id=${schedule.id || ''}`} 
          className="flex items-center gap-0.5 text-primary hover:underline group-hover:translate-x-0.5 transition-transform"
        >
          <span>Ver Detalhes / Confirmar</span>
          <ChevronRight className="w-3.5 h-3.5" />
        </a>
      </div>

    </div>
  );
};
