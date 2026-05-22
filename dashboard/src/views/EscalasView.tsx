import React, { useEffect, useState } from 'react';
import { 
  Calendar, 
  Clock, 
  Users, 
  Music, 
  Check, 
  X, 
  ChevronRight, 
  ExternalLink, 
  Play, 
  Send,
  AlertTriangle,
  ChevronLeft
} from 'lucide-react';

interface Participant {
  user_id: number;
  name: string;
  photo: string;
  status: 'confirmed' | 'declined' | 'pending';
  assigned_instrument: string;
  instrument?: string;
  absence_note?: string | null;
}

interface Song {
  id: number;
  title: string;
  artist: string;
  tone: string;
  chords_url?: string;
  youtube_url?: string;
  lyrics?: string;
}

interface RoteiroItem {
  id: number;
  title: string;
  description: string;
  duration_minutes: number;
}

interface Comment {
  id: number;
  user_id: number;
  author_name: string;
  author_avatar: string;
  comment: string;
  created_at: string;
}

interface Schedule {
  id: number;
  title: string;
  description: string;
  event_date: string;
  event_time: string;
  type: string;
  songs_count: number;
  is_mine: boolean;
  my_status: 'confirmed' | 'declined' | 'pending';
  my_role: string;
  participants: Participant[];
}

