import React, { useEffect, useState } from 'react';
import { 
  Megaphone, 
  Search, 
  Plus, 
  AlertTriangle, 
  Heart, 
  CheckCircle2, 
  Pin, 
  Archive, 
  Trash2, 
  Edit2, 
  BarChart2, 
  Tag as TagIcon, 
  X, 
  Calendar, 
  Clock
} from 'lucide-react';
import { useAuth } from '../context/AuthContext';

interface Tag {
  id: number;
  name: string;
  color: string;
  icon: string;
  is_default?: boolean | number;
}

interface Reader {
  name: string;
  read_at: string;
}

interface AvisoStats {
  read_count: number;
  total_users: number;
  read_percentage: number;
  readers: Reader[];
}

interface Aviso {
  id: number;
  title: string;
  message: string;
  priority: 'normal' | 'important' | 'urgent';
  type: string;
  target_audience: 'all' | 'team' | 'admins';
  expires_at: string | null;
  created_by: number;
  created_at: string;
  archived_at: string | null;
  author_name: string;
  author_avatar: string;
  reactions: {
    like: number;
    confirm: number;
  };
  user_reacted: {
    like: boolean;
    confirm: boolean;
  };
  tags: Tag[];
  is_read: boolean;
  is_pinned?: boolean | number;
}

