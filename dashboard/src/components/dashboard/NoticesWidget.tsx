import React from 'react';
import { Bell, AlertTriangle, Quote } from 'lucide-react';

interface NoticeItem {
  id: number;
  title: string;
  content: string;
  is_urgent: boolean;
  date: string;
}

interface NoticesWidgetProps {
  unreadCount: number;
  lastNoticeTitle: string;
}

export const NoticesWidget: React.FC<NoticesWidgetProps> = ({ unreadCount, lastNoticeTitle }) => {
  
  // Avisos estáticos importantes do ministério como fonte de informação rica
  const notices: NoticeItem[] = [
    {
      id: 1,
      title: 'Ensaio Geral com Todos os Grupos',
      content: 'Atenção, líder e instrumentistas: nosso ensaio geral nesta quinta-feira iniciará impreterivelmente às 19h30 no templo. Tragam as partituras estudadas!',
      is_urgent: true,
      date: 'Hoje'
    },
    {
      id: 2,
      title: 'Consagração e Oração do Ministério',
      content: 'Neste sábado teremos nosso momento mensal de consagração e clamor pelo ministério de louvor e mídias. Às 08:00 na sala dos jovens.',
      is_urgent: false,
      date: 'Amanhã'
    }
  ];

  // Se a API retornar um aviso real dinâmico que não seja o placeholder padrão, adicionamos no topo
  if (lastNoticeTitle && lastNoticeTitle !== 'Nenhum aviso novo') {
    // Evitar duplicar se for o ensaio
    if (!notices.some(n => n.title === lastNoticeTitle)) {
      notices.unshift({
        id: 99,
        title: lastNoticeTitle,
        content: 'Novo aviso publicado no mural administrativo do portal de louvor.',
        is_urgent: unreadCount > 0,
        date: 'Recente'
      });
    }
  }

  return (
    <div className="bg-surface border border-border-custom rounded-[4px] p-6 min-h-[220px] flex flex-col justify-between transition-all hover:border-primary/40 group relative overflow-hidden shadow-xs">
      
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center gap-2">
          <div className="bg-primary/10 text-primary p-1.5 rounded-[4px] border border-primary/20">
            <Bell className="w-4 h-4" />
          </div>
          <h3 className="text-xs font-black text-text-main font-hanken uppercase tracking-wider">
            Mural de Avisos
          </h3>
        </div>

        {unreadCount > 0 && (
          <span className="text-[9px] font-black bg-red-500/10 text-red-500 border border-red-500/20 px-2 py-0.5 rounded-[4px] uppercase tracking-wider animate-pulse">
            {unreadCount} {unreadCount === 1 ? 'Novo' : 'Novos'}
          </span>
        )}
      </div>

      {/* Conteúdo: Lista de Avisos com scroll sutil */}
      <div className="space-y-3 max-h-[145px] overflow-y-auto pr-1 select-none flex-1 mb-4">
        {notices.map((notice) => (
          <div 
            key={notice.id}
            className={`p-3 rounded-[4px] border transition-all text-xs relative overflow-hidden ${
              notice.is_urgent
                ? 'bg-red-500/5 border-red-500/25 shadow-[inset_3px_0_0_#ef4444]'
                : 'bg-bg-dark/40 border-border-custom hover:border-text-muted/30'
            }`}
          >
            {/* Indicador de urgente */}
            <div className="flex items-start justify-between gap-2">
              <div className="flex items-center gap-1.5">
                {notice.is_urgent && (
                  <AlertTriangle className="w-3.5 h-3.5 text-red-500 animate-bounce" />
                )}
                <h4 className={`font-bold text-text-main leading-tight ${notice.is_urgent ? 'text-red-500' : ''}`}>
                  {notice.title}
                </h4>
              </div>
              <span className="text-[9px] font-bold text-text-muted whitespace-nowrap bg-bg-dark border border-border-custom px-1.5 py-0.2 rounded-[4px]">
                {notice.date}
              </span>
            </div>
            <p className="text-text-muted mt-1 leading-relaxed text-[11px]">
              {notice.content}
            </p>
          </div>
        ))}
      </div>

      {/* Versículo da Semana / Caixa Decorativa Litúrgica */}
      <div className="border-t border-border-custom pt-4 select-none">
        <div className="bg-bg-dark/60 border border-border-custom rounded-[4px] p-3 relative overflow-hidden flex gap-3">
          <div className="text-primary opacity-30 mt-0.5">
            <Quote className="w-4 h-4 transform rotate-180" />
          </div>
          <div>
            <p className="text-[11px] italic font-semibold text-text-muted leading-relaxed">
              "Cantai-lhe um cântico novo; tocai bem e com júbilo."
            </p>
            <span className="text-[9px] font-black text-accent uppercase tracking-widest block mt-1.5">
              — Salmos 33:3
            </span>
          </div>
        </div>
      </div>

    </div>
  );
};