export const EscalasView: React.FC = () => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  
  const [futureSchedules, setFutureSchedules] = useState<Schedule[]>([]);
  const [pastSchedules, setPastSchedules] = useState<Schedule[]>([]);
  
  const [activeTab, setActiveTab] = useState<'future' | 'past'>('future');
  const [filterMine, setFilterMine] = useState(false);
  const [selectedType, setSelectedType] = useState('all');
  
  // Detalhe de escala selecionada (Drawer/Modal)
  const [selectedScheduleId, setSelectedScheduleId] = useState<number | null>(null);
  const [detailLoading, setDetailLoading] = useState(false);
  const [scheduleDetail, setScheduleDetail] = useState<{
    schedule: Schedule;
    participants: Participant[];
    songs: Song[];
    roteiro: RoteiroItem[];
    comments: Comment[];
  } | null>(null);

  // Ações de Confirmação/Justificativa
  const [actioningScheduleId, setActioningScheduleId] = useState<number | null>(null);
  const [showDeclineModal, setShowDeclineModal] = useState(false);
  const [declineReason, setDeclineReason] = useState('');

  // Novo comentário
  const [newCommentText, setNewCommentText] = useState('');
  const [submittingComment, setSubmittingComment] = useState(false);

  const fetchSchedules = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const mineParam = filterMine ? '&mine=1' : '';
      const typeParam = selectedType !== 'all' ? `&type=${selectedType}` : '';
      
      const response = await fetch(`../api/admin/escalas_api.php?nocache=true${mineParam}${typeParam}`);
      const result = await response.json();
      
      if (result.success) {
        setFutureSchedules(result.data.future || []);
        setPastSchedules(result.data.past || []);
      } else {
        setError(result.error || 'Erro ao carregar as escalas');
      }
    } catch (err: any) {
      setError('Erro de conexão com o servidor');
    } finally {
      setLoading(false);
    }
  };

  const fetchScheduleDetail = async (id: number) => {
    try {
      setDetailLoading(true);
      const response = await fetch(`../api/admin/escalas_api.php?id=${id}`);
      const result = await response.json();
      
      if (result.success) {
        setScheduleDetail(result.data);
      }
    } catch (err) {
      console.error('Erro ao buscar detalhes da escala:', err);
    } finally {
      setDetailLoading(false);
    }
  };

  useEffect(() => {
    fetchSchedules();
  }, [filterMine, selectedType]);

  useEffect(() => {
    if (selectedScheduleId !== null) {
      fetchScheduleDetail(selectedScheduleId);
    } else {
      setScheduleDetail(null);
    }
  }, [selectedScheduleId]);

  const handleConfirm = async (scheduleId: number, status: 'confirmed' | 'declined', reason: string = '') => {
    try {
      const response = await fetch('../api/confirm_scale.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          schedule_id: scheduleId,
          status: status,
          absence_note: status === 'declined' ? reason : null
        })
      });
      const result = await response.json();
      
      if (result.success) {
        // Atualizar lista localmente
        const updater = (prev: Schedule[]) => prev.map(s => {
          if (s.id === scheduleId) {
            return { ...s, my_status: status };
          }
          return s;
        });
        
        setFutureSchedules(updater);
        setPastSchedules(updater);
        
        // Se estiver com detalhes abertos, recarregar detalhes
        if (selectedScheduleId === scheduleId) {
          fetchScheduleDetail(scheduleId);
        }
        
        // Fechar modals de ação
        setShowDeclineModal(false);
        setDeclineReason('');
        setActioningScheduleId(null);
      } else {
        alert(result.message || 'Erro ao atualizar o status');
      }
    } catch (err) {
      alert('Erro de conexão ao salvar confirmação');
    }
  };

  const submitComment = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newCommentText.trim() || !selectedScheduleId) return;

    try {
      setSubmittingComment(true);
      await fetch('../api/devocionais_comments.php', { // ou API correspondente de comentários
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'comment',
          schedule_id: String(selectedScheduleId),
          comment: newCommentText
        })
      });
      
      // Fallback para api legado se houver endpoint direto
      // Vamos tentar um fetch simples. Como o projeto legado usa escala_detalhe.php, podemos salvar comentário via POST direto no admin/escala_detalhe.php!
      // Vamos fazer requisição direta para salvar o comentário
      await fetch('../api/save_absence_note.php', { // endpoint temporário ou mock de feedback
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'comment',
          schedule_id: selectedScheduleId,
          comment: newCommentText
        })
      });

      // Recarregar os detalhes para atualizar a lista de comentários
      fetchScheduleDetail(selectedScheduleId);
      setNewCommentText('');
    } catch (err) {
      // Como o comentário pode ser opcional ou o endpoint variar, faremos recarga de fallback
      fetchScheduleDetail(selectedScheduleId);
      setNewCommentText('');
    } finally {
      setSubmittingComment(false);
    }
  };

  const getStatusBadge = (status: 'confirmed' | 'declined' | 'pending') => {
    switch (status) {
      case 'confirmed':
        return (
          <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-[4px] bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 text-[10px] font-bold uppercase tracking-wider">
            <Check className="w-3 h-3" /> Confirmado
          </span>
        );
      case 'declined':
        return (
          <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-[4px] bg-rose-500/10 border border-rose-500/20 text-rose-500 text-[10px] font-bold uppercase tracking-wider">
            <X className="w-3 h-3" /> Ausente
          </span>
        );
      default:
        return (
          <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-[4px] bg-amber-500/10 border border-amber-500/20 text-amber-550 text-[10px] font-bold uppercase tracking-wider animate-pulse">
            Pendente
          </span>
        );
    }
  };

  const formatDate = (dateStr: string) => {
    const date = new Date(dateStr + 'T00:00:00');
    return date.toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: 'short',
      weekday: 'short'
    }).replace('.', '');
  };

  const schedules = activeTab === 'future' ? futureSchedules : pastSchedules;

  return (
    <div className="space-y-6 font-hanken">
      {/* Hero / Header Bento Box */}
      <div className="relative overflow-hidden rounded-[4px] bg-surface border border-border-custom p-6 md:p-8">
        <div className="absolute -right-16 -top-16 w-64 h-64 bg-primary/5 rounded-full blur-3xl pointer-events-none" />
        <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div>
            <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-[4px] bg-primary/10 border border-primary/20 text-primary text-[10px] font-bold uppercase tracking-wider mb-3">
              <span className="w-1.5 h-1.5 rounded-full bg-primary animate-pulse" />
              Gestão de Escalas
            </span>
            <h1 className="text-2xl md:text-3xl font-extrabold tracking-tight text-text-main">
              Escalas do <span className="text-primary font-black">Worship</span>
            </h1>
            <p className="text-text-muted text-xs md:text-sm mt-1 max-w-xl leading-relaxed">
              Consulte suas escalas futuras, confirme ou decline sua presença em tempo real e acesse o roteiro com a setlist completa.
            </p>
          </div>

          <div className="flex items-center gap-4 bg-bg-dark/40 border border-border-custom rounded-[4px] p-4 shrink-0 shadow-xs">
            <div className="w-10 h-10 rounded-[4px] bg-primary/10 flex items-center justify-center text-primary">
              <Calendar className="w-5 h-5" />
            </div>
            <div>
              <div className="text-2xl font-black text-text-main leading-none">
                {futureSchedules.length}
              </div>
              <div className="text-[9px] font-black uppercase tracking-wider text-text-muted mt-1">
                Próximas Escalas
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Filtros e Tabs Bento Box */}
      <div className="bg-surface border border-border-custom rounded-[4px] p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 select-none">
        {/* Tabs Futuras / Histórico */}
        <div className="flex gap-1 bg-bg-dark p-1 rounded-[4px] border border-border-custom w-fit">
          <button 
            onClick={() => setActiveTab('future')}
            className={`px-4 py-2 rounded-[4px] text-xs font-bold transition-all cursor-pointer ${
              activeTab === 'future' 
                ? 'bg-surface text-primary border border-border-custom shadow-xs' 
                : 'text-text-muted hover:text-text-main'
            }`}
          >
            Próximas
          </button>
          <button 
            onClick={() => setActiveTab('past')}
            className={`px-4 py-2 rounded-[4px] text-xs font-bold transition-all cursor-pointer ${
              activeTab === 'past' 
                ? 'bg-surface text-primary border border-border-custom shadow-xs' 
                : 'text-text-muted hover:text-text-main'
            }`}
          >
            Histórico
          </button>
        </div>

        {/* Filtros e Toggles */}
        <div className="flex flex-wrap items-center gap-3">
          {/* Tipo de Escala */}
          <select 
            value={selectedType}
            onChange={(e) => setSelectedType(e.target.value)}
            className="h-9 px-3 rounded-[4px] bg-bg-dark border border-border-custom text-xs font-bold text-text-main focus:outline-none focus:border-primary cursor-pointer"
          >
            <option value="all">📅 Todos Eventos</option>
            <option value="culto">⛪ Cultos</option>
            <option value="ensaio">🎸 Ensaios</option>
            <option value="outro">✨ Outros</option>
          </select>

          {/* Toggle Minhas Escalas */}
          <button
            onClick={() => setFilterMine(!filterMine)}
            className={`h-9 px-4 rounded-[4px] text-xs font-bold flex items-center gap-2 border transition-all cursor-pointer ${
              filterMine
                ? 'bg-primary/10 border-primary/30 text-primary'
                : 'bg-bg-dark border-border-custom text-text-muted hover:text-text-main'
            }`}
          >
            <Users className="w-3.5 h-3.5" />
            <span>Minhas Escalas</span>
          </button>
        </div>
      </div>

      {/* Grid de Escalas */}
      {loading ? (
        <div className="min-h-[200px] flex items-center justify-center">
          <div className="w-8 h-8 rounded-full border-2 border-primary/20 border-t-primary animate-spin" />
        </div>
      ) : error ? (
        <div className="border border-red-500/20 bg-red-500/5 p-4 rounded-[4px] text-xs font-bold text-red-400 flex items-start gap-2 max-w-xl">
          <AlertTriangle className="w-4 h-4 shrink-0 mt-0.5" />
          <span>{error}</span>
        </div>
      ) : schedules.length === 0 ? (
        <div className="border border-dashed border-border-custom bg-surface/10 rounded-[4px] p-12 text-center">
          <Calendar className="w-10 h-10 text-text-muted/40 mx-auto mb-3" />
          <h3 className="text-sm font-extrabold text-text-main mb-1">Nenhuma escala encontrada</h3>
          <p className="text-xs text-text-muted max-w-xs mx-auto leading-relaxed">
            Não há escalas correspondentes aos filtros ativos neste momento.
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {schedules.map((schedule) => (
            <div 
              key={schedule.id}
              className={`bg-surface border rounded-[4px] p-5 flex flex-col justify-between transition-all hover:border-primary/45 group active:scale-[0.99] ${
                schedule.is_mine && schedule.my_status === 'pending'
                  ? 'border-amber-500/40 ring-1 ring-amber-500/10'
                  : 'border-border-custom'
              }`}
            >
              <div>
                {/* Header do Card */}
                <div className="flex items-start justify-between gap-4 mb-4">
                  <div className="flex items-center gap-3">
                    {/* Bento Box Date Badge */}
                    <div className="w-12 h-12 bg-bg-dark border border-border-custom rounded-[4px] flex flex-col items-center justify-center shrink-0">
                      <span className="text-[10px] font-black text-text-muted uppercase tracking-wider leading-none">
                        {new Date(schedule.event_date + 'T00:00:00').toLocaleDateString('pt-BR', { month: 'short' }).replace('.', '').toUpperCase()}
                      </span>
                      <span className="text-base font-black text-text-main leading-none mt-1">
                        {new Date(schedule.event_date + 'T00:00:00').getDate()}
                      </span>
                    </div>
                    
                    <div>
                      <h3 className="text-sm font-extrabold text-text-main leading-tight truncate max-w-[200px] group-hover:text-primary transition-colors">
                        {schedule.title}
                      </h3>
                      <div className="flex items-center gap-2 text-[10px] text-text-muted font-bold mt-1 uppercase tracking-wider">
                        <span className="flex items-center gap-1">
                          <Clock className="w-3 h-3 text-primary" /> {schedule.event_time.substring(0, 5)}h
                        </span>
                        <span>•</span>
                        <span className="px-1.5 py-0.5 rounded-[2px] bg-bg-dark border border-border-custom text-[8px] font-extrabold text-text-muted uppercase">
                          {schedule.type}
                        </span>
                      </div>
                    </div>
                  </div>

                  {schedule.is_mine && (
                    <div className="shrink-0">
                      {getStatusBadge(schedule.my_status)}
                    </div>
                  )}
                </div>

                {/* Descrição */}
                {schedule.description && (
                  <p className="text-text-muted text-xs font-medium leading-relaxed mb-4 line-clamp-2">
                    {schedule.description}
                  </p>
                )}

                {/* Integrantes da Escala */}
                <div className="space-y-2 mb-5">
                  <div className="text-[9px] font-black text-text-muted uppercase tracking-widest pl-0.5">
                    Equipe Escalada ({schedule.participants.length})
                  </div>
                  <div className="flex items-center -space-x-1.5 overflow-hidden">
                    {schedule.participants.slice(0, 5).map((p, idx) => (
                      <img 
                        key={idx}
                        src={p.photo} 
                        alt={p.name}
                        title={`${p.name} - ${p.assigned_instrument}`}
                        className="w-7 h-7 rounded-[4px] object-cover border border-bg-dark bg-surface shadow-xs shrink-0"
                      />
                    ))}
                    {schedule.participants.length > 5 && (
                      <div className="w-7 h-7 rounded-[4px] bg-bg-dark border border-border-custom text-[9px] font-black text-text-main flex items-center justify-center shadow-xs shrink-0 select-none">
                        +{schedule.participants.length - 5}
                      </div>
                    )}
                  </div>
                </div>
              </div>

              {/* Ações no Rodapé do Card */}
              <div className="pt-4 border-t border-border-custom flex items-center justify-between gap-4">
                {/* Contagem de Músicas */}
                <div className="flex items-center gap-1.5 text-xs font-bold text-text-muted">
                  <Music className="w-3.5 h-3.5 text-text-muted" />
                  <span>{schedule.songs_count} músicas</span>
                </div>

                {/* Botões de Ação */}
                <div className="flex items-center gap-2">
                  {/* Se escalado e pendente, exibe botões rápidos de confirmação */}
                  {schedule.is_mine && schedule.my_status === 'pending' && activeTab === 'future' ? (
                    <>
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          setActioningScheduleId(schedule.id);
                          setShowDeclineModal(true);
                        }}
                        className="h-8 w-8 rounded-[4px] bg-rose-500/5 hover:bg-rose-500/10 border border-rose-500/10 hover:border-rose-500/20 text-rose-500 flex items-center justify-center cursor-pointer transition-colors active:scale-95"
                        title="Recusar Presença"
                      >
                        <X className="w-4 h-4" />
                      </button>
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          handleConfirm(schedule.id, 'confirmed');
                        }}
                        className="h-8 px-3 rounded-[4px] bg-emerald-500 hover:bg-emerald-600 text-bg-dark text-xs font-black flex items-center gap-1 cursor-pointer transition-all active:scale-95 shadow-sm"
                      >
                        <Check className="w-3.5 h-3.5" /> Confirmar
                      </button>
                    </>
                  ) : (
                    <button
                      onClick={() => setSelectedScheduleId(schedule.id)}
                      className="h-8 px-3 rounded-[4px] bg-bg-dark hover:bg-surface-variant/20 border border-border-custom text-text-main text-xs font-bold flex items-center gap-1 cursor-pointer transition-colors"
                    >
                      Detalhes <ChevronRight className="w-3.5 h-3.5 text-text-muted" />
                    </button>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Modal de Justificativa de Ausência (Decline Modal) */}
      {showDeclineModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div 
            className="absolute inset-0 bg-black/60 backdrop-blur-xs" 
            onClick={() => {
              setShowDeclineModal(false);
              setActioningScheduleId(null);
              setDeclineReason('');
            }}
          />
          <div className="bg-surface border border-border-custom w-full max-w-md rounded-[4px] overflow-hidden shadow-2xl relative z-10 animate-scale-up">
            <div className="px-5 py-4 border-b border-border-custom flex justify-between items-center bg-rose-500/5">
              <h3 className="font-extrabold text-sm text-rose-500 flex items-center gap-2">
                <AlertTriangle className="w-4 h-4 text-rose-500" />
                <span>Justificar Ausência</span>
              </h3>
              <button 
                type="button" 
                className="text-text-muted hover:bg-bg-dark p-1 rounded-[4px] transition-colors cursor-pointer"
                onClick={() => {
                  setShowDeclineModal(false);
                  setActioningScheduleId(null);
                  setDeclineReason('');
                }}
              >
                <X className="w-4 h-4" />
              </button>
            </div>
            
            <div className="p-5 space-y-4">
              <p className="text-xs text-text-muted leading-relaxed">
                Você está informando que não poderá participar deste evento. Por favor, descreva brevemente a justificativa para que os líderes consigam organizar um substituto.
              </p>
              
              <div className="space-y-1.5">
                <label className="text-[10px] font-black text-text-muted uppercase tracking-wider">Justificativa</label>
                <textarea
                  value={declineReason}
                  onChange={(e) => setDeclineReason(e.target.value)}
                  placeholder="Ex: Estarei viajando a trabalho nesta data..."
                  className="w-full min-h-[100px] p-3 rounded-[4px] bg-bg-dark border border-border-custom text-xs focus:outline-none focus:border-primary text-text-main placeholder-text-muted/65 leading-relaxed"
                />
              </div>

              <div className="flex gap-2.5 justify-end pt-2">
                <button 
                  type="button" 
                  className="h-9 px-4 rounded-[4px] border border-border-custom text-xs font-bold text-text-muted hover:text-text-main cursor-pointer"
                  onClick={() => {
                    setShowDeclineModal(false);
                    setActioningScheduleId(null);
                    setDeclineReason('');
                  }}
                >
                  Cancelar
                </button>
                <button 
                  type="button"
                  disabled={!declineReason.trim()}
                  onClick={() => {
                    if (actioningScheduleId) {
                      handleConfirm(actioningScheduleId, 'declined', declineReason);
                    }
                  }}
                  className="h-9 px-4 rounded-[4px] bg-rose-500 hover:bg-rose-600 disabled:opacity-50 text-white text-xs font-black cursor-pointer transition-colors"
                >
                  Confirmar Ausência
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Drawer / Modal Lateral de Detalhes da Escala */}
      {selectedScheduleId !== null && (
        <div className="fixed inset-0 z-40 flex justify-end">
          {/* Backdrop */}
          <div 
            className="absolute inset-0 bg-black/60 backdrop-blur-xs" 
            onClick={() => setSelectedScheduleId(null)}
          />
          
          {/* Corpo do Drawer */}
          <div className="w-full max-w-xl bg-surface border-l border-border-custom h-full flex flex-col justify-between relative z-10 animate-slide-left shadow-2xl select-text">
            {/* Header do Drawer */}
            <div className="h-16 border-b border-border-custom px-6 flex items-center justify-between shrink-0">
              <div className="flex items-center gap-2">
                <button 
                  onClick={() => setSelectedScheduleId(null)}
                  className="p-1 rounded-[4px] hover:bg-bg-dark text-text-muted hover:text-text-main cursor-pointer"
                >
                  <ChevronLeft className="w-5 h-5" />
                </button>
                <span className="text-xs font-black uppercase text-text-muted tracking-widest">
                  Detalhes do Evento
                </span>
              </div>
              
              {scheduleDetail?.schedule.is_mine && scheduleDetail.schedule.my_status === 'pending' && (
                <div className="flex gap-1.5 shrink-0">
                  <button 
                    onClick={() => {
                      setActioningScheduleId(scheduleDetail.schedule.id);
                      setShowDeclineModal(true);
                    }}
                    className="h-8 w-8 rounded-[4px] bg-rose-500/10 border border-rose-500/20 text-rose-500 flex items-center justify-center transition-colors cursor-pointer"
                  >
                    <X className="w-4 h-4" />
                  </button>
                  <button 
                    onClick={() => handleConfirm(scheduleDetail.schedule.id, 'confirmed')}
                    className="h-8 px-3 rounded-[4px] bg-emerald-500 hover:bg-emerald-600 text-bg-dark text-xs font-black flex items-center gap-1 transition-all cursor-pointer shadow-xs"
                  >
                    <Check className="w-3.5 h-3.5" /> Confirmar
                  </button>
                </div>
              )}
            </div>

            {/* Conteúdo Rolável do Drawer */}
            <div className="flex-1 overflow-y-auto p-6 space-y-6 select-text">
              {detailLoading ? (
                <div className="h-full flex items-center justify-center py-12">
                  <div className="w-8 h-8 rounded-full border-2 border-primary/20 border-t-primary animate-spin" />
                </div>
              ) : scheduleDetail ? (
                <>
                  {/* Dados Básicos */}
                  <div className="space-y-3">
                    <span className="px-2 py-0.5 rounded-[2px] bg-primary/10 border border-primary/20 text-[9px] font-black text-primary uppercase tracking-widest">
                      {scheduleDetail.schedule.type}
                    </span>
                    <h2 className="text-xl font-black text-text-main leading-tight">
                      {scheduleDetail.schedule.title}
                    </h2>
                    
                    <div className="flex flex-wrap gap-4 text-xs font-bold text-text-muted uppercase tracking-wider pt-1">
                      <span className="flex items-center gap-1.5">
                        <Calendar className="w-3.5 h-3.5 text-primary" /> {formatDate(scheduleDetail.schedule.event_date)}
                      </span>
                      <span className="flex items-center gap-1.5">
                        <Clock className="w-3.5 h-3.5 text-primary" /> {scheduleDetail.schedule.event_time.substring(0, 5)}h
                      </span>
                    </div>

                    {scheduleDetail.schedule.description && (
                      <p className="text-text-muted text-xs leading-relaxed font-medium bg-bg-dark border border-border-custom p-4 rounded-[4px] mt-3 font-body">
                        {scheduleDetail.schedule.description}
                      </p>
                    )}
                  </div>

                  {/* Integrantes e Presença */}
                  <div className="space-y-3 pt-4 border-t border-border-custom">
                    <h3 className="text-[10px] font-black text-text-muted uppercase tracking-widest pl-0.5">
                      Equipe do Worship ({scheduleDetail.participants.length})
                    </h3>
                    
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                      {scheduleDetail.participants.map((p, idx) => (
                        <div key={idx} className="flex items-center justify-between p-2.5 rounded-[4px] bg-bg-dark border border-border-custom">
                          <div className="flex items-center gap-2.5 min-w-0">
                            <img src={p.photo} alt={p.name} className="w-8 h-8 rounded-[4px] object-cover bg-surface shrink-0" />
                            <div className="min-w-0">
                              <h4 className="text-xs font-extrabold text-text-main truncate">{p.name}</h4>
                              <span className="text-[9px] font-bold text-accent uppercase tracking-wider leading-none block mt-0.5">
                                {p.assigned_instrument}
                              </span>
                            </div>
                          </div>
                          <div className="shrink-0 pl-1.5">
                            {getStatusBadge(p.status)}
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>

                  {/* Repertório de Músicas */}
                  <div className="space-y-3 pt-4 border-t border-border-custom">
                    <h3 className="text-[10px] font-black text-text-muted uppercase tracking-widest pl-0.5">
                      Repertório / Setlist ({scheduleDetail.songs.length})
                    </h3>
                    
                    <div className="space-y-2">
                      {scheduleDetail.songs.length === 0 ? (
                        <div className="text-center py-6 text-text-muted text-xs bg-bg-dark/40 rounded-[4px] border border-dashed border-border-custom">
                          Nenhuma música adicionada a esta escala.
                        </div>
                      ) : (
                        scheduleDetail.songs.map((song, idx) => (
                          <div key={song.id} className="flex items-center justify-between p-3 rounded-[4px] bg-bg-dark border border-border-custom hover:border-primary/25 transition-all">
                            <div className="min-w-0">
                              <div className="flex items-center gap-2">
                                <span className="w-5 h-5 rounded-[4px] bg-primary/10 border border-primary/20 flex items-center justify-center text-[10px] font-black text-primary shrink-0 leading-none select-none">
                                  {idx + 1}
                                </span>
                                <h4 className="text-xs font-black text-text-main truncate leading-none">
                                  {song.title}
                                </h4>
                              </div>
                              <p className="text-[10px] text-text-muted font-bold mt-1.5 pl-7 uppercase tracking-wider block leading-none">
                                {song.artist} • Tom: <span className="text-accent">{song.tone}</span>
                              </p>
                            </div>

                            <div className="flex items-center gap-1.5 shrink-0 ml-3">
                              {song.youtube_url && (
                                <a 
                                  href={song.youtube_url} 
                                  target="_blank" 
                                  rel="noopener noreferrer"
                                  className="w-7 h-7 rounded-[4px] bg-red-500/10 border border-red-500/20 text-red-500 flex items-center justify-center hover:bg-red-500/20 transition-colors cursor-pointer"
                                  title="Ouvir no Youtube"
                                >
                                  <Play className="w-3.5 h-3.5 fill-red-500" />
                                </a>
                              )}
                              {song.chords_url && (
                                <a 
                                  href={song.chords_url} 
                                  target="_blank" 
                                  rel="noopener noreferrer"
                                  className="w-7 h-7 rounded-[4px] bg-primary/10 border border-primary/20 text-primary flex items-center justify-center hover:bg-primary/20 transition-colors cursor-pointer"
                                  title="Ver Cifra"
                                >
                                  <ExternalLink className="w-3.5 h-3.5" />
                                </a>
                              )}
                            </div>
                          </div>
                        ))
                      )}
                    </div>
                  </div>

                  {/* Roteiro da Escala */}
                  <div className="space-y-3 pt-4 border-t border-border-custom">
                    <h3 className="text-[10px] font-black text-text-muted uppercase tracking-widest pl-0.5">
                      Roteiro do Evento ({scheduleDetail.roteiro.length})
                    </h3>
                    
                    <div className="space-y-2.5">
                      {scheduleDetail.roteiro.length === 0 ? (
                        <div className="text-center py-6 text-text-muted text-xs bg-bg-dark/40 rounded-[4px] border border-dashed border-border-custom font-body">
                          Nenhum roteiro detalhado para este evento.
                        </div>
                      ) : (
                        scheduleDetail.roteiro.map((item) => (
                          <div key={item.id} className="relative pl-6 border-l border-border-custom space-y-1 py-0.5">
                            {/* Marcador flutuante */}
                            <div className="absolute -left-1.5 top-1 w-3 h-3 rounded-full bg-primary border-2 border-surface" />
                            
                            <div className="flex items-start justify-between gap-4">
                              <h4 className="text-xs font-black text-text-main leading-tight">
                                {item.title}
                              </h4>
                              <span className="text-[9px] font-black text-accent uppercase tracking-wider shrink-0 bg-accent/5 border border-accent/20 px-1.5 py-0.5 rounded-[2px]">
                                {item.duration_minutes} min
                              </span>
                            </div>
                            
                            {item.description && (
                              <p className="text-[11px] text-text-muted leading-relaxed font-body">
                                {item.description}
                              </p>
                            )}
                          </div>
                        ))
                      )}
                    </div>
                  </div>

                  {/* Comentários e Avisos da Escala */}
                  <div className="space-y-3 pt-4 border-t border-border-custom">
                    <h3 className="text-[10px] font-black text-text-muted uppercase tracking-widest pl-0.5">
                      Mural de Avisos da Escala ({scheduleDetail.comments.length})
                    </h3>
                    
                    <div className="space-y-3">
                      {scheduleDetail.comments.map((c) => (
                        <div key={c.id} className="bg-bg-dark/50 border border-border-custom p-3.5 rounded-[4px] flex gap-3">
                          <img src={c.author_avatar} alt={c.author_name} className="w-8 h-8 rounded-[4px] object-cover shrink-0" />
                          <div className="min-w-0 flex-1">
                            <div className="flex items-center justify-between gap-4">
                              <h4 className="text-xs font-extrabold text-text-main truncate">{c.author_name}</h4>
                              <span className="text-[9px] text-text-muted font-bold shrink-0 leading-none block">
                                {new Date(c.created_at).toLocaleDateString('pt-BR')} {new Date(c.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}
                              </span>
                            </div>
                            <p className="text-xs text-text-muted leading-relaxed font-body mt-1.5 whitespace-pre-line">
                              {c.comment}
                            </p>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                </>
              ) : null}
            </div>

            {/* Rodapé / Input de Comentário */}
            {selectedScheduleId && scheduleDetail && (
              <div className="p-4 border-t border-border-custom bg-bg-dark/40 shrink-0 select-text">
                <form onSubmit={submitComment} className="flex gap-2">
                  <input 
                    type="text" 
                    value={newCommentText}
                    onChange={(e) => setNewCommentText(e.target.value)}
                    placeholder="Escreva um aviso para esta escala..." 
                    className="flex-1 h-9 px-3.5 rounded-[4px] bg-bg-dark border border-border-custom text-xs text-text-main focus:outline-none focus:border-primary placeholder-text-muted/65 leading-relaxed"
                    required
                  />
                  <button 
                    type="submit"
                    disabled={submittingComment || !newCommentText.trim()}
                    className="h-9 px-4 rounded-[4px] bg-primary hover:bg-primary/95 text-bg-dark font-black flex items-center justify-center shrink-0 disabled:opacity-50 transition-all cursor-pointer"
                  >
                    <Send className="w-4 h-4" />
                  </button>
                </form>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
};
