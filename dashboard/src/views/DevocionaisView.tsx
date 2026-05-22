import React, { useEffect, useState } from 'react';
import { 
  BookOpen, 
  Heart, 
  Search, 
  MessageSquare, 
  ExternalLink, 
  Send, 
  X, 
  AlertTriangle,
  Bookmark,
  Plus
} from 'lucide-react';

interface DevotionalTag {
  id: number;
  name: string;
  color: string;
}

interface Devotional {
  id: number;
  title: string;
  content: string;
  media_type: 'none' | 'video' | 'link';
  media_url: string | null;
  created_at: string;
  user_id: number;
  author_name: string;
  author_avatar: string;
  series_title: string | null;
  series_color: string | null;
  comment_count: number;
  is_read: boolean;
  tags: DevotionalTag[];
}

interface PrayerRequest {
  id: number;
  user_id: number;
  title: string;
  description: string;
  category: 'health' | 'family' | 'work' | 'spiritual' | 'gratitude' | 'other';
  is_urgent: boolean;
  is_anonymous: boolean;
  is_answered: boolean;
  created_at: string;
  author_name: string | null;
  author_avatar: string | null;
  pray_count: number;
  comment_count: number;
  is_interceded: boolean;
}

interface Comment {
  id: number;
  devotional_id: number;
  user_id: number;
  comment: string;
  created_at: string;
  author_name: string;
  author_avatar: string;
}

