import React, { useEffect, useState } from 'react';
import { 
  Users, 
  Search, 
  Phone, 
  MessageSquare, 
  User, 
  Plus,
  AlertTriangle,
  Mail,
  Compass
} from 'lucide-react';
import { useAuth } from '../context/AuthContext';

interface Role {
  id: number;
  name: string;
  icon: string;
  color: string;
  is_primary: boolean;
}

interface Member {
  id: number;
  name: string;
  email: string;
  phone: string;
  avatar: string;
  role: 'admin' | 'user';
  roles: Role[];
  total_escalas: number;
  taxa: number | null;
}

export const MembrosView: React.FC = () => {
  const { user: currentUser } = useAuth();
  const [loading, setLoading] = useState(true);
  const [members, setMembers] = useState<Member[]>([]);
  const [error, setError] = useState<string | null>(null);

  // Busca e ordenação
  const [search, setSearch] = useState('');
  const [sort, setSort] = useState<'name' | 'taxa' | 'escalas'>('name');

  const fetchMembers = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await fetch(`../api/admin/membros_api.php?nocache=true&sort=${sort}`);
      const result = await response.json();
      
      if (result.success) {
        setMembers(result.data || []);
      } else {
        setError(result.error || 'Erro ao carregar equipe');
      }
    } catch (err) {
      setError('Erro de conexão com o servidor');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchMembers();
  }, [sort]);

  const isAdmin = currentUser?.role === 'admin';

  // Filtragem no client-side
  const filteredMembers = members.filter(m => {
    const term = search.toLowerCase();
    const matchesName = m.name.toLowerCase().includes(term);
    const matchesRole = m.roles.some(r => r.name.toLowerCase().includes(term));
    return matchesName || matchesRole;
  });

  const getRingColor = (taxa: number) => {
    if (taxa >= 80) return 'text-emerald-500';
    if (taxa >= 60) return 'text-primary';
    if (taxa >= 40) return 'text-amber-500';
    return 'text-rose-500';
  };

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

  return (
    <div className="space-y-6 font-hanken">
      {/* Hero / Header Bento Box */}
      <div className="relative overflow-hidden rounded-[4px] bg-surface border border-border-custom p-6 md:p-8">
        <div className="absolute -right-16 -top-16 w-64 h-64 bg-primary/5 rounded-full blur-3xl pointer-events-none" />
        <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div>
            <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-[4px] bg-primary/10 border border-primary/20 text-primary text-[10px] font-bold uppercase tracking-wider mb-3">
              👥 Equipe de Voluntários
            </span>
            <h1 className="text-2xl md:text-3xl font-extrabold tracking-tight text-text-main">
              Nossa <span className="text-primary font-black">Equipe</span>
            </h1>
            <p className="text-text-muted text-xs md:text-sm mt-1 max-w-xl leading-relaxed">
              Consulte a lista de membros, instrumentistas, vocalistas e técnicos de som. Monitore as taxas de presença e escalas.
            </p>
          </div>

          <div className="flex items-center gap-4 bg-bg-dark/40 border border-border-custom rounded-[4px] p-4 shrink-0 shadow-xs">
            <div className="w-10 h-10 rounded-[4px] bg-primary/10 flex items-center justify-center text-primary">
              <Users className="w-5 h-5" />
            </div>
            <div>
              <div className="text-2xl font-black text-text-main leading-none">
                {members.length}
              </div>
              <div className="text-[9px] font-black uppercase tracking-wider text-text-muted mt-1">
                Membros Ativos
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Caixa Bento de Busca e Filtros */}
      <div className="bg-surface border border-border-custom rounded-[4px] p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 select-none">
        {/* Campo de Busca Principal */}
        <div className="relative flex-1 w-full">
          <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted w-5 h-5" />
          <input 
            type="text" 
            placeholder="Buscar membros por nome ou instrumento..." 
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full h-11 pl-12 pr-4 bg-bg-dark border border-border-custom rounded-[4px] text-sm focus:outline-none focus:border-primary text-text-main placeholder-text-muted/65 leading-relaxed"
          />
        </div>

        {/* Botões de Ordenação */}
        {isAdmin && (
          <div className="flex items-center gap-2 overflow-x-auto shrink-0">
            <span className="text-[10px] font-black text-text-muted uppercase tracking-wider mr-2 whitespace-nowrap">Ordenar por:</span>
            
            <button 
              onClick={() => setSort('name')}
              className={`h-9 px-4 rounded-[4px] text-xs font-bold whitespace-nowrap transition-all cursor-pointer border ${
                sort === 'name' 
                  ? 'bg-primary/10 border-primary/30 text-primary' 
                  : 'bg-bg-dark text-text-muted border-border-custom hover:text-text-main'
              }`}
            >
              Nome
            </button>
            
            <button 
              onClick={() => setSort('taxa')}
              className={`h-9 px-4 rounded-[4px] text-xs font-bold whitespace-nowrap transition-all cursor-pointer border ${
                sort === 'taxa' 
                  ? 'bg-primary/10 border-primary/30 text-primary' 
                  : 'bg-bg-dark text-text-muted border-border-custom hover:text-text-main'
              }`}
            >
              Presença
            </button>

            <button 
              onClick={() => setSort('escalas')}
              className={`h-9 px-4 rounded-[4px] text-xs font-bold whitespace-nowrap transition-all cursor-pointer border ${
                sort === 'escalas' 
                  ? 'bg-primary/10 border-primary/30 text-primary' 
                  : 'bg-bg-dark text-text-muted border-border-custom hover:text-text-main'
              }`}
            >
              Escalas
            </button>
          </div>
        )}
      </div>

      {/* Grid de Membros */}
      {loading ? (
        <div className="min-h-[200px] flex items-center justify-center">
          <div className="w-8 h-8 rounded-full border-2 border-primary/20 border-t-primary animate-spin" />
        </div>
      ) : error ? (
        <div className="border border-red-500/20 bg-red-500/5 p-4 rounded-[4px] text-xs font-bold text-red-400 flex items-start gap-2 max-w-xl">
          <AlertTriangle className="w-4 h-4 shrink-0 mt-0.5" />
          <span>{error}</span>
        </div>
      ) : filteredMembers.length === 0 ? (
        <div className="border border-dashed border-border-custom bg-surface/10 rounded-[4px] p-12 text-center">
          <Users className="w-10 h-10 text-text-muted/40 mx-auto mb-3" />
          <h3 className="text-sm font-extrabold text-text-main mb-1">Nenhum voluntário encontrado</h3>
          <p className="text-xs text-text-muted max-w-xs mx-auto leading-relaxed">
            Não encontramos membros ativos correspondentes ao termo digitado.
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {filteredMembers.map((member) => {
            const initial = member.name.charAt(0).toUpperCase();
            const hasPhone = !!member.phone;
            
            return (
              <div 
                key={member.id}
                className="bg-surface border border-border-custom rounded-[4px] p-5 flex flex-col justify-between relative overflow-hidden group hover:border-primary/45 transition-all duration-200 active:scale-[0.99]"
              >
                <div>
                  {/* Cabeçalho do Card */}
                  <div className="flex items-start justify-between gap-4 mb-4 relative z-10">
                    {/* Avatar */}
                    <div className="relative">
                      <div className="w-12 h-12 rounded-[4px] border border-border-custom flex items-center justify-center shrink-0 overflow-hidden relative bg-bg-dark shadow-xs">
                        {member.avatar && !member.avatar.includes('ui-avatars.com') ? (
                          <img 
                            src={member.avatar} 
                            alt={member.name} 
                            className="w-full h-full object-cover"
                            onError={(e) => {
                              (e.target as HTMLImageElement).style.display = 'none';
                            }}
                          />
                        ) : (
                          <div className={`absolute inset-0 bg-gradient-to-br ${getAvatarGradient(member.name)} flex items-center justify-center text-white font-extrabold text-base`}>
                            {initial}
                          </div>
                        )}
                      </div>
                      
                      {member.role === 'admin' && (
                        <span className="absolute -bottom-1 -right-1 bg-altar-gold text-bg-dark text-[8px] font-black px-1.5 py-0.5 rounded-[2px] border border-surface shadow-xs uppercase tracking-wider leading-none">
                          ADM
                        </span>
                      )}
                    </div>

                    {/* Ações Rápidas (WhatsApp / Perfil) */}
                    <div className="flex items-center gap-1.5">
                      {hasPhone && (
                        <a 
                          href={`https://wa.me/55${member.phone.replace(/\D/g, '')}`} 
                          target="_blank" 
                          rel="noopener noreferrer"
                          className="w-8 h-8 bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 rounded-[4px] flex items-center justify-center transition-all hover:bg-emerald-500/20 active:scale-90" 
                          title="Falar no WhatsApp"
                        >
                          <MessageSquare className="w-3.5 h-3.5 fill-emerald-500/5" />
                        </a>
                      )}
                      
                      <a 
                        href={`../admin/perfil.php?id=${member.id}`} 
                        className="w-8 h-8 bg-bg-dark hover:bg-surface-variant/20 border border-border-custom text-text-muted hover:text-text-main rounded-[4px] flex items-center justify-center transition-all"
                        title="Ver Perfil Completo"
                      >
                        <User className="w-3.5 h-3.5" />
                      </a>
                    </div>
                  </div>

                  {/* Informações */}
                  <div className="space-y-1.5">
                    <h3 className="text-xs font-black text-text-main leading-tight truncate group-hover:text-primary transition-colors max-w-[200px]" title={member.name}>
                      {member.name}
                    </h3>
                    
                    <div className="flex items-center gap-1.5 text-[10px] text-text-muted font-bold leading-none font-body">
                      <Mail className="w-3 h-3 text-text-muted/65" />
                      <span className="truncate max-w-[170px]" title={member.email}>{member.email}</span>
                    </div>

                    {hasPhone && (
                      <div className="flex items-center gap-1.5 text-[10px] text-text-muted font-bold leading-none font-body mt-1">
                        <Phone className="w-3 h-3 text-text-muted/65" />
                        <span>{member.phone}</span>
                      </div>
                    )}
                  </div>
                </div>

                {/* Instrumentos & Estatísticas */}
                <div className="mt-5 pt-4 border-t border-border-custom flex items-center justify-between gap-4">
                  {/* Instrumentos */}
                  <div className="flex flex-wrap gap-1 max-w-[70%]">
                    {member.roles && member.roles.length > 0 ? (
                      member.roles.slice(0, 2).map((role) => (
                        <span 
                          key={role.id}
                          className="inline-flex items-center gap-1 px-2 py-0.5 rounded-[4px] bg-bg-dark border border-border-custom text-[8px] font-black uppercase tracking-wider text-text-muted"
                        >
                          <Compass className="w-2.5 h-2.5 text-primary shrink-0" />
                          <span>{role.name}</span>
                        </span>
                      ))
                    ) : (
                      <span className="text-[10px] font-semibold text-text-muted/50 italic leading-none block">Sem função</span>
                    )}

                    {member.roles && member.roles.length > 2 && (
                      <span className="inline-flex items-center px-1.5 py-0.5 rounded-[4px] bg-bg-dark border border-border-custom text-[8px] font-black text-text-muted" title="Mais instrumentos">
                        +{member.roles.length - 2}
                      </span>
                    )}
                  </div>

                  {/* Círculo de Presença SVG (Líderes apenas) */}
                  {isAdmin && member.taxa !== null && (
                    <div className="flex items-center gap-2 shrink-0 select-none">
                      <div className="relative w-8 h-8 flex items-center justify-center" title={`Presença em escalas passadas: ${member.taxa}%`}>
                        <svg className="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                          <path className="text-bg-dark border border-border-custom stroke-border-custom" strokeWidth="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                          <path className={`${getRingColor(member.taxa)} transition-all duration-500`} strokeDasharray={`${member.taxa}, 100`} strokeWidth="3.5" strokeLinecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <span className="absolute text-[8px] font-black text-text-main">{member.taxa}%</span>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* FAB para adicionar novo membro (Admin Only) */}
      {isAdmin && (
        <a 
          href="../admin/perfil.php?new=1" 
          className="fixed bottom-8 right-8 w-12 h-12 bg-primary text-bg-dark rounded-[4px] flex items-center justify-center shadow-lg hover:scale-105 active:scale-95 transition-all duration-200 z-30" 
          title="Adicionar Novo Voluntário"
        >
          <Plus className="w-5 h-5 text-bg-dark" strokeWidth={3} />
        </a>
      )}
    </div>
  );
};