export const AvisosView: React.FC = () => {
  const { user: currentUser } = useAuth();
  const isAdmin = currentUser?.role === 'admin';

  // Estados principais
  const [loading, setLoading] = useState(true);
  const [avisos, setAvisos] = useState<Aviso[]>([]);
  const [tags, setTags] = useState<Tag[]>([]);
  const [error, setError] = useState<string | null>(null);

  // Estados de filtros
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<'active' | 'archived' | 'expired'>('active');
  const [priorityFilter, setPriorityFilter] = useState<string>('all');
  const [typeFilter, setTypeFilter] = useState<string>('all');
  const [selectedTagFilters, setSelectedTagFilters] = useState<number[]>([]);

  // Estados de drawers / modais
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [statsDrawerOpen, setStatsDrawerOpen] = useState(false);
  const [tagManagerOpen, setTagManagerOpen] = useState(false);

  // Estados do formulário de aviso
  const [avisoId, setAvisoId] = useState<number | null>(null);
  const [formTitle, setFormTitle] = useState('');
  const [formMessage, setFormMessage] = useState('');
  const [formPriority, setFormPriority] = useState<'normal' | 'important' | 'urgent'>('normal');
  const [formType, setFormType] = useState('geral');
  const [formAudience, setFormAudience] = useState<'all' | 'team' | 'admins'>('all');
  const [formExpiresAt, setFormExpiresAt] = useState('');
  const [formSelectedTags, setFormSelectedTags] = useState<number[]>([]);

  // Estados do formulário de tag
  const [newTagName, setNewTagName] = useState('');
  const [newTagColor, setNewTagColor] = useState('#2e7eed');

  // Estados de estatísticas
  const [selectedAvisoForStats, setSelectedAvisoForStats] = useState<Aviso | null>(null);
  const [statsLoading, setStatsLoading] = useState(false);
  const [statsData, setStatsData] = useState<AvisoStats | null>(null);

  // Buscar avisos e tags
  const fetchAvisosAndTags = async () => {
    try {
      setLoading(true);
      setError(null);

      // Carregar tags primeiro
      const tagsRes = await fetch('../api/admin/avisos_api.php?action=list_tags');
      const tagsData = await tagsRes.json();
      if (tagsData.success) {
        setTags(tagsData.tags || []);
      }

      // Carregar avisos com filtros
      const isArchived = statusFilter === 'archived' ? 'true' : 'false';
      const isHistory = statusFilter === 'expired' ? 'true' : 'false';
      
      const avisosRes = await fetch(
        `../api/admin/avisos_api.php?action=list&archived=${isArchived}&history=${isHistory}&priority=${priorityFilter}&type=${typeFilter}&search=${encodeURIComponent(search)}`
      );
      const avisosData = await avisosRes.json();
      
      if (avisosData.success) {
        setAvisos(avisosData.avisos || []);
      } else {
        setError(avisosData.error || 'Erro ao carregar avisos');
      }
    } catch (err) {
      setError('Erro de conexão com a API de avisos');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAvisosAndTags();
  }, [statusFilter, priorityFilter, typeFilter, search]);

  // Alternar reação (Curtir ou Confirmar)
  const handleToggleReaction = async (avisoId: number, type: 'like' | 'confirm') => {
    try {
      const response = await fetch('../api/admin/avisos_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'toggle_reaction',
          aviso_id: avisoId,
          reaction_type: type
        })
      });
      const result = await response.json();
      
      if (result.success) {
        // Atualizar contagem no estado local reativamente e de forma suave
        setAvisos(prevAvisos => prevAvisos.map(aviso => {
          if (aviso.id === avisoId) {
            return {
              ...aviso,
              reactions: {
                ...aviso.reactions,
                [type]: result.count
              },
              user_reacted: {
                ...aviso.user_reacted,
                [type]: result.reacted
              },
              is_read: type === 'confirm' ? (result.reacted ? true : aviso.is_read) : aviso.is_read
            };
          }
          return aviso;
        }));
      }
    } catch (err) {
      console.error('Erro ao registrar reação:', err);
    }
  };

  // Fixar / Desfixar aviso
  const handleTogglePin = async (avisoId: number, currentPinned: boolean) => {
    try {
      const response = await fetch('../api/admin/avisos_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'pin_aviso',
          aviso_id: avisoId,
          is_pinned: !currentPinned
        })
      });
      const result = await response.json();
      if (result.success) {
        setAvisos(prev => prev.map(a => a.id === avisoId ? { ...a, is_pinned: !currentPinned } : a)
          .sort((a, b) => {
            const aPinned = a.is_pinned ? 1 : 0;
            const bPinned = b.is_pinned ? 1 : 0;
            return bPinned - aPinned; // Reordenar mantendo fixados no topo
          })
        );
      }
    } catch (err) {
      console.error(err);
    }
  };

  // Arquivar / Desarquivar aviso
  const handleToggleArchive = async (avisoId: number, isArchived: boolean) => {
    if (!window.confirm(isArchived ? 'Deseja desarquivar este comunicado?' : 'Deseja arquivar este comunicado? Ele sairá do mural principal.')) return;
    try {
      const response = await fetch('../api/admin/avisos_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'archive',
          id: avisoId,
          archive: !isArchived
        })
      });
      const result = await response.json();
      if (result.success) {
        fetchAvisosAndTags();
      }
    } catch (err) {
      console.error(err);
    }
  };

  // Excluir aviso permanentemente
  const handleDeleteAviso = async (avisoId: number) => {
    if (!window.confirm('Tem certeza absoluta que deseja excluir este aviso permanentemente? Esta ação não pode ser desfeita!')) return;
    try {
      const response = await fetch('../api/admin/avisos_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'delete',
          id: avisoId
        })
      });
      const result = await response.json();
      if (result.success) {
        setAvisos(prev => prev.filter(a => a.id !== avisoId));
      }
    } catch (err) {
      console.error(err);
    }
  };

  // Abrir formulário para criação
  const openCreateForm = () => {
    setAvisoId(null);
    setFormTitle('');
    setFormMessage('');
    setFormPriority('normal');
    setFormType('geral');
    setFormAudience('all');
    setFormExpiresAt('');
    setFormSelectedTags([]);
    setDrawerOpen(true);
  };

  // Abrir formulário para edição
  const openEditForm = (aviso: Aviso) => {
    setAvisoId(aviso.id);
    setFormTitle(aviso.title);
    setFormMessage(aviso.message);
    setFormPriority(aviso.priority);
    setFormType(aviso.type);
    setFormAudience(aviso.target_audience);
    setFormExpiresAt(aviso.expires_at || '');
    setFormSelectedTags(aviso.tags.map(t => t.id));
    setDrawerOpen(true);
  };

  // Salvar Aviso (Create ou Update)
  const handleSaveAviso = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!formTitle || !formMessage) return;

    try {
      const isEdit = avisoId !== null;
      const body = {
        action: isEdit ? 'update' : 'create',
        id: avisoId,
        title: formTitle,
        message: formMessage,
        priority: formPriority,
        type: formType,
        target_audience: formAudience,
        expires_at: formExpiresAt || null,
        tags: formSelectedTags
      };

      const response = await fetch('../api/admin/avisos_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });
      const result = await response.json();

      if (result.success) {
        setDrawerOpen(false);
        fetchAvisosAndTags();
      } else {
        alert('Erro ao salvar aviso: ' + (result.error || 'Erro desconhecido'));
      }
    } catch (err) {
      alert('Erro de conexão ao salvar aviso');
    }
  };

  // Alternar tag no formulário
  const handleToggleFormTag = (tagId: number) => {
    setFormSelectedTags(prev => 
      prev.includes(tagId) ? prev.filter(id => id !== tagId) : [...prev, tagId]
    );
  };

  // Alternar tag no filtro
  const handleToggleFilterTag = (tagId: number) => {
    setSelectedTagFilters(prev => 
      prev.includes(tagId) ? prev.filter(id => id !== tagId) : [...prev, tagId]
    );
  };

  // Ver estatísticas de alcance do aviso
  const handleViewStats = async (aviso: Aviso) => {
    setSelectedAvisoForStats(aviso);
    setStatsData(null);
    setStatsDrawerOpen(true);
    setStatsLoading(true);
    try {
      const response = await fetch(`../api/admin/avisos_api.php?action=get_aviso_stats&aviso_id=${aviso.id}`);
      const data = await response.json();
      if (data.success) {
        setStatsData(data);
      }
    } catch (err) {
      console.error(err);
    } finally {
      setStatsLoading(false);
    }
  };

  // Criar nova tag
  const handleCreateTag = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newTagName) return;
    try {
      const response = await fetch('../api/admin/avisos_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'create_tag',
          name: newTagName,
          color: newTagColor,
          icon: 'tag'
        })
      });
      const result = await response.json();
      if (result.success) {
        setNewTagName('');
        // Recarregar tags
        const tagsRes = await fetch('../api/admin/avisos_api.php?action=list_tags');
        const tagsData = await tagsRes.json();
        if (tagsData.success) {
          setTags(tagsData.tags || []);
        }
      }
    } catch (err) {
      console.error(err);
    }
  };

  // Deletar tag
  const handleDeleteTag = async (tagId: number) => {
    if (!window.confirm('Excluir esta tag permanentemente? Todos os avisos perderão esta etiqueta.')) return;
    try {
      const response = await fetch('../api/admin/avisos_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'delete_tag',
          id: tagId
        })
      });
      const result = await response.json();
      if (result.success) {
        setTags(prev => prev.filter(t => t.id !== tagId));
        setSelectedTagFilters(prev => prev.filter(id => id !== tagId));
      }
    } catch (err) {
      console.error(err);
    }
  };

  // Filtragem local baseada nas tags selecionadas no cabeçalho
  const filteredAvisos = avisos.filter(a => {
    if (selectedTagFilters.length === 0) return true;
    return a.tags.some(tag => selectedTagFilters.includes(tag.id));
  });

  const getPriorityStyle = (priority: 'normal' | 'important' | 'urgent') => {
    switch (priority) {
      case 'urgent':
        return {
          cardBorder: 'border-[#B32424] hover:border-[#D63030]',
          accent: 'border-l-[3px] border-l-[#B32424]',
          badge: 'bg-[#B32424]/10 border border-[#B32424]/20 text-red-500'
        };
      case 'important':
        return {
          cardBorder: 'border-primary/30 hover:border-primary/50',
          accent: 'border-l-[3px] border-l-primary',
          badge: 'bg-primary/10 border border-primary/20 text-primary'
        };
      default:
        return {
          cardBorder: 'border-border-custom hover:border-text-muted/30',
          accent: '',
          badge: 'bg-bg-dark border border-border-custom text-text-muted'
        };
    }
  };

  return (
    <div className="space-y-6 font-hanken text-text-main select-none pb-24">
      {/* 1. Hero / Header Bento Box */}
      <div className="relative overflow-hidden rounded-[4px] bg-surface border border-border-custom p-6 md:p-8">
        <div className="absolute -right-16 -top-16 w-64 h-64 bg-primary/5 rounded-full blur-3xl pointer-events-none" />
        <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div>
            <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-[4px] bg-primary/10 border border-primary/20 text-primary text-[10px] font-bold uppercase tracking-wider mb-3">
              📢 Mural da Liderança
            </span>
            <h1 className="text-2xl md:text-3xl font-extrabold tracking-tight text-text-main">
              Mural de <span className="text-primary font-black">Avisos</span>
            </h1>
            <p className="text-text-muted text-xs md:text-sm mt-1 max-w-xl leading-relaxed">
              Fique por dentro das datas, ensaios especiais, devocionais e recados importantes da nossa equipe de adoração.
            </p>
          </div>

          <div className="flex flex-wrap gap-2.5 shrink-0">
            {isAdmin && (
              <>
                <button 
                  onClick={() => setTagManagerOpen(true)}
                  className="h-11 px-4 rounded-[4px] bg-surface-variant/20 hover:bg-surface-variant/30 border border-border-custom text-text-main font-bold text-xs flex items-center gap-2 cursor-pointer transition-all active:scale-[0.98]"
                >
                  <TagIcon className="w-4 h-4 text-text-muted" />
                  Gerenciar Tags
                </button>
                
                <button 
                  onClick={openCreateForm}
                  className="h-11 px-5 rounded-[4px] bg-primary hover:bg-primary-hover text-bg-dark font-extrabold text-xs flex items-center gap-2 cursor-pointer transition-all active:scale-[0.98] shadow-xs"
                >
                  <Plus className="w-4 h-4 text-bg-dark" strokeWidth={3} />
                  Criar Comunicado
                </button>
              </>
            )}
          </div>
        </div>
      </div>

      {/* 2. Filtros e Abas Bento Box */}
      <div className="bg-surface border border-border-custom rounded-[4px] p-5 space-y-4">
        {/* Abas Principais (Ativos / Arquivados / Expirados) */}
        <div className="flex border-b border-border-custom pb-2 overflow-x-auto gap-4">
          <button
            onClick={() => setStatusFilter('active')}
            className={`pb-2.5 text-xs font-black uppercase tracking-wider transition-all border-b-2 cursor-pointer whitespace-nowrap ${
              statusFilter === 'active' 
                ? 'border-primary text-primary' 
                : 'border-transparent text-text-muted hover:text-text-main'
            }`}
          >
            🟢 Ativos ({avisos.filter(a => !a.archived_at).length})
          </button>
          
          {isAdmin && (
            <>
              <button
                onClick={() => setStatusFilter('archived')}
                className={`pb-2.5 text-xs font-black uppercase tracking-wider transition-all border-b-2 cursor-pointer whitespace-nowrap ${
                  statusFilter === 'archived' 
                    ? 'border-primary text-primary' 
                    : 'border-transparent text-text-muted hover:text-text-main'
                }`}
              >
                📁 Arquivados
              </button>

              <button
                onClick={() => setStatusFilter('expired')}
                className={`pb-2.5 text-xs font-black uppercase tracking-wider transition-all border-b-2 cursor-pointer whitespace-nowrap ${
                  statusFilter === 'expired' 
                    ? 'border-primary text-primary' 
                    : 'border-transparent text-text-muted hover:text-text-main'
                }`}
              >
                ⏳ Expirados
              </button>
            </>
          )}
        </div>

        {/* Inputs de busca e seletores */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          {/* Busca por texto */}
          <div className="relative md:col-span-2">
            <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted w-4 h-4" />
            <input
              type="text"
              placeholder="Buscar comunicados por título ou conteúdo..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full h-11 pl-11 pr-4 bg-bg-dark border border-border-custom rounded-[4px] text-sm focus:outline-none focus:border-primary text-text-main placeholder-text-muted/65 leading-relaxed"
            />
          </div>

          {/* Filtro por Assunto */}
          <div>
            <select
              value={typeFilter}
              onChange={(e) => setTypeFilter(e.target.value)}
              className="w-full h-11 px-3 bg-bg-dark border border-border-custom rounded-[4px] text-xs font-bold text-text-main focus:outline-none focus:border-primary cursor-pointer"
            >
              <option value="all">📢 Todos os Assuntos</option>
              <option value="geral">📢 Geral</option>
              <option value="espiritual">🙏 Espiritual</option>
              <option value="eventos">🎉 Eventos</option>
              <option value="musica">🎵 Música</option>
            </select>
          </div>

          {/* Filtro de Prioridade */}
          <div>
            <select
              value={priorityFilter}
              onChange={(e) => setPriorityFilter(e.target.value)}
              className="w-full h-11 px-3 bg-bg-dark border border-border-custom rounded-[4px] text-xs font-bold text-text-main focus:outline-none focus:border-primary cursor-pointer"
            >
              <option value="all">✨ Todas as Prioridades</option>
              <option value="normal">ℹ️ Normal</option>
              <option value="important">⭐ Importante</option>
              <option value="urgent">🚨 Urgente</option>
            </select>
          </div>
        </div>

        {/* Filtro por Tags (Dropdown de Checkboxes) */}
        {tags.length > 0 && (
          <div className="pt-2 border-t border-border-custom/50 flex flex-wrap items-center gap-2">
            <span className="text-[10px] font-black uppercase text-text-muted tracking-wider mr-1">Filtrar por Tags:</span>
            {tags.map(tag => {
              const active = selectedTagFilters.includes(tag.id);
              return (
                <button
                  key={tag.id}
                  onClick={() => handleToggleFilterTag(tag.id)}
                  className={`px-3 py-1.5 rounded-[4px] text-[9px] font-black uppercase tracking-wider border cursor-pointer transition-all ${
                    active 
                      ? 'text-white' 
                      : 'text-text-muted bg-bg-dark border-border-custom hover:text-text-main'
                  }`}
                  style={{ 
                    backgroundColor: active ? tag.color : undefined,
                    borderColor: active ? tag.color : undefined 
                  }}
                >
                  {tag.name}
                </button>
              );
            })}
            
            {selectedTagFilters.length > 0 && (
              <button 
                onClick={() => setSelectedTagFilters([])}
                className="text-[9px] font-black uppercase tracking-widest text-red-500 hover:underline px-2 py-1 ml-2 cursor-pointer"
              >
                Limpar Filtros
              </button>
            )}
          </div>
        )}
      </div>

      {/* 3. Lista de Comunicados */}
      {loading ? (
        <div className="min-h-[200px] flex items-center justify-center">
          <div className="w-8 h-8 rounded-full border-2 border-primary/20 border-t-primary animate-spin" />
        </div>
      ) : error ? (
        <div className="border border-red-500/20 bg-red-500/5 p-4 rounded-[4px] text-xs font-bold text-red-400 flex items-start gap-2 max-w-xl">
          <AlertTriangle className="w-4 h-4 shrink-0 mt-0.5" />
          <span>{error}</span>
        </div>
      ) : filteredAvisos.length === 0 ? (
        <div className="border border-dashed border-border-custom bg-surface/10 rounded-[4px] p-12 text-center">
          <Megaphone className="w-10 h-10 text-text-muted/40 mx-auto mb-3 animate-pulse" />
          <h3 className="text-sm font-extrabold text-text-main mb-1">Nenhum aviso no mural</h3>
          <p className="text-xs text-text-muted max-w-xs mx-auto leading-relaxed">
            Não há comunicados cadastrados para as configurações e filtros selecionados.
          </p>
        </div>
      ) : (
        <div className="space-y-4">
          {filteredAvisos.map(aviso => {
            const visual = getPriorityStyle(aviso.priority);
            const isPinned = !!aviso.is_pinned;
            
            return (
              <div
                key={aviso.id}
                className={`bg-surface rounded-[4px] p-5 md:p-6 shadow-sm border transition-all duration-200 relative overflow-hidden group ${visual.cardBorder} ${visual.accent}`}
              >
                {/* Header Card */}
                <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                  <div className="flex items-center gap-3">
                    {/* Avatar do Autor */}
                    <div className="w-10 h-10 rounded-[4px] border border-border-custom flex items-center justify-center shrink-0 bg-bg-dark font-extrabold text-sm overflow-hidden select-none">
                      {aviso.author_avatar && !aviso.author_avatar.includes('ui-avatars.com') ? (
                        <img src={aviso.author_avatar} alt={aviso.author_name} className="w-full h-full object-cover" />
                      ) : (
                        <div className="w-full h-full bg-primary/10 text-primary flex items-center justify-center uppercase font-black">
                          {aviso.author_name.charAt(0)}
                        </div>
                      )}
                    </div>

                    <div>
                      <div className="text-xs font-black text-text-main truncate max-w-[200px]" title={aviso.author_name}>
                        {aviso.author_name}
                      </div>
                      <div className="text-[9px] text-text-muted font-bold uppercase tracking-wider flex items-center gap-1.5 mt-0.5 leading-none">
                        <Clock className="w-2.5 h-2.5" />
                        <span>
                          {new Date(aviso.created_at).toLocaleDateString('pt-BR', {
                            day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
                          })}
                        </span>
                      </div>
                    </div>
                  </div>

                  {/* Badges / Ações de Admin */}
                  <div className="flex items-center flex-wrap gap-2 select-none">
                    {isPinned && (
                      <span className="inline-flex items-center gap-1 text-[8px] font-black uppercase tracking-widest bg-altar-gold/10 border border-altar-gold/25 text-altar-gold px-2 py-0.5 rounded-[2px] leading-none">
                        <Pin className="w-2.5 h-2.5 rotate-45" />
                        Fixado
                      </span>
                    )}

                    {aviso.priority !== 'normal' && (
                      <span className={`text-[8px] font-black uppercase tracking-widest px-2 py-0.5 rounded-[2px] leading-none ${visual.badge}`}>
                        {aviso.priority === 'urgent' ? '🚨 Urgente' : '⭐ Importante'}
                      </span>
                    )}

                    {aviso.expires_at && (
                      <span className="inline-flex items-center gap-1 text-[8px] font-black uppercase tracking-widest bg-amber-500/10 border border-amber-500/20 text-amber-500 px-2 py-0.5 rounded-[2px] leading-none">
                        <Calendar className="w-2.5 h-2.5" />
                        Expira em: {new Date(aviso.expires_at).toLocaleDateString('pt-BR')}
                      </span>
                    )}

                    {/* Ações Administrativas */}
                    {isAdmin && (
                      <div className="flex items-center gap-1.5 ml-2 border-l border-border-custom pl-2.5">
                        <button
                          onClick={() => handleTogglePin(aviso.id, isPinned)}
                          className={`w-7 h-7 rounded-[4px] border flex items-center justify-center transition-all cursor-pointer ${
                            isPinned 
                              ? 'bg-altar-gold/10 border-altar-gold/30 text-altar-gold' 
                              : 'bg-bg-dark border-border-custom text-text-muted hover:text-text-main'
                          }`}
                          title={isPinned ? 'Desfixar' : 'Fixar no Topo'}
                        >
                          <Pin className={`w-3.5 h-3.5 ${isPinned ? 'rotate-45' : ''}`} />
                        </button>

                        <button
                          onClick={() => handleViewStats(aviso)}
                          className="w-7 h-7 bg-bg-dark hover:bg-primary/10 border border-border-custom hover:border-primary/30 text-text-muted hover:text-primary rounded-[4px] flex items-center justify-center transition-all cursor-pointer"
                          title="Ver Alcance e Leitores"
                        >
                          <BarChart2 className="w-3.5 h-3.5" />
                        </button>

                        <button
                          onClick={() => openEditForm(aviso)}
                          className="w-7 h-7 bg-bg-dark hover:bg-altar-gold/10 border border-border-custom hover:border-altar-gold/30 text-text-muted hover:text-altar-gold rounded-[4px] flex items-center justify-center transition-all cursor-pointer"
                          title="Editar Comunicado"
                        >
                          <Edit2 className="w-3.5 h-3.5" />
                        </button>

                        <button
                          onClick={() => handleToggleArchive(aviso.id, statusFilter === 'archived')}
                          className="w-7 h-7 bg-bg-dark hover:bg-text-muted/10 border border-border-custom hover:border-text-muted/30 text-text-muted hover:text-text-main rounded-[4px] flex items-center justify-center transition-all cursor-pointer"
                          title={statusFilter === 'archived' ? 'Desarquivar' : 'Arquivar'}
                        >
                          <Archive className="w-3.5 h-3.5" />
                        </button>

                        <button
                          onClick={() => handleDeleteAviso(aviso.id)}
                          className="w-7 h-7 bg-bg-dark hover:bg-red-500/10 border border-border-custom hover:border-red-500/30 text-text-muted hover:text-red-500 rounded-[4px] flex items-center justify-center transition-all cursor-pointer"
                          title="Excluir Permanentemente"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    )}
                  </div>
                </div>

                {/* Corpo Card (Título & Mensagem HTML) */}
                <div className="mt-4 space-y-3">
                  <h2 className="text-base md:text-lg font-extrabold text-text-main leading-snug tracking-tight">
                    {aviso.title}
                  </h2>
                  <div 
                    className="text-text-muted text-xs md:text-sm font-sans font-medium leading-relaxed break-words prose prose-invert max-w-none 
                      [&_p]:mb-2 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_strong]:text-text-main [&_strong]:font-black"
                    dangerouslySetInnerHTML={{ __html: aviso.message }}
                  />
                </div>

                {/* Rodapé Card: Tags do Comunicado & Reações reativas */}
                <div className="mt-5 pt-4 border-t border-border-custom/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4 select-none">
                  {/* Tags do aviso */}
                  <div className="flex flex-wrap gap-1.5">
                    {aviso.tags.length > 0 ? (
                      aviso.tags.map(tag => (
                        <span
                          key={tag.id}
                          className="inline-flex items-center text-[9px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded-[2px] border"
                          style={{
                            backgroundColor: tag.color + '15',
                            borderColor: tag.color + '30',
                            color: tag.color
                          }}
                        >
                          #{tag.name}
                        </span>
                      ))
                    ) : (
                      <span className="text-[9px] font-bold text-text-muted/40 italic">#geral</span>
                    )}
                  </div>

                  {/* Reações (Curtir & Confirmar Leitura) */}
                  <div className="flex items-center gap-3">
                    {/* Botão de Curtir */}
                    <button
                      onClick={() => handleToggleReaction(aviso.id, 'like')}
                      className={`h-9 px-3.5 rounded-[4px] border text-xs font-bold flex items-center gap-2 cursor-pointer transition-all active:scale-[0.96] ${
                        aviso.user_reacted.like 
                          ? 'bg-primary/10 border-primary/20 text-primary' 
                          : 'bg-bg-dark border-border-custom text-text-muted hover:text-text-main hover:bg-surface-variant/20'
                      }`}
                    >
                      <Heart className={`w-3.5 h-3.5 ${aviso.user_reacted.like ? 'fill-current' : ''}`} />
                      <span>{aviso.reactions.like}</span>
                    </button>

                    {/* Botão de Confirmar Leitura */}
                    <button
                      onClick={() => handleToggleReaction(aviso.id, 'confirm')}
                      className={`h-9 px-3.5 rounded-[4px] border text-xs font-bold flex items-center gap-2 cursor-pointer transition-all active:scale-[0.96] ${
                        aviso.is_read 
                          ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-500' 
                          : 'bg-bg-dark border-border-custom text-text-muted hover:text-text-main hover:bg-surface-variant/20'
                      }`}
                    >
                      <CheckCircle2 className="w-3.5 h-3.5" />
                      <span>{aviso.is_read ? 'Lido' : 'Confirmar Leitura'}</span>
                      {aviso.reactions.confirm > 0 && (
                        <span className="ml-0.5 text-[9px] opacity-75">({aviso.reactions.confirm})</span>
                      )}
                    </button>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* ========================================================================= */}
      {/* DRAWER / MODAL: CRIAR OU EDITAR AVISO */}
      {/* ========================================================================= */}
      {drawerOpen && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
          <div className="bg-surface w-full max-w-xl rounded-[4px] border border-border-custom p-6 shadow-2xl animate-fade-in max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between pb-4 border-b border-border-custom mb-4">
              <h2 className="text-base font-extrabold text-text-main">
                {avisoId ? 'Editar Comunicado' : 'Criar Comunicado'}
              </h2>
              <button 
                onClick={() => setDrawerOpen(false)}
                className="p-1 rounded-[4px] border border-border-custom text-text-muted hover:text-text-main hover:bg-bg-dark cursor-pointer transition-colors"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <form onSubmit={handleSaveAviso} className="space-y-4 font-sans text-sm">
              {/* Título */}
              <div className="flex flex-col gap-1.5">
                <label className="text-[10px] font-black uppercase text-text-muted tracking-wider">Título do Comunicado</label>
                <input
                  type="text"
                  required
                  placeholder="Ex: Novo cronograma de ensaios..."
                  value={formTitle}
                  onChange={(e) => setFormTitle(e.target.value)}
                  className="w-full h-11 px-4 bg-bg-dark border border-border-custom rounded-[4px] text-sm text-text-main focus:outline-none focus:border-primary font-bold placeholder-text-muted/40"
                />
              </div>

              {/* Mensagem */}
              <div className="flex flex-col gap-1.5">
                <label className="text-[10px] font-black uppercase text-text-muted tracking-wider">Mensagem (HTML simples suportado)</label>
                <textarea
                  required
                  rows={6}
                  placeholder="Escreva as instruções, novidades ou devocionais aqui... Suporta quebras de linha normais ou tags HTML para negrito, listas, etc."
                  value={formMessage}
                  onChange={(e) => setFormMessage(e.target.value)}
                  className="w-full p-4 bg-bg-dark border border-border-custom rounded-[4px] text-xs md:text-sm text-text-main focus:outline-none focus:border-primary font-medium placeholder-text-muted/40 resize-y min-h-[120px]"
                />
              </div>

              {/* Grid: Prioridade, Assunto, Público */}
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div className="flex flex-col gap-1.5">
                  <label className="text-[10px] font-black uppercase text-text-muted tracking-wider">Prioridade</label>
                  <select
                    value={formPriority}
                    onChange={(e) => setFormPriority(e.target.value as any)}
                    className="w-full h-11 px-3 bg-bg-dark border border-border-custom rounded-[4px] text-xs font-bold text-text-main focus:outline-none focus:border-primary cursor-pointer"
                  >
                    <option value="normal">ℹ️ Normal</option>
                    <option value="important">⭐ Importante</option>
                    <option value="urgent">🚨 Urgente</option>
                  </select>
                </div>

                <div className="flex flex-col gap-1.5">
                  <label className="text-[10px] font-black uppercase text-text-muted tracking-wider">Assunto</label>
                  <select
                    value={formType}
                    onChange={(e) => setFormType(e.target.value)}
                    className="w-full h-11 px-3 bg-bg-dark border border-border-custom rounded-[4px] text-xs font-bold text-text-main focus:outline-none focus:border-primary cursor-pointer"
                  >
                    <option value="geral">📢 Geral</option>
                    <option value="espiritual">🙏 Espiritual</option>
                    <option value="eventos">🎉 Eventos</option>
                    <option value="musica">🎵 Música</option>
                  </select>
                </div>

                <div className="flex flex-col gap-1.5">
                  <label className="text-[10px] font-black uppercase text-text-muted tracking-wider">Público-Alvo</label>
                  <select
                    value={formAudience}
                    onChange={(e) => setFormAudience(e.target.value as any)}
                    className="w-full h-11 px-3 bg-bg-dark border border-border-custom rounded-[4px] text-xs font-bold text-text-main focus:outline-none focus:border-primary cursor-pointer"
                  >
                    <option value="all">👥 Todos</option>
                    <option value="team">🎸 Equipe</option>
                    <option value="admins">👑 Líderes</option>
                  </select>
                </div>
              </div>

              {/* Expiração */}
              <div className="flex flex-col gap-1.5">
                <label className="text-[10px] font-black uppercase text-text-muted tracking-wider">Expiração em (Opcional)</label>
                <input
                  type="date"
                  value={formExpiresAt}
                  onChange={(e) => setFormExpiresAt(e.target.value)}
                  className="w-full h-11 px-4 bg-bg-dark border border-border-custom rounded-[4px] text-xs font-bold text-text-main focus:outline-none focus:border-primary cursor-pointer"
                />
              </div>

              {/* Seleção de Tags */}
              {tags.length > 0 && (
                <div className="flex flex-col gap-1.5">
                  <label className="text-[10px] font-black uppercase text-text-muted tracking-wider">Tags do Comunicado</label>
                  <div className="flex flex-wrap gap-2 p-3 bg-bg-dark/50 border border-border-custom rounded-[4px]">
                    {tags.map(tag => {
                      const active = formSelectedTags.includes(tag.id);
                      return (
                        <button
                          key={tag.id}
                          type="button"
                          onClick={() => handleToggleFormTag(tag.id)}
                          className={`px-3 py-1.5 rounded-[4px] text-[9px] font-black uppercase tracking-wider border cursor-pointer transition-all ${
                            active
                              ? 'text-white'
                              : 'bg-bg-dark border-border-custom text-text-muted hover:text-text-main'
                          }`}
                          style={{
                            backgroundColor: active ? tag.color : undefined,
                            borderColor: active ? tag.color : undefined
                          }}
                        >
                          {tag.name}
                        </button>
                      );
                    })}
                  </div>
                </div>
              )}

              {/* Botões Form */}
              <div className="flex gap-3 pt-4 border-t border-border-custom mt-6">
                <button
                  type="button"
                  onClick={() => setDrawerOpen(false)}
                  className="flex-1 h-12 bg-bg-dark hover:bg-bg-dark/80 border border-border-custom text-text-muted hover:text-text-main font-bold text-xs rounded-[4px] cursor-pointer transition-all active:scale-[0.98]"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  className="flex-[2] h-12 bg-primary hover:bg-primary-hover text-bg-dark font-extrabold text-xs rounded-[4px] cursor-pointer transition-all active:scale-[0.98] shadow-xs"
                >
                  Publicar Aviso
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* DRAWER / MODAL: ESTATÍSTICAS DE LEITURA (ALCANCE) */}
      {/* ========================================================================= */}
      {statsDrawerOpen && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
          <div className="bg-surface w-full max-w-md rounded-[4px] border border-border-custom p-6 shadow-2xl animate-fade-in max-h-[85vh] overflow-y-auto">
            <div className="flex items-center justify-between pb-4 border-b border-border-custom mb-5">
              <h2 className="text-base font-extrabold text-text-main flex items-center gap-2">
                <BarChart2 className="w-4 h-4 text-primary" />
                Alcance do Comunicado
              </h2>
              <button 
                onClick={() => setStatsDrawerOpen(false)}
                className="p-1 rounded-[4px] border border-border-custom text-text-muted hover:text-text-main hover:bg-bg-dark cursor-pointer transition-colors"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {statsLoading ? (
              <div className="min-h-[200px] flex flex-col items-center justify-center gap-3">
                <div className="w-8 h-8 rounded-full border-2 border-primary/20 border-t-primary animate-spin" />
                <span className="text-[10px] font-black uppercase tracking-widest text-text-muted">Calculando alcance...</span>
              </div>
            ) : statsData ? (
              <div className="space-y-6 select-none font-hanken">
                
                {/* Cabeçalho do aviso selecionado */}
                <div className="bg-bg-dark p-3.5 border border-border-custom rounded-[4px]">
                  <h3 className="text-xs font-black text-text-main leading-tight truncate">
                    {selectedAvisoForStats?.title}
                  </h3>
                  <span className="text-[9px] font-bold text-text-muted uppercase tracking-wider block mt-1.5">
                    Público: {selectedAvisoForStats?.target_audience === 'all' ? 'Todos os membros' : selectedAvisoForStats?.target_audience === 'team' ? 'Equipe de Louvor' : 'Apenas Líderes'}
                  </span>
                </div>

                {/* SVG Radial Progress de Confirmações */}
                <div className="flex flex-col items-center justify-center p-4 bg-bg-dark/40 border border-border-custom rounded-[4px] relative">
                  <div className="relative w-28 h-28 flex items-center justify-center">
                    <svg className="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                      <circle className="text-bg-dark stroke-border-custom/45" strokeWidth="3" stroke="currentColor" fill="none" r="16" cx="18" cy="18" />
                      <circle className="text-primary stroke-current transition-all duration-700" strokeWidth="3.5" strokeDasharray={`${statsData.read_percentage}, 100`} strokeLinecap="round" fill="none" r="16" cx="18" cy="18" />
                    </svg>
                    <div className="absolute flex flex-col items-center justify-center leading-none">
                      <span className="text-2xl font-black text-text-main">{statsData.read_percentage}%</span>
                      <span className="text-[8px] font-black uppercase text-text-muted mt-1 tracking-widest">Leitores</span>
                    </div>
                  </div>
                  
                  <div className="text-center mt-4">
                    <div className="text-xs font-extrabold text-text-main">
                      {statsData.read_count} de {statsData.total_users} voluntários
                    </div>
                    <span className="text-[9px] font-bold text-text-muted uppercase tracking-wider block mt-0.5">
                      confirmaram a leitura deste comunicado
                    </span>
                  </div>
                </div>

                {/* Leitores Detalhados */}
                <div className="space-y-2.5">
                  <h4 className="text-[10px] font-black uppercase text-text-muted tracking-widest pl-1">
                    Histórico de Leituras ({statsData.readers.length})
                  </h4>

                  {statsData.readers.length === 0 ? (
                    <div className="p-5 border border-dashed border-border-custom bg-surface/10 text-center rounded-[4px]">
                      <span className="text-xs font-semibold text-text-muted italic block">
                        Nenhum membro leu ainda.
                      </span>
                    </div>
                  ) : (
                    <div className="max-h-[220px] overflow-y-auto pr-1 space-y-1.5 divide-y divide-border-custom/50">
                      {statsData.readers.map((reader, idx) => (
                        <div key={idx} className="flex items-center justify-between py-2 text-xs font-bold select-none">
                          <span className="text-text-main font-extrabold truncate max-w-[200px]" title={reader.name}>
                            {reader.name}
                          </span>
                          <span className="text-[9px] text-text-muted font-bold whitespace-nowrap">
                            {new Date(reader.read_at).toLocaleDateString('pt-BR')} às {new Date(reader.read_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}
                          </span>
                        </div>
                      ))}
                    </div>
                  )}
                </div>

                {/* Botão de Fechar */}
                <button
                  onClick={() => setStatsDrawerOpen(false)}
                  className="w-full h-11 bg-bg-dark hover:bg-bg-dark/80 border border-border-custom text-text-muted hover:text-text-main font-bold text-xs rounded-[4px] cursor-pointer transition-all active:scale-[0.98]"
                >
                  Fechar Estatísticas
                </button>
              </div>
            ) : (
              <div className="text-center py-6 text-xs text-text-muted">
                Falha ao extrair estatísticas de leitura.
              </div>
            )}
          </div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* DRAWER / MODAL: GERENCIADOR DE TAGS */}
      {/* ========================================================================= */}
      {tagManagerOpen && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
          <div className="bg-surface w-full max-w-md rounded-[4px] border border-border-custom p-6 shadow-2xl animate-fade-in max-h-[85vh] overflow-y-auto">
            <div className="flex items-center justify-between pb-4 border-b border-border-custom mb-4">
              <h2 className="text-base font-extrabold text-text-main flex items-center gap-2">
                <TagIcon className="w-4 h-4 text-primary" />
                Gerenciar Tags de Avisos
              </h2>
              <button 
                onClick={() => setTagManagerOpen(false)}
                className="p-1 rounded-[4px] border border-border-custom text-text-muted hover:text-text-main hover:bg-bg-dark cursor-pointer transition-colors"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {/* Listagem de Tags Cadastradas */}
            <div className="space-y-2.5 max-h-[280px] overflow-y-auto pr-1">
              <span className="text-[10px] font-black uppercase text-text-muted tracking-widest block pl-1">Tags Ativas</span>
              {tags.length === 0 ? (
                <div className="text-center py-6 text-xs text-text-muted italic bg-bg-dark border border-border-custom rounded-[4px]">
                  Nenhuma tag cadastrada.
                </div>
              ) : (
                tags.map(tag => (
                  <div 
                    key={tag.id} 
                    className="flex items-center justify-between p-3 bg-bg-dark border border-border-custom rounded-[4px] hover:border-text-muted/30 transition-all select-none"
                  >
                    <div className="flex items-center gap-3">
                      <div className="w-3.5 h-3.5 rounded-[2px] border border-white/5 shadow-xs" style={{ backgroundColor: tag.color }} />
                      <div>
                        <span className="text-xs font-black text-text-main">{tag.name}</span>
                        <span className="text-[8px] font-bold text-text-muted uppercase tracking-widest block mt-0.5">
                          {tag.is_default ? 'Padrão do Sistema' : 'Criada por Líder'}
                        </span>
                      </div>
                    </div>

                    {!tag.is_default && (
                      <button
                        onClick={() => handleDeleteTag(tag.id)}
                        className="w-7 h-7 rounded-[4px] border border-border-custom bg-surface hover:bg-red-500/10 hover:border-red-500/30 text-text-muted hover:text-red-500 flex items-center justify-center transition-all cursor-pointer"
                        title="Deletar Tag"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    )}
                  </div>
                ))
              )}
            </div>

            {/* Form de Criação de Novas Tags */}
            <form onSubmit={handleCreateTag} className="pt-4 border-t border-border-custom mt-5 space-y-4">
              <span className="text-[10px] font-black uppercase text-text-muted tracking-widest block pl-1">Criar Nova Tag</span>
              
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div className="sm:col-span-2 flex flex-col gap-1.5">
                  <label className="text-[9px] font-bold uppercase text-text-muted">Nome da Tag</label>
                  <input
                    type="text"
                    required
                    placeholder="Ex: Ensaio Geral"
                    value={newTagName}
                    onChange={(e) => setNewTagName(e.target.value)}
                    className="w-full h-10 px-3 bg-bg-dark border border-border-custom rounded-[4px] text-xs text-text-main focus:outline-none focus:border-primary font-bold placeholder-text-muted/40"
                  />
                </div>

                <div className="flex flex-col gap-1.5">
                  <label className="text-[9px] font-bold uppercase text-text-muted">Cor</label>
                  <div className="flex items-center gap-1.5 bg-bg-dark border border-border-custom rounded-[4px] h-10 px-2 select-none relative">
                    <input
                      type="color"
                      value={newTagColor}
                      onChange={(e) => setNewTagColor(e.target.value)}
                      className="w-7 h-7 bg-transparent border-0 rounded-[4px] cursor-pointer"
                    />
                    <span className="text-[10px] font-extrabold uppercase text-text-main font-mono shrink-0 select-all cursor-pointer">
                      {newTagColor}
                    </span>
                  </div>
                </div>
              </div>

              <button
                type="submit"
                className="w-full h-11 bg-primary hover:bg-primary-hover text-bg-dark font-extrabold text-xs rounded-[4px] flex items-center justify-center gap-1.5 cursor-pointer transition-all active:scale-[0.98]"
              >
                <Plus className="w-4 h-4 text-bg-dark" strokeWidth={3} />
                Adicionar Tag
              </button>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
