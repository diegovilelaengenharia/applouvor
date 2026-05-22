import React, { useEffect, useState } from 'react';
import { 
  Heart, 
  Search, 
  Plus, 
  X, 
  Check, 
  AlertTriangle, 
  ExternalLink, 
  Music
} from 'lucide-react';
import { useAuth } from '../context/AuthContext';

interface Suggestion {
  id: number;
  user_id: number;
  title: string;
  artist: string;
  tone: string | null;
  youtube_link: string | null;
  spotify_link: string | null;
  reason: string | null;
  status: 'pending' | 'approved' | 'rejected';
  created_at: string;
  reviewed_by: number | null;
  reviewed_at: string | null;
  user_name: string;
  user_photo: string | null;
}

export const SugestoesView: React.FC = () => {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';

  const [activeTab, setActiveTab] = useState<'pending' | 'approved' | 'rejected' | 'all'>('pending');
  const [suggestions, setSuggestions] = useState<Suggestion[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState('');

  // Modal para sugerir nova música
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [newTitle, setNewTitle] = useState('');
  const [newArtist, setNewArtist] = useState('');
  const [newTone, setNewTone] = useState('');
  const [newYoutube, setNewYoutube] = useState('');
  const [newSpotify, setNewSpotify] = useState('');
  const [newReason, setNewReason] = useState('');
  const [creating, setCreating] = useState(false);
  const [createMessage, setCreateMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  // Ações administrativas
  const [adminActionLoading, setAdminActionLoading] = useState<number | null>(null);

  const fetchSuggestions = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const filterParam = activeTab ? `&filter=${activeTab}` : '';
      const response = await fetch(`../api/admin/sugestoes_api.php?action=list${filterParam}&nocache=true`);
      const result = await response.json();
      
      if (result.success) {
        setSuggestions(result.suggestions || []);
      } else {
        setError(result.message || 'Erro ao carregar sugestões.');
      }
    } catch (err) {
      setError('Erro de conexão com o servidor.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchSuggestions();
  }, [activeTab]);

  const handleCreateSuggestion = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newTitle.trim() || !newArtist.trim()) return;

    try {
      setCreating(true);
      setCreateMessage(null);

      const response = await fetch('../api/admin/sugestoes_api.php?action=create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          title: newTitle,
          artist: newArtist,
          tone: newTone.trim() || null,
          youtube_link: newYoutube.trim() || null,
          spotify_link: newSpotify.trim() || null,
          reason: newReason.trim() || null
        })
      });

      const result = await response.json();

      if (result.success) {
        setCreateMessage({ type: 'success', text: 'Sua sugestão de louvor foi enviada com sucesso!' });
        // Limpar formulário
        setNewTitle('');
        setNewArtist('');
        setNewTone('');
        setNewYoutube('');
        setNewSpotify('');
        setNewReason('');
        
        // Recarregar a lista se estiver visualizando pendentes ou todas
        if (activeTab === 'pending' || activeTab === 'all') {
          fetchSuggestions();
        }

        // Fechar modal após 1.5s
        setTimeout(() => {
          setShowCreateModal(false);
          setCreateMessage(null);
        }, 1500);
      } else {
        setCreateMessage({ type: 'error', text: result.message || 'Erro ao enviar sugestão.' });
      }
    } catch (err) {
      setCreateMessage({ type: 'error', text: 'Erro de conexão ao enviar a sugestão.' });
    } finally {
      setCreating(false);
    }
  };

  const handleApprove = async (id: number) => {
    if (adminActionLoading !== null) return;

    try {
      setAdminActionLoading(id);
      const response = await fetch('../api/admin/sugestoes_api.php?action=approve', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });

      const result = await response.json();

      if (result.success) {
        fetchSuggestions();
      } else {
        alert(result.message || 'Erro ao aprovar sugestão.');
      }
    } catch (err) {
      alert('Erro de conexão ao tentar aprovar.');
    } finally {
      setAdminActionLoading(null);
    }
  };

  const handleReject = async (id: number) => {
    if (adminActionLoading !== null) return;

    try {
      setAdminActionLoading(id);
      const response = await fetch('../api/admin/sugestoes_api.php?action=reject', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });

      const result = await response.json();

      if (result.success) {
        fetchSuggestions();
      } else {
        alert(result.message || 'Erro ao rejeitar sugestão.');
      }
    } catch (err) {
      alert('Erro de conexão ao tentar rejeitar.');
    } finally {
      setAdminActionLoading(null);
    }
  };

  // Gerador de cores para avatares mock
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

  const filteredSuggestions = suggestions.filter(item => 
    item.title.toLowerCase().includes(search.toLowerCase()) || 
    item.artist.toLowerCase().includes(search.toLowerCase()) || 
    (item.user_name && item.user_name.toLowerCase().includes(search.toLowerCase()))
  );

  return (
    <div className="space-y-6 font-hanken">
      {/* Hero Header Bento Box */}
      <div className="relative overflow-hidden rounded-[4px] bg-surface border border-border-custom p-6 md:p-8">
        <div className="absolute -right-16 -top-16 w-64 h-64 bg-primary/5 rounded-full blur-3xl pointer-events-none" />
        <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div>
            <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-[4px] bg-primary/10 border border-primary/20 text-primary text-[10px] font-bold uppercase tracking-wider mb-3">
              ❤️ Louvores Sugeridos
            </span>
            <h1 className="text-2xl md:text-3xl font-extrabold tracking-tight text-text-main">
              Sugestões de <span className="text-primary font-black">Músicas</span>
            </h1>
            <p className="text-text-muted text-xs md:text-sm mt-1 max-w-xl leading-relaxed">
              Descubra novos louvores sugeridos pelo ministério. Voluntários podem sugerir canções e líderes podem aprová-las para inserção direta no repertório oficial.
            </p>
          </div>

          <button
            onClick={() => setShowCreateModal(true)}
            className="h-9 px-4 rounded-[4px] bg-primary text-bg-dark text-xs font-black flex items-center gap-1.5 shrink-0 cursor-pointer transition-all active:scale-95 shadow-sm"
          >
            <Plus className="w-4 h-4 text-bg-dark" strokeWidth={3} />
            <span>Sugerir Louvor</span>
          </button>
        </div>
      </div>

      {/* Caixa Bento de Busca & Filtros */}
      <div className="bg-surface border border-border-custom rounded-[4px] p-5 space-y-4 select-none">
        {/* Campo de Busca */}
        <div className="relative w-full">
          <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted w-5 h-5" />
          <input 
            type="text" 
            placeholder="Buscar por música, artista ou proponente..." 
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full h-11 pl-12 pr-4 bg-bg-dark border border-border-custom rounded-[4px] text-sm focus:outline-none focus:border-primary text-text-main placeholder-text-muted/65 leading-relaxed font-medium"
          />
        </div>

        {/* Filtros em Linha */}
        <div className="flex flex-wrap items-center justify-between gap-4">
          <div className="flex items-center gap-2">
            <span className="text-[10px] font-black text-text-muted uppercase tracking-wider pl-1">Filtrar Status:</span>
            
            <div className="flex gap-1 p-0.5 bg-bg-dark rounded-[4px] border border-border-custom h-8 shrink-0">
              <button
                onClick={() => setActiveTab('pending')}
                className={`px-3 py-1 rounded-[2px] text-[10px] font-bold cursor-pointer transition-all ${activeTab === 'pending' ? 'bg-surface text-primary border border-border-custom shadow-xs' : 'text-text-muted hover:text-text-main'}`}
              >
                Pendentes
              </button>
              <button
                onClick={() => setActiveTab('approved')}
                className={`px-3 py-1 rounded-[2px] text-[10px] font-bold cursor-pointer transition-all ${activeTab === 'approved' ? 'bg-surface text-primary border border-border-custom shadow-xs' : 'text-text-muted hover:text-text-main'}`}
              >
                Aprovadas
              </button>
              <button
                onClick={() => setActiveTab('rejected')}
                className={`px-3 py-1 rounded-[2px] text-[10px] font-bold cursor-pointer transition-all ${activeTab === 'rejected' ? 'bg-surface text-primary border border-border-custom shadow-xs' : 'text-text-muted hover:text-text-main'}`}
              >
                Rejeitadas
              </button>
              <button
                onClick={() => setActiveTab('all')}
                className={`px-3 py-1 rounded-[2px] text-[10px] font-bold cursor-pointer transition-all ${activeTab === 'all' ? 'bg-surface text-primary border border-border-custom shadow-xs' : 'text-text-muted hover:text-text-main'}`}
              >
                Todas
              </button>
            </div>
          </div>

          <div className="text-[10px] text-text-muted font-bold uppercase tracking-wider bg-bg-dark/40 border border-border-custom/50 px-2.5 py-1 rounded-[2px]">
            Total: {filteredSuggestions.length} {filteredSuggestions.length === 1 ? 'sugestão' : 'sugestões'}
          </div>
        </div>
      </div>

      {/* Grid de Sugestões */}
      {loading ? (
        <div className="min-h-[250px] flex items-center justify-center">
          <div className="w-8 h-8 rounded-full border-2 border-primary/20 border-t-primary animate-spin" />
        </div>
      ) : error ? (
        <div className="border border-red-500/20 bg-red-500/5 p-4 rounded-[4px] text-xs font-bold text-red-400 flex items-start gap-2 max-w-xl">
          <AlertTriangle className="w-4 h-4 shrink-0 mt-0.5" />
          <span>{error}</span>
        </div>
      ) : filteredSuggestions.length === 0 ? (
        <div className="border border-dashed border-border-custom bg-surface/10 rounded-[4px] p-12 text-center">
          <Heart className="w-10 h-10 text-text-muted/40 mx-auto mb-3" />
          <h3 className="text-sm font-extrabold text-text-main mb-1">Nenhuma sugestão encontrada</h3>
          <p className="text-xs text-text-muted max-w-xs mx-auto leading-relaxed">
            {search ? 'Tente ajustar sua busca ou limpar os filtros para encontrar o que procura.' : 'Não há sugestões sob este status no momento. Envie uma nova sugestão!'}
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {filteredSuggestions.map((item) => (
            <div 
              key={item.id}
              className={`bg-surface border border-border-custom rounded-[4px] p-5 flex flex-col justify-between transition-all duration-200 hover:border-primary/20 ${
                item.status === 'pending' ? 'border-l-2 border-l-amber-500/70' :
                item.status === 'approved' ? 'border-l-2 border-l-emerald-500/70' :
                'border-l-2 border-l-rose-500/70'
              }`}
            >
              <div className="space-y-4">
                {/* Header do Card */}
                <div className="flex items-start justify-between gap-4">
                  <div className="flex items-center gap-2.5">
                    {/* Foto/Avatar do Proponente */}
                    <div className="w-8 h-8 rounded-[4px] border border-border-custom flex items-center justify-center shrink-0 overflow-hidden relative bg-bg-dark">
                      {item.user_photo && !item.user_photo.includes('ui-avatars.com') ? (
                        <img src={item.user_photo} alt={item.user_name} className="w-full h-full object-cover" />
                      ) : (
                        <div className={`absolute inset-0 bg-gradient-to-br ${getAvatarGradient(item.user_name)} flex items-center justify-center text-white font-extrabold text-xs`}>
                          {item.user_name.charAt(0).toUpperCase()}
                        </div>
                      )}
                    </div>
                    
                    <div>
                      <h4 className="text-[11px] font-black text-text-main truncate max-w-[150px] leading-tight">
                        {item.user_name}
                      </h4>
                      <span className="text-[8px] text-text-muted font-bold uppercase tracking-wider block mt-0.5">
                        Sugerido em {new Date(item.created_at).toLocaleDateString('pt-BR')}
                      </span>
                    </div>
                  </div>

                  {/* Status Badge */}
                  <span className={`inline-flex items-center px-2 py-0.5 rounded-[2px] text-[8px] font-extrabold uppercase tracking-wider border ${
                    item.status === 'pending' ? 'text-amber-500 bg-amber-500/10 border-amber-500/25' :
                    item.status === 'approved' ? 'text-emerald-500 bg-emerald-500/10 border-emerald-500/25' :
                    'text-rose-500 bg-rose-500/10 border-rose-500/25'
                  }`}>
                    {item.status === 'pending' ? 'Pendente' :
                     item.status === 'approved' ? 'Aprovada' : 'Rejeitada'}
                  </span>
                </div>

                {/* Corpo do Card (Música & Cifra) */}
                <div className="space-y-2 select-text">
                  <div>
                    <h3 className="text-xs font-black text-text-main flex items-center gap-1.5 flex-wrap">
                      <Music className="w-3.5 h-3.5 text-primary shrink-0" />
                      {item.title}
                    </h3>
                    <p className="text-[10px] text-text-muted font-bold uppercase tracking-wider mt-0.5">
                      por {item.artist}
                    </p>
                  </div>

                  {item.tone && (
                    <div className="inline-flex items-center gap-1 px-2 py-0.5 rounded-[2px] bg-bg-dark border border-border-custom text-[9px] font-bold text-text-main select-none">
                      <span className="text-text-muted">Tom:</span>
                      <span className="text-primary font-black uppercase">{item.tone}</span>
                    </div>
                  )}

                  {item.reason && (
                    <div className="bg-bg-dark/45 border border-border-custom rounded-[4px] p-3 mt-2 select-text">
                      <div className="text-[8px] font-black text-text-muted uppercase tracking-wider mb-1">Justificativa / Comentário</div>
                      <p className="text-[11px] text-text-muted leading-relaxed font-body font-medium whitespace-pre-line italic">
                        "{item.reason}"
                      </p>
                    </div>
                  )}
                </div>
              </div>

              {/* Footer do Card (Links & Ações Adm) */}
              <div className="pt-4 mt-4 border-t border-border-custom flex flex-wrap items-center justify-between gap-3 select-none shrink-0">
                {/* Links de Mídia */}
                <div className="flex items-center gap-2">
                  {item.youtube_link && (
                    <a 
                      href={item.youtube_link} 
                      target="_blank" 
                      rel="noopener noreferrer"
                      className="h-7 px-2.5 rounded-[4px] bg-red-500/5 hover:bg-red-500/10 border border-red-500/10 hover:border-red-500/20 text-red-500 text-[10px] font-bold uppercase tracking-wider flex items-center gap-1 cursor-pointer transition-all active:scale-95"
                    >
                      <ExternalLink className="w-3 h-3" />
                      <span>YouTube</span>
                    </a>
                  )}

                  {item.spotify_link && (
                    <a 
                      href={item.spotify_link} 
                      target="_blank" 
                      rel="noopener noreferrer"
                      className="h-7 px-2.5 rounded-[4px] bg-emerald-500/5 hover:bg-emerald-500/10 border border-emerald-500/10 hover:border-emerald-500/20 text-emerald-500 text-[10px] font-bold uppercase tracking-wider flex items-center gap-1 cursor-pointer transition-all active:scale-95"
                    >
                      <ExternalLink className="w-3 h-3" />
                      <span>Spotify</span>
                    </a>
                  )}

                  {!item.youtube_link && !item.spotify_link && (
                    <span className="text-[9px] text-text-muted italic">Sem links de mídia anexos</span>
                  )}
                </div>

                {/* Ações do Administrador */}
                {isAdmin && item.status === 'pending' && (
                  <div className="flex items-center gap-2">
                    <button
                      onClick={() => handleReject(item.id)}
                      disabled={adminActionLoading !== null}
                      className="h-7 px-2.5 rounded-[4px] bg-rose-500/10 hover:bg-rose-500/15 border border-rose-500/20 hover:border-rose-500/30 text-rose-500 text-[10px] font-black uppercase tracking-wider flex items-center gap-1 cursor-pointer transition-all active:scale-95 disabled:opacity-50"
                      title="Rejeitar louvor"
                    >
                      <X className="w-3 h-3" />
                      <span>Rejeitar</span>
                    </button>

                    <button
                      onClick={() => handleApprove(item.id)}
                      disabled={adminActionLoading !== null}
                      className="h-7 px-2.5 rounded-[4px] bg-emerald-500/10 hover:bg-emerald-500/15 border border-emerald-500/20 hover:border-emerald-500/30 text-emerald-500 text-[10px] font-black uppercase tracking-wider flex items-center gap-1 cursor-pointer transition-all active:scale-95 disabled:opacity-50"
                      title="Aprovar e adicionar ao Repertório"
                    >
                      <Check className="w-3 h-3 text-emerald-500" strokeWidth={3} />
                      <span>Aprovar</span>
                    </button>
                  </div>
                )}
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Modal / Drawer para sugerir música */}
      {showCreateModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div 
            className="absolute inset-0 bg-black/60 backdrop-blur-xs select-none" 
            onClick={() => { if (!creating) setShowCreateModal(false); }}
          />
          <div className="bg-surface border border-border-custom w-full max-w-md rounded-[4px] overflow-hidden shadow-2xl relative z-10 animate-scale-up select-text">
            <div className="px-5 py-4 border-b border-border-custom flex justify-between items-center bg-primary/5">
              <h3 className="font-extrabold text-sm text-primary flex items-center gap-2">
                <Heart className="w-4 h-4 text-primary fill-primary/10" />
                <span>Sugerir Louvor para o Repertório</span>
              </h3>
              <button 
                type="button" 
                disabled={creating}
                className="text-text-muted hover:bg-bg-dark p-1 rounded-[4px] transition-colors cursor-pointer disabled:opacity-50"
                onClick={() => setShowCreateModal(false)}
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <form onSubmit={handleCreateSuggestion} className="p-5 space-y-4">
              {createMessage && (
                <div className={`p-3 rounded-[4px] text-xs font-bold border ${
                  createMessage.type === 'success' 
                    ? 'bg-emerald-500/5 border-emerald-500/20 text-emerald-400' 
                    : 'bg-rose-500/5 border-rose-500/20 text-rose-400'
                }`}>
                  {createMessage.text}
                </div>
              )}

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-1.5">
                  <label className="text-[10px] font-black text-text-muted uppercase tracking-wider">Título da Música *</label>
                  <input
                    type="text"
                    value={newTitle}
                    onChange={(e) => setNewTitle(e.target.value)}
                    placeholder="Ex: Hosana"
                    className="w-full h-9 px-3 rounded-[4px] bg-bg-dark border border-border-custom text-xs focus:outline-none focus:border-primary text-text-main placeholder-text-muted/65 font-medium"
                    required
                    disabled={creating}
                  />
                </div>

                <div className="space-y-1.5">
                  <label className="text-[10px] font-black text-text-muted uppercase tracking-wider">Artista / Banda *</label>
                  <input
                    type="text"
                    value={newArtist}
                    onChange={(e) => setNewArtist(e.target.value)}
                    placeholder="Ex: Hillsong"
                    className="w-full h-9 px-3 rounded-[4px] bg-bg-dark border border-border-custom text-xs focus:outline-none focus:border-primary text-text-main placeholder-text-muted/65 font-medium"
                    required
                    disabled={creating}
                  />
                </div>
              </div>

              <div className="space-y-1.5">
                <label className="text-[10px] font-black text-text-muted uppercase tracking-wider">Tom Sugerido (opcional)</label>
                <input
                  type="text"
                  value={newTone}
                  onChange={(e) => setNewTone(e.target.value)}
                  placeholder="Ex: G, F#m, C..."
                  className="w-full h-9 px-3 rounded-[4px] bg-bg-dark border border-border-custom text-xs focus:outline-none focus:border-primary text-text-main placeholder-text-muted/65 uppercase font-bold"
                  disabled={creating}
                />
              </div>

              <div className="grid grid-cols-1 gap-3">
                <div className="space-y-1.5">
                  <label className="text-[10px] font-black text-text-muted uppercase tracking-wider">Link do YouTube (opcional)</label>
                  <input
                    type="url"
                    value={newYoutube}
                    onChange={(e) => setNewYoutube(e.target.value)}
                    placeholder="https://youtube.com/watch?v=..."
                    className="w-full h-9 px-3 rounded-[4px] bg-bg-dark border border-border-custom text-xs focus:outline-none focus:border-primary text-text-main placeholder-text-muted/65 font-medium"
                    disabled={creating}
                  />
                </div>

                <div className="space-y-1.5">
                  <label className="text-[10px] font-black text-text-muted uppercase tracking-wider">Link do Spotify (opcional)</label>
                  <input
                    type="url"
                    value={newSpotify}
                    onChange={(e) => setNewSpotify(e.target.value)}
                    placeholder="https://open.spotify.com/track/..."
                    className="w-full h-9 px-3 rounded-[4px] bg-bg-dark border border-border-custom text-xs focus:outline-none focus:border-primary text-text-main placeholder-text-muted/65 font-medium"
                    disabled={creating}
                  />
                </div>
              </div>

              <div className="space-y-1.5">
                <label className="text-[10px] font-black text-text-muted uppercase tracking-wider">Por que devemos tocar essa música? *</label>
                <textarea
                  value={newReason}
                  onChange={(e) => setNewReason(e.target.value)}
                  placeholder="Escreva por que essa música edificará o ministério e a igreja..."
                  className="w-full min-h-[90px] p-3 rounded-[4px] bg-bg-dark border border-border-custom text-xs focus:outline-none focus:border-primary text-text-main placeholder-text-muted/65 leading-relaxed font-medium"
                  required
                  disabled={creating}
                />
              </div>

              <div className="flex gap-2.5 justify-end pt-2 select-none">
                <button 
                  type="button" 
                  disabled={creating}
                  className="h-9 px-4 rounded-[4px] border border-border-custom text-xs font-bold text-text-muted hover:text-text-main cursor-pointer transition-colors disabled:opacity-50"
                  onClick={() => setShowCreateModal(false)}
                >
                  Cancelar
                </button>
                <button 
                  type="submit"
                  disabled={creating || !newTitle.trim() || !newArtist.trim() || !newReason.trim()}
                  className="h-9 px-5 rounded-[4px] bg-primary text-bg-dark text-xs font-black cursor-pointer transition-all disabled:opacity-50"
                >
                  {creating ? 'Enviando...' : 'Sugerir Música'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
