import React, { useState, useEffect } from 'react';
import { 
  Users, 
  Music, 
  Sparkles, 
  CalendarDays, 
  BookOpen, 
  PlusCircle, 
  ClipboardList, 
  AlertTriangle,
  ArrowRight,
  TrendingUp
} from 'lucide-react';
import { ScheduleWidget } from '../components/dashboard/ScheduleWidget';
import { MetronomeWidget } from '../components/dashboard/MetronomeWidget';
import { NoticesWidget } from '../components/dashboard/NoticesWidget';
import { useAuth } from '../context/AuthContext';

export const DashboardView: React.FC = () => {
  const { user } = useAuth();
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchDashboardData = async () => {
      try {
        const response = await fetch('/api/admin/dashboard_data_api.php');
        
        if (response.status === 401) {
          // AuthContext já cuida do redirecionamento, mas por segurança paramos aqui
          return;
        }

        if (!response.ok) {
          throw new Error('Erro ao carregar dados do servidor');
        }

        const res = await response.json();
        if (res.success) {
          setData(res.data);
        } else {
          throw new Error(res.error || 'Falha ao buscar dados');
        }
      } catch (err: any) {
        console.error('Erro ao carregar dados do painel:', err);
        setError(err.message || 'Erro de conexão com a API');
        
        // Mock de dados elegante no ambiente de desenvolvimento local
        if (import.meta.env.DEV) {
          setData({
            salutation: 'Bom dia',
            userName: user?.name || 'Músico',
            userRole: 'admin',
            totalMusicas: 142,
            totalMembros: 28,
            statsMembros: { vocals: 12, instrumentalists: 16 },
            niverCount: 3,
            proximoNiver: { name: 'Diego Vilela', dia: 28 },
            unreadCount: 1,
            ultimoAviso: 'Ensaio Geral com Todos os Grupos',
            totalSchedules: 2,
            nextSchedule: {
              id: 105,
              event_date: new Date().toISOString().split('T')[0],
              event_time: '19:00:00',
              event_type: 'Culto de Celebração de Domingo',
              my_status: 'confirmed',
              my_role: 'Guitarrista / Líder'
            },
            ultimaMusica: { title: 'Porque Ele Vive', artist: 'Harpa Cristã', tone: 'G' }
          });
        }
      } finally {
        setLoading(false);
      }
    };

    fetchDashboardData();
  }, [user]);

  // Renderizador do Skeleton Loader de alta fidelidade
  if (loading) {
    return (
      <div className="space-y-6 animate-pulse select-none">
        {/* Linha 1: Boas-vindas Skeleton */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="md:col-span-2 h-44 bg-surface border border-border-custom rounded-[4px]" />
          <div className="h-44 bg-surface border border-border-custom rounded-[4px]" />
        </div>
        
        {/* Linha 2: Bento Grid Skeleton */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="h-64 bg-surface border border-border-custom rounded-[4px]" />
          <div className="h-64 bg-surface border border-border-custom rounded-[4px]" />
          <div className="h-64 bg-surface border border-border-custom rounded-[4px]" />
        </div>
        
        {/* Linha 3: Rodapé */}
        <div className="h-32 bg-surface border border-border-custom rounded-[4px]" />
      </div>
    );
  }

  // Tratamento de erro na conexão local se não estiver em Dev
  if (error && !data) {
    return (
      <div className="bg-surface border border-red-500/20 p-8 rounded-[4px] text-center max-w-md mx-auto my-12">
        <AlertTriangle className="w-12 h-12 text-red-500 mx-auto mb-4 animate-bounce" />
        <h3 className="text-base font-bold text-text-main font-hanken">Falha de Conectividade</h3>
        <p className="text-xs text-text-muted mt-2 leading-relaxed">
          Não conseguimos estabelecer uma conexão segura com o servidor Hostinger ou os cookies locais expiraram.
        </p>
        <button
          onClick={() => window.location.reload()}
          className="mt-6 px-4 py-2 bg-primary hover:bg-primary-hover text-white text-xs font-bold rounded-[4px] cursor-pointer"
        >
          Tentar Novamente
        </button>
      </div>
    );
  }

  return (
    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-3 duration-300">
      
      {/* LINHA 1: Bento Superior (Boas-vindas + Estatísticas Rápidas) */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        {/* Painel Boas-vindas Gigante */}
        <div className="md:col-span-2 bg-surface border border-border-custom rounded-[4px] p-6 relative overflow-hidden flex flex-col justify-between min-h-[176px] transition-all hover:border-primary/30 group">
          {/* Decorações do Design */}
          <div className="absolute top-0 right-0 w-48 h-48 bg-primary/3 rounded-full blur-3xl -mr-16 -mt-16 transition-all group-hover:bg-primary/5" />
          
          <div className="relative z-10">
            <span className="text-[10px] font-black text-accent uppercase tracking-widest bg-accent/10 border border-accent/20 px-2 py-0.5 rounded-[4px]">
              Visão Geral do Altar
            </span>
            <h1 className="text-2xl font-black text-text-main font-hanken mt-3 leading-tight tracking-tight">
              {data.salutation}, {user?.name}!
            </h1>
            <p className="text-xs text-text-muted mt-1.5 leading-relaxed max-w-xl">
              Este é o painel de comando do seu ministério de louvor. Daqui você visualiza sua próxima escala, ajusta o andamento das músicas no metrônomo de ensaio e acessa ferramentas litúrgicas avançadas.
            </p>
          </div>

          <div className="flex flex-wrap items-center gap-3 pt-4 border-t border-border-custom/50 relative z-10 mt-4">
            <a
              href="../admin/repertorio.php"
              className="flex items-center gap-1.5 px-3 py-1.5 bg-primary hover:bg-primary-hover text-white rounded-[4px] text-xs font-bold transition-all active:scale-95 shadow-sm"
            >
              <Music className="w-3.5 h-3.5" />
              <span>Ver Repertório</span>
            </a>
            <a
              href="../admin/agenda.php"
              className="flex items-center gap-1.5 px-3 py-1.5 bg-surface border border-border-custom hover:bg-bg-dark text-text-main hover:text-text-main rounded-[4px] text-xs font-bold transition-colors"
            >
              <CalendarDays className="w-3.5 h-3.5 text-text-muted" />
              <span>Agenda Completa</span>
            </a>
          </div>
        </div>

        {/* Painel Estatísticas Rápidas de Equipe */}
        <div className="bg-surface border border-border-custom rounded-[4px] p-6 flex flex-col justify-between transition-all hover:border-primary/30 group relative">
          <div className="flex items-center justify-between select-none">
            <span className="text-[10px] font-black text-text-muted uppercase tracking-widest">
              Dados do Ministério
            </span>
            <TrendingUp className="w-4 h-4 text-primary" />
          </div>

          <div className="my-3 space-y-3 select-none">
            {/* Repertório Count */}
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Music className="w-4 h-4 text-text-muted" />
                <span className="text-xs text-text-muted">Repertório:</span>
              </div>
              <span className="text-sm font-black text-text-main font-hanken">
                {data.totalMusicas} músicas
              </span>
            </div>

            {/* Membros Count */}
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Users className="w-4 h-4 text-text-muted" />
                <span className="text-xs text-text-muted">Equipe de Louvor:</span>
              </div>
              <span className="text-sm font-black text-text-main font-hanken">
                {data.totalMembros} membros
              </span>
            </div>

            {/* Aniversariantes */}
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Sparkles className="w-4 h-4 text-accent" />
                <span className="text-xs text-text-muted">Niver do Mês:</span>
              </div>
              <span className="text-xs font-bold text-accent">
                {data.niverCount > 0 
                  ? `${data.niverCount} ${data.niverCount === 1 ? 'membro' : 'membros'}` 
                  : 'Nenhum neste mês'}
              </span>
            </div>
          </div>

          <div className="border-t border-border-custom/50 pt-3 flex items-center justify-between text-[9px] font-bold text-text-muted uppercase tracking-wider select-none mt-auto">
            <span>PIB Oliveira</span>
            {data.proximoNiver && (
              <span className="text-text-muted truncate max-w-[150px]">
                Próximo: {data.proximoNiver.name} ({data.proximoNiver.dia})
              </span>
            )}
          </div>
        </div>

      </div>

      {/* LINHA 2: Bento Grid Principal de 3 Colunas (Widgets Interativos) */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        {/* Widget 1: Próxima Escala */}
        <ScheduleWidget 
          schedule={data.nextSchedule} 
          totalSchedules={data.totalSchedules} 
        />

        {/* Widget 2: Metrônomo */}
        <MetronomeWidget />

        {/* Widget 3: Mural de Avisos */}
        <NoticesWidget 
          unreadCount={data.unreadCount} 
          lastNoticeTitle={data.ultimoAviso} 
        />

      </div>

      {/* LINHA 3: Bento Inferior (Atalhos e Ações Rápidas de Administração) */}
      <div className="bg-surface border border-border-custom rounded-[4px] p-6 transition-all hover:border-primary/20">
        <h3 className="text-xs font-black text-text-main font-hanken uppercase tracking-wider mb-4 select-none">
          Atalhos de Acesso Rápido
        </h3>
        
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
          
          <a
            href="../admin/musica_adicionar.php"
            className="flex flex-col items-center justify-center p-4 border border-border-custom hover:border-primary/30 bg-bg-dark/20 hover:bg-surface rounded-[4px] text-center transition-all group active:scale-95 shadow-2xs"
          >
            <PlusCircle className="w-5 h-5 text-text-muted group-hover:text-primary transition-colors mb-2" />
            <span className="text-xs font-bold text-text-main">Nova Música</span>
            <span className="text-[9px] text-text-muted mt-1">Repertório</span>
          </a>

          <a
            href="../admin/escala.php"
            className="flex flex-col items-center justify-center p-4 border border-border-custom hover:border-primary/30 bg-bg-dark/20 hover:bg-surface rounded-[4px] text-center transition-all group active:scale-95 shadow-2xs"
          >
            <ClipboardList className="w-5 h-5 text-text-muted group-hover:text-primary transition-colors mb-2" />
            <span className="text-xs font-bold text-text-main">Montar Escala</span>
            <span className="text-[9px] text-text-muted mt-1">Liderança</span>
          </a>

          <a
            href="../admin/indisponibilidade.php"
            className="flex flex-col items-center justify-center p-4 border border-border-custom hover:border-primary/30 bg-bg-dark/20 hover:bg-surface rounded-[4px] text-center transition-all group active:scale-95 shadow-2xs"
          >
            <AlertTriangle className="w-5 h-5 text-text-muted group-hover:text-accent transition-colors mb-2" />
            <span className="text-xs font-bold text-text-main">Ausências</span>
            <span className="text-[9px] text-text-muted mt-1">Indisponível</span>
          </a>

          <a
            href="../admin/leitura.php"
            className="flex flex-col items-center justify-center p-4 border border-border-custom hover:border-primary/30 bg-bg-dark/20 hover:bg-surface rounded-[4px] text-center transition-all group active:scale-95 shadow-2xs"
          >
            <BookOpen className="w-5 h-5 text-text-muted group-hover:text-primary transition-colors mb-2" />
            <span className="text-xs font-bold text-text-main">Leitura Bíblica</span>
            <span className="text-[9px] text-text-muted mt-1">Progresso</span>
          </a>

        </div>

        {/* Última Música Cadastrada Destaque */}
        {data.ultimaMusica && (
          <div className="mt-5 border-t border-border-custom/50 pt-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs bg-bg-dark/30 p-3 rounded-[4px] border border-border-custom/30 select-none">
            <div className="flex items-center gap-2">
              <span className="text-[9px] font-black bg-primary/10 text-primary border border-primary/20 px-2 py-0.5 rounded-[4px] uppercase tracking-wider">
                Novidade
              </span>
              <span className="text-text-muted">Última música adicionada:</span>
              <strong className="text-text-main font-extrabold">{data.ultimaMusica.title}</strong>
              <span className="text-text-muted font-semibold">({data.ultimaMusica.artist})</span>
            </div>
            
            <div className="flex items-center gap-2 text-text-muted font-bold self-end sm:self-auto">
              <span>Tom:</span>
              <span className="bg-bg-dark border border-border-custom text-accent px-2.5 py-0.5 rounded-[4px] font-black font-hanken">
                {data.ultimaMusica.tone || 'N/A'}
              </span>
              <a 
                href="../admin/repertorio.php"
                className="ml-2 text-primary hover:underline flex items-center gap-0.5"
              >
                <span>Ver cifra</span>
                <ArrowRight className="w-3.5 h-3.5" />
              </a>
            </div>
          </div>
        )}

      </div>

    </div>
  );
};