export const DevocionaisView: React.FC = () => {
  const [activeTab, setActiveTab] = useState<'word' | 'prayer'>('word');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Gerar gradiente estético baseado no nome para avatares sem foto
  const getAvatarGradient = (name: string) => {
    const charCodeSum = name.split('').reduce((sum, char) => sum + char.charCodeAt(0), 0);
    const gradients = [
      'from-primary to-blue-400',
      'from-emerald-500 to-teal-400',
      'from-amber-500 to-orange-400',
      'from-rose-500 to-pink-400',
      'from-slate-700 to-slate-500',
    ];
    return gradients[charCodeSum % gradients.length];
  };

  // Estados dos Devocionais (Palavra)
  const [devotionals, setDevotionals] = useState<Devotional[]>([]);
  const [tags, setTags] = useState<{ id: number; name: string }[]>([]);
  const [authors, setAuthors] = useState<{ id: number; name: string }[]>([]);
  
  // Filtros dos devocionais
  const [search, setSearch] = useState('');
  const [selectedTag, setSelectedTag] = useState('');
  const [selectedAuthor, setSelectedAuthor] = useState('');
  const [selectedRead, setSelectedRead] = useState('all');

  // Estados das Orações
  const [prayers, setPrayers] = useState<PrayerRequest[]>([]);
  const [prayersLoading, setPrayersLoading] = useState(false);

  // Detalhe e Comentários do Devocional selecionado
  const [expandedCommentsDevId, setExpandedCommentsDevId] = useState<number | null>(null);
  const [comments, setComments] = useState<Comment[]>([]);
  const [commentsLoading, setCommentsLoading] = useState(false);
  const [newCommentText, setNewCommentText] = useState('');
  const [submittingComment, setSubmittingComment] = useState(false);

  // Ações rápidas de modais
  const [showCreatePrayerModal, setShowCreatePrayerModal] = useState(false);
  const [newPrayerTitle, setNewPrayerTitle] = useState('');
  const [newPrayerDesc, setNewPrayerDesc] = useState('');
  const [newPrayerCat, setNewPrayerCat] = useState<'health' | 'family' | 'work' | 'spiritual' | 'gratitude' | 'other'>('spiritual');
  const [newPrayerUrgent, setNewPrayerUrgent] = useState(false);
  const [newPrayerAnon, setNewPrayerAnon] = useState(false);
  const [creatingPrayer, setCreatingPrayer] = useState(false);

  const fetchDevotionals = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const searchParam = search ? `&search=${encodeURIComponent(search)}` : '';
      const tagParam = selectedTag ? `&tag=${selectedTag}` : '';
      const authorParam = selectedAuthor ? `&author=${selectedAuthor}` : '';
      const readParam = selectedRead !== 'all' ? `&read_status=${selectedRead}` : '';
      
      const response = await fetch(`../api/admin/devocionais_api.php?nocache=true${searchParam}${tagParam}${authorParam}${readParam}`);
      const result = await response.json();
      
      if (result.success) {
        setDevotionals(result.data.devotionals || []);
        setTags(result.data.tags || []);
        setAuthors(result.data.authors || []);
      } else {
        setError(result.error || 'Erro ao carregar devocionais');
      }
    } catch (err) {
      setError('Erro de conexão com o servidor');
    } finally {
      setLoading(false);
    }
  };

  const fetchPrayers = async () => {
    try {
      setPrayersLoading(true);
      const response = await fetch('../api/admin/devocionais_api.php?action=prayers');
      const result = await response.json();
      if (result.success) {
        setPrayers(result.data || []);
      }
    } catch (err) {
      console.error('Erro ao carregar orações:', err);
    } finally {
      setPrayersLoading(false);
    }
  };

  useEffect(() => {
    if (activeTab === 'word') {
      fetchDevotionals();
    } else {
      fetchPrayers();
    }
  }, [activeTab, search, selectedTag, selectedAuthor, selectedRead]);

  // Carrega comentários ao expandir um devocional
  useEffect(() => {
    if (expandedCommentsDevId !== null) {
      loadComments(expandedCommentsDevId);
    }
  }, [expandedCommentsDevId]);

  const loadComments = async (devotionalId: number) => {
    try {
      setCommentsLoading(true);
      const response = await fetch(`../api/admin/devocionais_api.php?action=devotional_detail&id=${devotionalId}`);
      const result = await response.json();
      if (result.success) {
        setComments(result.data.comments || []);
      }
    } catch (err) {
      console.error('Erro ao buscar comentários:', err);
    } finally {
      setCommentsLoading(false);
    }
  };

  // Marcar como lido
  const handleMarkRead = async (devotionalId: number) => {
    try {
      // Chamada à API legada principal
      const response = await fetch('../api/mark_devotional_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ devotional_id: devotionalId })
      });
      const result = await response.json();
      
      if (result.success) {
        setDevotionals(prev => prev.map(d => {
          if (d.id === devotionalId) {
            return { ...d, is_read: true };
          }
          return d;
        }));
      }
    } catch (err) {
      // Fallback local caso o endpoint falhe
      setDevotionals(prev => prev.map(d => {
        if (d.id === devotionalId) {
          return { ...d, is_read: true };
        }
        return d;
      }));
    }
  };

  // Interceder em pedido de oração
  const handleIntercede = async (prayerId: number) => {
    try {
      // Chamada à API legada principal
      const response = await fetch('../api/toggle_intercession.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ prayer_id: prayerId })
      });
      const result = await response.json();
      
      if (result.success) {
        setPrayers(prev => prev.map(p => {
          if (p.id === prayerId) {
            const nowInterceded = !p.is_interceded;
            return { 
              ...p, 
              is_interceded: nowInterceded,
              pray_count: nowInterceded ? p.pray_count + 1 : Math.max(0, p.pray_count - 1)
            };
          }
          return p;
        }));
      }
    } catch (err) {
      // Fallback local se falhar conexão
      setPrayers(prev => prev.map(p => {
        if (p.id === prayerId) {
          const nowInterceded = !p.is_interceded;
          return { 
            ...p, 
            is_interceded: nowInterceded,
            pray_count: nowInterceded ? p.pray_count + 1 : Math.max(0, p.pray_count - 1)
          };
        }
        return p;
      }));
    }
  };

  // Criar novo comentário
  const handleAddComment = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newCommentText.trim() || expandedCommentsDevId === null) return;

    try {
      setSubmittingComment(true);
      // O projeto legado salva comentários via POST direto no admin/devocionais.php ou em um helper
      // Vamos emular um formulário clássico via URLSearchParams
      await fetch('../admin/devocionais.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          csrf_token: (window as any).csrfToken || '', // se houver no escopo global
          action: 'comment',
          devotional_id: String(expandedCommentsDevId),
          comment: newCommentText
        })
      });
      
      // Como o redirect retornaria HTML, chamamos a recarga de comentários
      loadComments(expandedCommentsDevId);
      setNewCommentText('');
      
      // Atualizar contador localmente no card principal
      setDevotionals(prev => prev.map(d => {
        if (d.id === expandedCommentsDevId) {
          return { ...d, comment_count: d.comment_count + 1 };
        }
        return d;
      }));
    } catch (err) {
      loadComments(expandedCommentsDevId);
      setNewCommentText('');
    } finally {
      setSubmittingComment(false);
    }
  };

  // Criar Pedido de Oração
  const handleCreatePrayer = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newPrayerTitle.trim() || !newPrayerDesc.trim()) return;

    try {
      setCreatingPrayer(true);
      // Criar chamada post para o admin legado que lida com isso
      await fetch('../admin/devocionais.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          csrf_token: (window as any).csrfToken || '',
          action: 'create_prayer',
          title: newPrayerTitle,
          description: newPrayerDesc,
          category: newPrayerCat,
          is_urgent: newPrayerUrgent ? '1' : '0',
          is_anonymous: newPrayerAnon ? '1' : '0'
        })
      });

      // Recarregar lista e fechar modal
      fetchPrayers();
      setShowCreatePrayerModal(false);
      setNewPrayerTitle('');
      setNewPrayerDesc('');
      setNewPrayerUrgent(false);
      setNewPrayerAnon(false);
    } catch (err) {
      fetchPrayers();
      setShowCreatePrayerModal(false);
    } finally {
      setCreatingPrayer(false);
    }
  };

  const categoryTranslations: Record<string, string> = {
    health: 'Saúde',
    family: 'Família',
    work: 'Trabalho',
    spiritual: 'Espiritual',
    gratitude: 'Gratidão',
    other: 'Outros'
  };

  return (
    <div className="space-y-6 font-hanken">
      {/* Hero / Header Bento Box */}
      <div className="relative overflow-hidden rounded-[4px] bg-surface border border-border-custom p-6 md:p-8">
        <div className="absolute -right-16 -top-16 w-64 h-64 bg-primary/5 rounded-full blur-3xl pointer-events-none" />
        <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div>
            <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-[4px] bg-primary/10 border border-primary/20 text-primary text-[10px] font-bold uppercase tracking-wider mb-3">
              📖 Vida Espiritual
            </span>
            <h1 className="text-2xl md:text-3xl font-extrabold tracking-tight text-text-main">
              Espiritualidade <span className="text-primary font-black">& Oração</span>
            </h1>
            <p className="text-text-muted text-xs md:text-sm mt-1 max-w-xl leading-relaxed">
              Nutra sua fé diária com as reflexões bíblicas da equipe e interceda em comunidade nos pedidos de oração.
            </p>
          </div>

          <div className="flex gap-1 bg-bg-dark p-1 rounded-[4px] border border-border-custom w-fit shrink-0 select-none">
            <button 
              onClick={() => setActiveTab('word')}
              className={`px-4 py-2 rounded-[4px] text-xs font-bold transition-all cursor-pointer ${
                activeTab === 'word' 
                  ? 'bg-surface text-primary border border-border-custom shadow-xs' 
                  : 'text-text-muted hover:text-text-main'
              }`}
            >
              📖 Palavra
            </button>
            <button 
              onClick={() => setActiveTab('prayer')}
              className={`px-4 py-2 rounded-[4px] text-xs font-bold transition-all cursor-pointer ${
                activeTab === 'prayer' 
                  ? 'bg-surface text-primary border border-border-custom shadow-xs' 
                  : 'text-text-muted hover:text-text-main'
              }`}
            >
              🙏 Oração
            </button>
          </div>
        </div>
      </div>

      {/* Conteúdo Aba Palavra (Devocionais) */}
      {activeTab === 'word' && (
        <div className="space-y-6">
          {/* Caixa Bento de Busca e Filtros */}
          <div className="bg-surface border border-border-custom rounded-[4px] p-5 space-y-4 select-none">
            {/* Campo de Busca */}
            <div className="relative w-full">
              <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted w-5 h-5" />
              <input 
                type="text" 
                placeholder="Buscar devocional por título ou conteúdo..." 
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="w-full h-11 pl-12 pr-4 bg-bg-dark border border-border-custom rounded-[4px] text-sm focus:outline-none focus:border-primary text-text-main placeholder-text-muted/65 leading-relaxed"
              />
            </div>

            {/* Filtros em Linha */}
            <div className="flex flex-wrap items-center gap-3">
              <span className="text-[10px] font-black text-text-muted uppercase tracking-wider pl-1">Filtrar:</span>
              
              {/* Lidas / Não Lidas */}
              <div className="flex gap-1 p-0.5 bg-bg-dark rounded-[4px] border border-border-custom h-8 shrink-0">
                <button
                  onClick={() => setSelectedRead('all')}
                  className={`px-3 py-1 rounded-[2px] text-[10px] font-bold cursor-pointer ${selectedRead === 'all' ? 'bg-surface text-primary border border-border-custom' : 'text-text-muted hover:text-text-main'}`}
                >
                  Todas
                </button>
                <button
                  onClick={() => setSelectedRead('unread')}
                  className={`px-3 py-1 rounded-[2px] text-[10px] font-bold cursor-pointer ${selectedRead === 'unread' ? 'bg-surface text-primary border border-border-custom' : 'text-text-muted hover:text-text-main'}`}
                >
                  Não Lidas
                </button>
                <button
                  onClick={() => setSelectedRead('read')}
                  className={`px-3 py-1 rounded-[2px] text-[10px] font-bold cursor-pointer ${selectedRead === 'read' ? 'bg-surface text-primary border border-border-custom' : 'text-text-muted hover:text-text-main'}`}
                >
                  Lidas
                </button>
              </div>

              {/* Autor */}
              <select 
                value={selectedAuthor}
                onChange={(e) => setSelectedAuthor(e.target.value)}
                className="h-8 pl-3 pr-8 rounded-[4px] bg-bg-dark border border-border-custom text-xs font-bold text-text-main focus:outline-none focus:border-primary cursor-pointer max-w-[170px]"
              >
                <option value="">👤 Todos Autores</option>
                {authors.map(a => (
                  <option key={a.id} value={a.id}>{a.name}</option>
                ))}
              </select>

              {/* Tag */}
              <select 
                value={selectedTag}
                onChange={(e) => setSelectedTag(e.target.value)}
                className="h-8 pl-3 pr-8 rounded-[4px] bg-bg-dark border border-border-custom text-xs font-bold text-text-main focus:outline-none focus:border-primary cursor-pointer max-w-[150px]"
              >
                <option value="">🏷️ Todas Tags</option>
                {tags.map(t => (
                  <option key={t.id} value={t.id}>#{t.name}</option>
                ))}
              </select>

              {/* Limpar Filtros */}
              {(search || selectedTag || selectedAuthor || selectedRead !== 'all') && (
                <button
                  onClick={() => {
                    setSearch('');
                    setSelectedTag('');
                    setSelectedAuthor('');
                    setSelectedRead('all');
                  }}
                  className="h-8 px-3.5 rounded-[4px] text-xs font-bold bg-red-500/5 hover:bg-red-500/10 border border-red-500/10 hover:border-red-500/20 text-red-500 cursor-pointer transition-colors"
                >
                  Limpar Filtros
                </button>
              )}
            </div>
          </div>

          {/* Feed de Devocionais */}
          {loading ? (
            <div className="min-h-[200px] flex items-center justify-center">
              <div className="w-8 h-8 rounded-full border-2 border-primary/20 border-t-primary animate-spin" />
            </div>
          ) : error ? (
            <div className="border border-red-500/20 bg-red-500/5 p-4 rounded-[4px] text-xs font-bold text-red-400 flex items-start gap-2 max-w-xl">
              <AlertTriangle className="w-4 h-4 shrink-0 mt-0.5" />
              <span>{error}</span>
            </div>
          ) : devotionals.length === 0 ? (
            <div className="border border-dashed border-border-custom bg-surface/10 rounded-[4px] p-12 text-center">
              <BookOpen className="w-10 h-10 text-text-muted/40 mx-auto mb-3" />
              <h3 className="text-sm font-extrabold text-text-main mb-1">Nenhum devocional postado</h3>
              <p className="text-xs text-text-muted max-w-xs mx-auto leading-relaxed">
                Compartilhe reflexões bíblicas da palavra de Deus com toda a equipe do louvor.
              </p>
            </div>
          ) : (
            <div className="space-y-4">
              {devotionals.map((dev) => (
                <div 
                  key={dev.id}
                  className={`bg-surface border rounded-[4px] p-6 space-y-4 transition-all duration-200 ${
                    dev.is_read
                      ? 'border-border-custom opacity-90'
                      : 'border-l-2 border-l-primary border-border-custom'
                  }`}
                >
                  {/* Cabeçalho Card */}
                  <div className="flex items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                      {/* Avatar */}
                      <div className="w-9 h-9 rounded-[4px] border border-border-custom flex items-center justify-center shrink-0 overflow-hidden relative bg-bg-dark">
                        {dev.author_avatar && !dev.author_avatar.includes('ui-avatars.com') ? (
                          <img src={dev.author_avatar} alt={dev.author_name} className="w-full h-full object-cover" />
                        ) : (
                          <div className={`absolute inset-0 bg-gradient-to-br ${getAvatarGradient(dev.author_name)} flex items-center justify-center text-white font-extrabold text-sm`}>
                            {dev.author_name.charAt(0).toUpperCase()}
                          </div>
                        )}
                      </div>

                      <div>
                        <h4 className="text-xs font-black text-text-main flex items-center flex-wrap gap-1.5 leading-tight">
                          {dev.author_name}
                          {dev.series_title && (
                            <span 
                              className="inline-flex items-center px-1.5 py-0.5 rounded-[2px] text-[8px] font-black uppercase tracking-wider bg-primary/10 border border-primary/20 text-primary"
                              style={{ 
                                color: dev.series_color || '#2e7eed',
                                backgroundColor: dev.series_color ? `${dev.series_color}10` : '#2e7eed10',
                                border: `1px solid ${dev.series_color ? `${dev.series_color}25` : '#2e7eed25'}`
                              }}
                            >
                              📚 {dev.series_title}
                            </span>
                          )}
                        </h4>
                        
                        <div className="flex items-center gap-1.5 text-[9px] text-text-muted font-bold uppercase tracking-wider mt-1 block">
                          <span>{new Date(dev.created_at).toLocaleDateString('pt-BR')} às {new Date(dev.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}</span>
                          {!dev.is_read && (
                            <>
                              <span>•</span>
                              <span className="text-primary font-black">Novo</span>
                            </>
                          )}
                        </div>
                      </div>
                    </div>

                    {/* Botão de Marcar como Lido */}
                    {!dev.is_read && (
                      <button 
                        onClick={() => handleMarkRead(dev.id)}
                        className="h-7 px-2.5 rounded-[4px] bg-primary/10 hover:bg-primary/20 border border-primary/20 hover:border-primary/30 text-primary text-[10px] font-bold uppercase tracking-wider flex items-center gap-1 cursor-pointer transition-all active:scale-95"
                        title="Marcar como Lido"
                      >
                        <Bookmark className="w-3.5 h-3.5" /> Lido
                      </button>
                    )}
                  </div>

                  {/* Título e Texto (Leitura sublime) */}
                  <div className="space-y-2 select-text">
                    <h2 className="text-base font-black text-text-main leading-snug">
                      {dev.title}
                    </h2>
                    
                    {/* Renderização de HTML higienizado do Quill */}
                    <div 
                      className="text-text-muted text-xs leading-relaxed font-medium font-body select-text pt-1 border-t border-transparent space-y-2"
                      dangerouslySetInnerHTML={{ __html: dev.content }}
                    />

                    {/* Media Embeds */}
                    {dev.media_type === 'video' && dev.media_url && (
                      <div className="aspect-video w-full rounded-[4px] overflow-hidden border border-border-custom bg-bg-dark mt-4 select-none">
                        <iframe 
                          src={dev.media_url.replace('watch?v=', 'embed/')} 
                          className="w-full h-full border-0" 
                          allowFullScreen
                          title="Youtube Media"
                        />
                      </div>
                    )}
                    
                    {dev.media_type === 'link' && dev.media_url && (
                      <div className="pt-2 select-none">
                        <a 
                          href={dev.media_url} 
                          target="_blank" 
                          rel="noopener noreferrer" 
                          className="flex items-center gap-3 p-3 bg-bg-dark border border-border-custom rounded-[4px] hover:border-primary/25 transition-all text-text-main"
                        >
                          <div className="w-8 h-8 rounded-[4px] bg-surface text-primary border border-border-custom flex items-center justify-center shrink-0">
                            <ExternalLink className="w-4 h-4" />
                          </div>
                          <div className="min-w-0">
                            <div className="text-[8px] font-black text-text-muted uppercase tracking-wider">Link Anexo</div>
                            <div className="text-[10px] font-bold truncate font-body mt-0.5 text-primary">{dev.media_url}</div>
                          </div>
                        </a>
                      </div>
                    )}
                  </div>

                  {/* Tags */}
                  {dev.tags && dev.tags.length > 0 && (
                    <div className="flex flex-wrap gap-1.5 pt-1 select-none">
                      {dev.tags.map((tag) => (
                        <span 
                          key={tag.id}
                          className="inline-flex items-center px-2 py-0.5 rounded-[2px] bg-bg-dark border border-border-custom text-text-muted text-[9px] font-bold"
                        >
                          #{tag.name}
                        </span>
                      ))}
                    </div>
                  )}

                  {/* Ações (Comentários / Compartilhar) */}
                  <div className="flex items-center gap-3 pt-3 border-t border-border-custom select-none">
                    <button 
                      onClick={() => setExpandedCommentsDevId(expandedCommentsDevId === dev.id ? null : dev.id)}
                      className={`inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider px-3 py-2 rounded-[4px] transition-all cursor-pointer border ${
                        expandedCommentsDevId === dev.id
                          ? 'bg-primary/10 border-primary/30 text-primary'
                          : 'bg-bg-dark border-border-custom text-text-muted hover:text-text-main'
                      }`}
                    >
                      <MessageSquare className="w-3.5 h-3.5" />
                      <span>Comentários ({dev.comment_count})</span>
                    </button>
                  </div>

                  {/* Seção de Comentários Expandida */}
                  {expandedCommentsDevId === dev.id && (
                    <div className="pt-4 border-t border-border-custom space-y-3">
                      <div className="space-y-2.5 max-h-[220px] overflow-y-auto pr-1 select-text">
                        {commentsLoading ? (
                          <div className="py-4 text-center">
                            <div className="w-5 h-5 rounded-full border-2 border-primary/20 border-t-primary animate-spin mx-auto" />
                          </div>
                        ) : comments.length === 0 ? (
                          <p className="text-[11px] italic text-text-muted pl-1 py-1">Nenhum comentário nesta postagem. Seja o primeiro a comentar!</p>
                        ) : (
                          comments.map((c) => (
                            <div key={c.id} className="flex gap-2.5 bg-bg-dark/40 border border-border-custom p-3 rounded-[4px] select-text">
                              <div className="w-7 h-7 rounded-[4px] border border-border-custom flex items-center justify-center shrink-0 overflow-hidden relative bg-bg-dark select-none">
                                {c.author_avatar && !c.author_avatar.includes('ui-avatars.com') ? (
                                  <img src={c.author_avatar} alt={c.author_name} className="w-full h-full object-cover" />
                                ) : (
                                  <div className={`absolute inset-0 bg-gradient-to-br ${getAvatarGradient(c.author_name)} flex items-center justify-center text-white font-extrabold text-xs`}>
                                    {c.author_name.charAt(0).toUpperCase()}
                                  </div>
                                )}
                              </div>
                              
                              <div className="min-w-0 flex-1">
                                <div className="flex items-center justify-between gap-4">
                                  <span className="text-[10px] font-extrabold text-text-main truncate leading-none">{c.author_name}</span>
                                  <span className="text-[8px] text-text-muted font-bold shrink-0 leading-none">
                                    {new Date(c.created_at).toLocaleDateString('pt-BR')}
                                  </span>
                                </div>
                                <p className="text-[11px] text-text-muted leading-relaxed font-body mt-1.5 whitespace-pre-line font-medium select-text">
                                  {c.comment}
                                </p>
                              </div>
                            </div>
                          ))
                        )}
                      </div>

                      {/* Enviar Comentário */}
                      <form onSubmit={handleAddComment} className="flex gap-2 pt-1 select-text">
                        <input 
                          type="text" 
                          placeholder="Escreva uma mensagem..." 
                          value={newCommentText}
                          onChange={(e) => setNewCommentText(e.target.value)}
                          className="flex-1 h-9 px-3 rounded-[4px] bg-bg-dark border border-border-custom text-xs focus:outline-none focus:border-primary text-text-main placeholder-text-muted/65 leading-relaxed"
                          required
                        />
                        <button 
                          type="submit" 
                          disabled={submittingComment || !newCommentText.trim()}
                          className="w-9 h-9 bg-primary hover:bg-primary/95 text-bg-dark rounded-[4px] flex items-center justify-center shrink-0 disabled:opacity-50 transition-all cursor-pointer"
                        >
                          <Send className="w-3.5 h-3.5" />
                        </button>
                      </form>
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Conteúdo Aba Oração (Pedidos de Oração) */}
      {activeTab === 'prayer' && (
        <div className="space-y-6">
          <div className="flex items-center justify-between select-none">
            <span className="text-[10px] font-black text-text-muted uppercase tracking-widest pl-0.5">
              Mural Contemplativo de Oração
            </span>
            <button
              onClick={() => setShowCreatePrayerModal(true)}
              className="h-8 px-3.5 rounded-[4px] bg-primary text-bg-dark text-xs font-black flex items-center gap-1 cursor-pointer transition-all active:scale-95 shadow-sm"
            >
              <Plus className="w-3.5 h-3.5 text-bg-dark" strokeWidth={3} /> Criar Pedido
            </button>
          </div>

          {/* Grid de Pedidos */}
          {prayersLoading ? (
            <div className="min-h-[200px] flex items-center justify-center">
              <div className="w-8 h-8 rounded-full border-2 border-primary/20 border-t-primary animate-spin" />
            </div>
          ) : prayers.length === 0 ? (
            <div className="border border-dashed border-border-custom bg-surface/10 rounded-[4px] p-12 text-center select-none">
              <Heart className="w-10 h-10 text-text-muted/40 mx-auto mb-3" />
              <h3 className="text-sm font-extrabold text-text-main mb-1">Mural de orações vazio</h3>
              <p className="text-xs text-text-muted max-w-xs mx-auto leading-relaxed">
                Não há pedidos ativos de intercessão neste momento. Crie um novo pedido se precisar de oração!
              </p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 select-text">
              {prayers.map((prayer) => (
                <div 
                  key={prayer.id}
                  className={`bg-surface border rounded-[4px] p-5 flex flex-col justify-between transition-all group active:scale-[0.99] duration-200 ${
                    prayer.is_urgent
                      ? 'border-red-500/40 ring-1 ring-red-500/10'
                      : 'border-border-custom'
                  }`}
                >
                  <div className="space-y-4">
                    {/* Header Pedido */}
                    <div className="flex items-start justify-between gap-4">
                      <div className="flex items-center gap-2.5">
                        {/* Avatar */}
                        <div className="w-8 h-8 rounded-[4px] border border-border-custom flex items-center justify-center shrink-0 overflow-hidden relative bg-bg-dark select-none">
                          {prayer.is_anonymous ? (
                            <div className="absolute inset-0 bg-red-950/20 text-red-400 border border-red-900/40 flex items-center justify-center text-xs font-black select-none">
                              🔒
                            </div>
                          ) : prayer.author_avatar && !prayer.author_avatar.includes('ui-avatars.com') ? (
                            <img src={prayer.author_avatar} alt={prayer.author_name || ''} className="w-full h-full object-cover" />
                          ) : (
                            <div className={`absolute inset-0 bg-gradient-to-br ${getAvatarGradient(prayer.author_name || 'M')} flex items-center justify-center text-white font-extrabold text-xs`}>
                              {(prayer.author_name || 'A').charAt(0).toUpperCase()}
                            </div>
                          )}
                        </div>

                        <div>
                          <h4 className="text-[10px] font-black text-text-main truncate max-w-[120px] leading-tight">
                            {prayer.is_anonymous ? '🔒 Anônimo' : prayer.author_name}
                          </h4>
                          <span className="text-[8px] text-text-muted font-bold uppercase tracking-wider block mt-0.5">
                            {new Date(prayer.created_at).toLocaleDateString('pt-BR')}
                          </span>
                        </div>
                      </div>

                      <span className="inline-flex items-center px-1.5 py-0.5 rounded-[2px] text-[8px] font-extrabold bg-bg-dark border border-border-custom text-text-muted uppercase tracking-wider shrink-0 select-none">
                        {categoryTranslations[prayer.category] || 'Outros'}
                      </span>
                    </div>

                    {/* Conteúdo Pedido */}
                    <div className="space-y-1.5 select-text">
                      <h3 className="text-xs font-black text-text-main leading-snug flex items-center flex-wrap gap-1.5 font-display select-text">
                        {prayer.is_urgent && (
                          <span className="inline-flex items-center px-1 py-0.5 rounded-[2px] bg-red-500/10 border border-red-500/20 text-red-500 text-[8px] font-black uppercase tracking-widest shrink-0 animate-pulse select-none">🔥 Urgente</span>
                        )}
                        {prayer.title}
                      </h3>
                      <p className="text-[11px] text-text-muted leading-relaxed font-body font-medium select-text whitespace-pre-line">
                        {prayer.description}
                      </p>
                    </div>
                  </div>

                  {/* Footer Ações */}
                  <div className="pt-4 mt-4 border-t border-border-custom flex items-center justify-between gap-4 select-none shrink-0">
                    <button 
                      onClick={() => handleIntercede(prayer.id)}
                      className={`inline-flex items-center gap-1.5 text-[9px] font-black uppercase tracking-wider px-3.5 py-2 rounded-[4px] transition-all cursor-pointer border ${
                        prayer.is_interceded
                          ? 'bg-rose-500/15 border-rose-500/30 text-rose-500 shadow-xs'
                          : 'bg-bg-dark border-border-custom text-text-muted hover:text-text-main'
                      }`}
                    >
                      <Heart className={`w-3.5 h-3.5 ${prayer.is_interceded ? 'fill-rose-500 scale-105' : ''}`} />
                      <span>{prayer.is_interceded ? 'Intercedi' : 'Interceder'}</span>
                      <span className="opacity-75 font-body">({prayer.pray_count})</span>
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Modal de Criar Pedido de Oração */}
      {showCreatePrayerModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div 
            className="absolute inset-0 bg-black/60 backdrop-blur-xs select-none" 
            onClick={() => setShowCreatePrayerModal(false)}
          />
          <div className="bg-surface border border-border-custom w-full max-w-md rounded-[4px] overflow-hidden shadow-2xl relative z-10 animate-scale-up select-text">
            <div className="px-5 py-4 border-b border-border-custom flex justify-between items-center bg-primary/5">
              <h3 className="font-extrabold text-sm text-primary flex items-center gap-2">
                <Heart className="w-4 h-4 text-primary" />
                <span>Novo Pedido de Oração</span>
              </h3>
              <button 
                type="button" 
                className="text-text-muted hover:bg-bg-dark p-1 rounded-[4px] transition-colors cursor-pointer"
                onClick={() => setShowCreatePrayerModal(false)}
              >
                <X className="w-4 h-4" />
              </button>
            </div>
            
            <form onSubmit={handleCreatePrayer} className="p-5 space-y-4">
              <div className="space-y-1.5">
                <label className="text-[10px] font-black text-text-muted uppercase tracking-wider">Título do Pedido</label>
                <input
                  type="text"
                  value={newPrayerTitle}
                  onChange={(e) => setNewPrayerTitle(e.target.value)}
                  placeholder="Ex: Saúde de um familiar..."
                  className="w-full h-9 px-3 rounded-[4px] bg-bg-dark border border-border-custom text-xs focus:outline-none focus:border-primary text-text-main placeholder-text-muted/65"
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-1.5">
                  <label className="text-[10px] font-black text-text-muted uppercase tracking-wider">Categoria</label>
                  <select
                    value={newPrayerCat}
                    onChange={(e) => setNewPrayerCat(e.target.value as any)}
                    className="w-full h-9 px-2 rounded-[4px] bg-bg-dark border border-border-custom text-xs text-text-main focus:outline-none focus:border-primary cursor-pointer"
                  >
                    <option value="spiritual">🙏 Espiritual</option>
                    <option value="health">🏥 Saúde</option>
                    <option value="family">🏠 Família</option>
                    <option value="work">💼 Trabalho</option>
                    <option value="gratitude">✨ Gratidão</option>
                    <option value="other">❓ Outros</option>
                  </select>
                </div>

                <div className="flex flex-col justify-end space-y-2 select-none pb-1">
                  <label className="flex items-center gap-2 text-xs font-bold text-text-muted hover:text-text-main cursor-pointer">
                    <input 
                      type="checkbox"
                      checked={newPrayerUrgent}
                      onChange={(e) => setNewPrayerUrgent(e.target.checked)}
                      className="rounded-[2px] accent-primary" 
                    />
                    <span>🚨 Urgente</span>
                  </label>
                  <label className="flex items-center gap-2 text-xs font-bold text-text-muted hover:text-text-main cursor-pointer">
                    <input 
                      type="checkbox"
                      checked={newPrayerAnon}
                      onChange={(e) => setNewPrayerAnon(e.target.checked)}
                      className="rounded-[2px] accent-primary" 
                    />
                    <span>🔒 Anônimo</span>
                  </label>
                </div>
              </div>

              <div className="space-y-1.5">
                <label className="text-[10px] font-black text-text-muted uppercase tracking-wider">Descrição / Detalhes</label>
                <textarea
                  value={newPrayerDesc}
                  onChange={(e) => setNewPrayerDesc(e.target.value)}
                  placeholder="Escreva seu pedido com mais detalhes para podermos orar por você..."
                  className="w-full min-h-[100px] p-3 rounded-[4px] bg-bg-dark border border-border-custom text-xs focus:outline-none focus:border-primary text-text-main placeholder-text-muted/65 leading-relaxed"
                  required
                />
              </div>

              <div className="flex gap-2.5 justify-end pt-2 select-none">
                <button 
                  type="button" 
                  className="h-9 px-4 rounded-[4px] border border-border-custom text-xs font-bold text-text-muted hover:text-text-main cursor-pointer"
                  onClick={() => setShowCreatePrayerModal(false)}
                >
                  Cancelar
                </button>
                <button 
                  type="submit"
                  disabled={creatingPrayer || !newPrayerTitle.trim() || !newPrayerDesc.trim()}
                  className="h-9 px-4 rounded-[4px] bg-primary text-bg-dark text-xs font-black cursor-pointer transition-colors"
                >
                  {creatingPrayer ? 'Criando...' : 'Publicar Pedido'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
