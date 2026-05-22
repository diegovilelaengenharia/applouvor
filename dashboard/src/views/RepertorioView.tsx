import React, { useEffect, useState } from 'react';
import { 
  Music, 
  Search, 
  ExternalLink, 
  Play, 
  ChevronRight, 
  ChevronLeft, 
  ChevronsUp,
  AlertTriangle
} from 'lucide-react';

interface SongTag {
  id: number;
  name: string;
  color: string;
}

interface Song {
  id: string | number;
  title: string;
  artist: string;
  tone: string;
  rhythm?: string;
  youtube_url?: string;
  chords_url?: string;
  lyrics?: string;
  last_played?: string | null;
  tags: SongTag[];
}

interface FilterData {
  tags: { id: number; name: string; color: string; count: number }[];
  artists: { name: string; count: number }[];
  tones: { name: string; count: number }[];
}

export const RepertorioView: React.FC = () => {
  const [loading, setLoading] = useState(true);
  const [songs, setSongs] = useState<Song[]>([]);
  const [error, setError] = useState<string | null>(null);

  // Filtros ativos
  const [search, setSearch] = useState('');
  const [selectedTag, setSelectedTag] = useState<string>('');
  const [selectedTone, setSelectedTone] = useState<string>('');
  const [selectedArtist, setSelectedArtist] = useState<string>('');

  // Listas de filtros auxiliares do backend
  const [filtersData, setFiltersData] = useState<FilterData>({
    tags: [],
    artists: [],
    tones: []
  });

  // Visualizador Lateral de Cifra/Letra (Drawer)
  const [activeSongId, setActiveSongId] = useState<string | number | null>(null);
  const [activeSong, setActiveSong] = useState<Song | null>(null);
  const [autoScrollActive, setAutoScrollActive] = useState(false);
  const [scrollIntervalId, setScrollIntervalId] = useState<number | null>(null);
  const [scrollSpeed, setScrollSpeed] = useState(1); // 1 = devagar, 2 = médio, 3 = rápido

  const fetchFilters = async () => {
    try {
      const response = await fetch('../api/admin/repertorio_api.php?action=filters');
      const result = await response.json();
      if (result.success) {
        setFiltersData(result.data);
      }
    } catch (err) {
      console.error('Erro ao carregar filtros auxiliares:', err);
    }
  };

  const fetchSongs = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const searchParam = search ? `&search=${encodeURIComponent(search)}` : '';
      const tagParam = selectedTag ? `&tag_id=${selectedTag}` : '';
      const toneParam = selectedTone ? `&tone=${encodeURIComponent(selectedTone)}` : '';
      
      const response = await fetch(`../api/admin/repertorio_api.php?nocache=true${searchParam}${tagParam}${toneParam}`);
      const result = await response.json();
      
      if (result.success) {
        let filteredSongs = result.data || [];
        // Filtro de artista no client-side para manter performance
        if (selectedArtist) {
          filteredSongs = filteredSongs.filter((s: Song) => s.artist === selectedArtist);
        }
        setSongs(filteredSongs);
      } else {
        setError(result.error || 'Erro ao carregar repertório');
      }
    } catch (err) {
      setError('Erro de conexão com o servidor');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchFilters();
  }, []);

  useEffect(() => {
    const timer = setTimeout(() => {
      fetchSongs();
    }, 250); // Debounce de busca
    return () => clearTimeout(timer);
  }, [search, selectedTag, selectedTone, selectedArtist]);

  // Gerenciar o Drawer de cifra
  useEffect(() => {
    if (activeSongId !== null) {
      const song = songs.find(s => String(s.id) === String(activeSongId));
      setActiveSong(song || null);
    } else {
      setActiveSong(null);
      stopAutoScroll();
    }
  }, [activeSongId, songs]);

  const toggleAutoScroll = () => {
    if (autoScrollActive) {
      stopAutoScroll();
    } else {
      startAutoScroll();
    }
  };

  const startAutoScroll = () => {
    stopAutoScroll();
    setAutoScrollActive(true);
    
    const container = document.getElementById('lyrics-scroll-container');
    if (!container) return;

    const intervalSpeed = scrollSpeed === 1 ? 55 : scrollSpeed === 2 ? 35 : 20;

    const id = window.setInterval(() => {
      container.scrollTop += 1;
    }, intervalSpeed);

    setScrollIntervalId(id);
  };

  const stopAutoScroll = () => {
    if (scrollIntervalId) {
      clearInterval(scrollIntervalId);
      setScrollIntervalId(null);
    }
    setAutoScrollActive(false);
  };

  useEffect(() => {
    if (autoScrollActive) {
      startAutoScroll(); // Reinicia o scroll se a velocidade mudar
    }
  }, [scrollSpeed]);

  useEffect(() => {
    return () => stopAutoScroll(); // Limpar ao desmontar
  }, [scrollIntervalId]);

  return (
    <div className="space-y-6 font-hanken">
      {/* Hero / Header Bento Box */}
      <div className="relative overflow-hidden rounded-[4px] bg-surface border border-border-custom p-6 md:p-8">
        <div className="absolute -right-16 -top-16 w-64 h-64 bg-primary/5 rounded-full blur-3xl pointer-events-none" />
        <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div>
            <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-[4px] bg-primary/10 border border-primary/20 text-primary text-[10px] font-bold uppercase tracking-wider mb-3">
              🎸 Acervo Musical
            </span>
            <h1 className="text-2xl md:text-3xl font-extrabold tracking-tight text-text-main">
              Repertório <span className="text-primary font-black">PIB Louvor</span>
            </h1>
            <p className="text-text-muted text-xs md:text-sm mt-1 max-w-xl leading-relaxed">
              Consulte as cifras oficiais, ouça no Youtube, filtre por tom, ritmo ou tags temáticas de forma instantânea.
            </p>
          </div>

          <div className="flex items-center gap-4 bg-bg-dark/40 border border-border-custom rounded-[4px] p-4 shrink-0 shadow-xs">
            <div className="w-10 h-10 rounded-[4px] bg-primary/10 flex items-center justify-center text-primary">
              <Music className="w-5 h-5" />
            </div>
            <div>
              <div className="text-2xl font-black text-text-main leading-none">
                {songs.length}
              </div>
              <div className="text-[9px] font-black uppercase tracking-wider text-text-muted mt-1">
                Músicas Filtradas
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Caixa Bento de Busca e Filtros */}
      <div className="bg-surface border border-border-custom rounded-[4px] p-5 space-y-4">
        {/* Campo de Busca Principal */}
        <div className="relative w-full">
          <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted w-5 h-5" />
          <input 
            type="text" 
            placeholder="Buscar por título da música ou artista..." 
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full h-12 pl-12 pr-4 bg-bg-dark border border-border-custom rounded-[4px] text-sm focus:outline-none focus:border-primary text-text-main placeholder-text-muted/65 leading-relaxed"
          />
        </div>

        {/* Filtros em Linha */}
        <div className="flex flex-wrap items-center gap-3 select-none">
          <span className="text-[10px] font-black text-text-muted uppercase tracking-wider pl-1">Filtrar por:</span>
          
          {/* Categoria / Tags */}
          <select 
            value={selectedTag}
            onChange={(e) => setSelectedTag(e.target.value)}
            className="h-8 pl-3 pr-8 rounded-[4px] bg-bg-dark border border-border-custom text-xs font-bold text-text-main focus:outline-none focus:border-primary cursor-pointer"
          >
            <option value="">🏷️ Todas Tags</option>
            {filtersData.tags.map(t => (
              <option key={t.id} value={t.id}>#{t.name} ({t.count})</option>
            ))}
          </select>

          {/* Tom */}
          <select 
            value={selectedTone}
            onChange={(e) => setSelectedTone(e.target.value)}
            className="h-8 pl-3 pr-8 rounded-[4px] bg-bg-dark border border-border-custom text-xs font-bold text-text-main focus:outline-none focus:border-primary cursor-pointer"
          >
            <option value="">🎼 Tons (Todos)</option>
            {filtersData.tones.map(t => (
              <option key={t.name} value={t.name}>{t.name} ({t.count})</option>
            ))}
          </select>

          {/* Artista */}
          <select 
            value={selectedArtist}
            onChange={(e) => setSelectedArtist(e.target.value)}
            className="h-8 pl-3 pr-8 rounded-[4px] bg-bg-dark border border-border-custom text-xs font-bold text-text-main focus:outline-none focus:border-primary cursor-pointer max-w-[200px]"
          >
            <option value="">👤 Artistas (Todos)</option>
            {filtersData.artists.map(a => (
              <option key={a.name} value={a.name}>{a.name} ({a.count})</option>
            ))}
          </select>

          {/* Limpar Filtros */}
          {(search || selectedTag || selectedTone || selectedArtist) && (
            <button
              onClick={() => {
                setSearch('');
                setSelectedTag('');
                setSelectedTone('');
                setSelectedArtist('');
              }}
              className="h-8 px-3.5 rounded-[4px] text-xs font-bold bg-red-500/5 hover:bg-red-500/10 border border-red-500/10 hover:border-red-500/20 text-red-500 cursor-pointer transition-colors"
            >
              Limpar Filtros
            </button>
          )}
        </div>
      </div>

      {/* Grid de Músicas */}
      {loading ? (
        <div className="min-h-[200px] flex items-center justify-center">
          <div className="w-8 h-8 rounded-full border-2 border-primary/20 border-t-primary animate-spin" />
        </div>
      ) : error ? (
        <div className="border border-red-500/20 bg-red-500/5 p-4 rounded-[4px] text-xs font-bold text-red-400 flex items-start gap-2 max-w-xl">
          <AlertTriangle className="w-4 h-4 shrink-0 mt-0.5" />
          <span>{error}</span>
        </div>
      ) : songs.length === 0 ? (
        <div className="border border-dashed border-border-custom bg-surface/10 rounded-[4px] p-12 text-center">
          <Music className="w-10 h-10 text-text-muted/40 mx-auto mb-3" />
          <h3 className="text-sm font-extrabold text-text-main mb-1">Nenhuma música encontrada</h3>
          <p className="text-xs text-text-muted max-w-xs mx-auto leading-relaxed">
            Não encontramos músicas com base nos termos de pesquisa e filtros aplicados.
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {songs.map((song) => (
            <div 
              key={song.id}
              className="bg-surface border border-border-custom rounded-[4px] p-4 flex flex-col justify-between hover:border-primary/45 transition-all group active:scale-[0.99] duration-200"
            >
              <div>
                <div className="flex items-start justify-between gap-4">
                  <div className="flex items-center gap-3">
                    {/* Badge do Tom da Música */}
                    <div className="w-10 h-10 bg-bg-dark border border-border-custom rounded-[4px] flex items-center justify-center shrink-0 shadow-xs">
                      <span className="text-sm font-black text-accent">{song.tone}</span>
                    </div>

                    <div>
                      <h3 className="text-xs font-black text-text-main leading-tight group-hover:text-primary transition-colors truncate max-w-[200px]" title={song.title}>
                        {song.title}
                      </h3>
                      <p className="text-[10px] text-text-muted font-bold mt-1.5 uppercase tracking-wider block leading-none">
                        {song.artist}
                      </p>
                    </div>
                  </div>

                  {/* Ritmo */}
                  {song.rhythm && (
                    <span className="px-2 py-0.5 rounded-[2px] bg-bg-dark border border-border-custom text-[8px] font-extrabold text-text-muted uppercase tracking-wider">
                      {song.rhythm}
                    </span>
                  )}
                </div>

                {/* Tags da Música */}
                {song.tags && song.tags.length > 0 && (
                  <div className="flex flex-wrap gap-1 mt-4">
                    {song.tags.map((tag) => (
                      <span 
                        key={tag.id}
                        className="inline-flex items-center px-2 py-0.5 rounded-[2px] text-[8px] font-black uppercase tracking-wider"
                        style={{ 
                          backgroundColor: tag.color ? `${tag.color}15` : '#2e7eed15', 
                          color: tag.color || '#2e7eed',
                          border: `1px solid ${tag.color ? `${tag.color}30` : '#2e7eed30'}`
                        }}
                      >
                        {tag.name}
                      </span>
                    ))}
                  </div>
                )}
              </div>

              {/* Ações */}
              <div className="mt-5 pt-3 border-t border-border-custom flex items-center justify-between gap-4">
                {/* Info última execução */}
                <div className="text-[9px] text-text-muted font-bold uppercase tracking-wider">
                  {song.last_played ? (
                    <span>Tocada em: {new Date(song.last_played + 'T00:00:00').toLocaleDateString('pt-BR')}</span>
                  ) : (
                    <span className="italic opacity-60">Sem histórico</span>
                  )}
                </div>

                <div className="flex items-center gap-1.5">
                  {song.youtube_url && (
                    <a 
                      href={song.youtube_url} 
                      target="_blank" 
                      rel="noopener noreferrer"
                      className="w-8 h-8 rounded-[4px] bg-red-500/10 border border-red-500/20 text-red-500 flex items-center justify-center hover:bg-red-500/20 transition-colors cursor-pointer"
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
                      className="w-8 h-8 rounded-[4px] bg-primary/10 border border-primary/20 text-primary flex items-center justify-center hover:bg-primary/20 transition-colors cursor-pointer"
                      title="Abrir Cifra Externa"
                    >
                      <ExternalLink className="w-3.5 h-3.5" />
                    </a>
                  )}

                  {song.lyrics ? (
                    <button
                      onClick={() => setActiveSongId(song.id)}
                      className="h-8 px-3 rounded-[4px] bg-bg-dark hover:bg-surface-variant/20 border border-border-custom text-text-main text-xs font-bold flex items-center gap-1 cursor-pointer transition-colors"
                    >
                      Cifra <ChevronRight className="w-3.5 h-3.5 text-text-muted" />
                    </button>
                  ) : song.chords_url ? (
                    <a
                      href={song.chords_url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="h-8 px-3 rounded-[4px] bg-bg-dark hover:bg-surface-variant/20 border border-border-custom text-text-main text-xs font-bold flex items-center gap-1 cursor-pointer transition-colors"
                    >
                      Cifra <ExternalLink className="w-3.5 h-3.5 text-text-muted" />
                    </a>
                  ) : null}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Visualizador de Letras/Cifras Embutido (Drawer Lateral) */}
      {activeSongId !== null && (
        <div className="fixed inset-0 z-40 flex justify-end">
          {/* Backdrop */}
          <div 
            className="absolute inset-0 bg-black/60 backdrop-blur-xs" 
            onClick={() => setActiveSongId(null)}
          />
          
          {/* Corpo do Drawer */}
          <div className="w-full max-w-2xl bg-[#121316] border-l border-border-custom h-full flex flex-col justify-between relative z-10 animate-slide-left shadow-2xl select-text">
            {/* Header */}
            <div className="h-16 border-b border-border-custom px-6 flex items-center justify-between shrink-0 bg-surface">
              <div className="flex items-center gap-2">
                <button 
                  onClick={() => setActiveSongId(null)}
                  className="p-1 rounded-[4px] hover:bg-bg-dark text-text-muted hover:text-text-main cursor-pointer"
                >
                  <ChevronLeft className="w-5 h-5" />
                </button>
                <div>
                  <h3 className="text-xs font-black uppercase text-text-main truncate max-w-[200px] leading-none">
                    {activeSong?.title}
                  </h3>
                  <span className="text-[9px] font-bold text-text-muted uppercase tracking-wider block mt-1 leading-none">
                    {activeSong?.artist} • Tom: <span className="text-accent">{activeSong?.tone}</span>
                  </span>
                </div>
              </div>

              {/* Botões do Menu Superior do Drawer */}
              <div className="flex items-center gap-2">
                {/* Auto Scroll Speed */}
                {autoScrollActive && (
                  <div className="flex items-center gap-1 bg-bg-dark p-0.5 rounded-[4px] border border-border-custom mr-1 shrink-0">
                    <button 
                      onClick={() => setScrollSpeed(1)}
                      className={`px-2 py-1 rounded-[2px] text-[8px] font-black uppercase tracking-wider cursor-pointer ${scrollSpeed === 1 ? 'bg-primary text-bg-dark' : 'text-text-muted'}`}
                    >
                      X1
                    </button>
                    <button 
                      onClick={() => setScrollSpeed(2)}
                      className={`px-2 py-1 rounded-[2px] text-[8px] font-black uppercase tracking-wider cursor-pointer ${scrollSpeed === 2 ? 'bg-primary text-bg-dark' : 'text-text-muted'}`}
                    >
                      X2
                    </button>
                    <button 
                      onClick={() => setScrollSpeed(3)}
                      className={`px-2 py-1 rounded-[2px] text-[8px] font-black uppercase tracking-wider cursor-pointer ${scrollSpeed === 3 ? 'bg-primary text-bg-dark' : 'text-text-muted'}`}
                    >
                      X3
                    </button>
                  </div>
                )}

                {/* Auto Scroll Toggle */}
                <button
                  onClick={toggleAutoScroll}
                  className={`h-8 px-3 rounded-[4px] text-[10px] font-black uppercase tracking-wider flex items-center gap-1.5 transition-all cursor-pointer ${
                    autoScrollActive
                      ? 'bg-primary/20 border border-primary/40 text-primary animate-pulse'
                      : 'bg-bg-dark border border-border-custom text-text-muted hover:text-text-main'
                  }`}
                >
                  <ChevronsUp className={`w-3.5 h-3.5 ${autoScrollActive ? 'rotate-180 duration-500' : ''}`} />
                  <span>{autoScrollActive ? 'Parar Rolagem' : 'Rolar Cifra'}</span>
                </button>
              </div>
            </div>

            {/* Container Principal da Cifra/Letra */}
            <div 
              id="lyrics-scroll-container"
              className="flex-1 overflow-y-auto p-6 md:p-8 bg-[#121316] select-text scroll-smooth"
            >
              {activeSong?.lyrics ? (
                <pre className="text-xs md:text-sm font-mono font-bold leading-relaxed whitespace-pre text-text-main select-text pl-1">
                  {activeSong.lyrics}
                </pre>
              ) : (
                <div className="h-full flex flex-col items-center justify-center text-center p-6 text-text-muted">
                  <Music className="w-10 h-10 mb-3 opacity-30 animate-pulse" />
                  <p className="text-xs font-bold leading-relaxed max-w-xs font-body">
                    A letra ou cifra formatada não está embutida nesta música. Você pode abrir o link completo ou o vídeo do Youtube.
                  </p>
                  
                  <div className="flex gap-2.5 mt-5 shrink-0">
                    {activeSong?.chords_url && (
                      <a 
                        href={activeSong.chords_url} 
                        target="_blank" 
                        rel="noopener noreferrer"
                        className="h-9 px-4 rounded-[4px] bg-primary hover:bg-primary/95 text-bg-dark text-xs font-black flex items-center gap-1.5 transition-colors"
                      >
                        Abrir Cifra Externa <ExternalLink className="w-3.5 h-3.5" />
                      </a>
                    )}
                  </div>
                </div>
              )}
            </div>

            {/* Rodapé do Drawer */}
            <div className="h-14 border-t border-border-custom px-6 bg-surface flex items-center justify-between shrink-0 select-none">
              <span className="text-[9px] font-black uppercase text-text-muted tracking-widest">
                PIB Worship © Cifras Oficiais
              </span>
              <div className="flex gap-2 shrink-0">
                {activeSong?.youtube_url && (
                  <a 
                    href={activeSong.youtube_url} 
                    target="_blank" 
                    rel="noopener noreferrer"
                    className="h-7 px-3.5 rounded-[4px] bg-red-500/10 border border-red-500/20 text-red-500 text-[10px] font-extrabold flex items-center gap-1 transition-colors cursor-pointer"
                  >
                    <Play className="w-3.5 h-3.5 fill-red-500" /> Youtube
                  </a>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
