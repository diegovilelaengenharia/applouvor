import React from 'react';
import { 
  LayoutDashboard, 
  Music, 
  Calendar, 
  BookOpen, 
  Users, 
  Bell, 
  Heart, 
  Clock, 
  LogOut,
  ExternalLink,
  ChevronLeft
} from 'lucide-react';
import { useAuth } from '../../context/AuthContext';

interface SidebarProps {
  isOpen: boolean;
  setIsOpen: (isOpen: boolean) => void;
  currentView: string;
  onViewChange: (view: string) => void;
}

export const Sidebar: React.FC<SidebarProps> = ({ isOpen, setIsOpen, currentView, onViewChange }) => {
  const { logout, user } = useAuth();

  const menuItems = [
    { id: 'dashboard', name: 'Dashboard', icon: LayoutDashboard },
    { id: 'escalas', name: 'Escalas', icon: Calendar },
    { id: 'repertorio', name: 'Repertório', icon: Music },
    { id: 'devocionais', name: 'Devocionais', icon: BookOpen },
    { id: 'avisos', name: 'Avisos', icon: Bell },
    { id: 'sugestoes', name: 'Sugestões', icon: Heart },
    { id: 'metronomo', name: 'Metrônomo', icon: Clock },
    { id: 'membros', name: 'Membros', icon: Users },
  ];

  return (
    <>
      {/* Overlay para mobile */}
      {isOpen && (
        <div 
          className="fixed inset-0 z-40 bg-black/60 backdrop-blur-xs md:hidden"
          onClick={() => setIsOpen(false)}
        />
      )}

      <aside 
        className={`fixed top-0 bottom-0 left-0 z-50 flex flex-col w-64 bg-bg-dark border-r border-border-custom transition-transform duration-300 md:translate-x-0 ${
          isOpen ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        {/* Header da Sidebar */}
        <div className="h-16 flex items-center justify-between px-6 border-b border-border-custom">
          <div className="flex items-center gap-2">
            <div className="w-2.5 h-2.5 rounded-full bg-primary animate-pulse" />
            <h1 className="text-lg font-black tracking-tight text-text-main font-hanken">
              PIB LOUVOR<span className="text-accent">.</span>
            </h1>
          </div>
          
          <button 
            onClick={() => setIsOpen(false)}
            className="p-1.5 rounded-[4px] border border-border-custom hover:bg-surface text-text-muted hover:text-text-main md:hidden cursor-pointer transition-colors"
          >
            <ChevronLeft className="w-4 h-4" />
          </button>
        </div>

        {/* Informações da Conta do Líder */}
        <div className="p-5 border-b border-border-custom bg-surface/30">
          <div className="flex items-center gap-3">
            <img 
              src={user?.photo} 
              alt={user?.name}
              className="w-10 h-10 rounded-[4px] border border-border-custom object-cover shadow-sm bg-surface"
              onError={(e) => {
                const target = e.target as HTMLImageElement;
                target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || 'M')}&background=2e7eed&color=fff`;
              }}
            />
            <div className="overflow-hidden">
              <h4 className="text-xs font-extrabold text-text-main font-hanken truncate">
                {user?.name}
              </h4>
              <span className="text-[10px] font-bold text-accent uppercase tracking-wider">
                {user?.role === 'admin' ? 'Líder / Admin' : 'Músico'}
              </span>
            </div>
          </div>
        </div>

        {/* Links de Navegação */}
        <nav className="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto select-none">
          <span className="block px-3 mb-2 text-[9px] font-black text-text-muted uppercase tracking-widest">
            Ministério
          </span>
          {menuItems.map((item) => {
            const Icon = item.icon;
            const active = item.id === currentView;
            return (
              <button
                key={item.id}
                onClick={() => {
                  onViewChange(item.id);
                  setIsOpen(false);
                }}
                className={`w-full flex items-center justify-between px-3 py-2.5 rounded-[4px] text-xs font-bold transition-all duration-200 group cursor-pointer ${
                  active 
                    ? 'bg-primary/10 text-primary border border-primary/20 shadow-xs' 
                    : 'text-text-muted hover:bg-surface hover:text-text-main border border-transparent'
                }`}
              >
                <div className="flex items-center gap-2.5">
                  <Icon className={`w-4 h-4 transition-transform duration-200 group-hover:scale-110 ${
                    active ? 'text-primary' : 'text-text-muted group-hover:text-text-main'
                  }`} />
                  <span>{item.name}</span>
                </div>
              </button>
            );
          })}
        </nav>

        {/* Footer da Sidebar / Ações Rápidas */}
        <div className="p-4 border-t border-border-custom space-y-2 bg-surface/20">
          <a
            href="../index.php"
            className="flex items-center gap-2.5 px-3 py-2 text-xs font-bold text-text-muted hover:text-text-main transition-colors group"
          >
            <ExternalLink className="w-3.5 h-3.5 text-text-muted group-hover:text-text-main" />
            <span>Voltar ao Portal</span>
          </a>
          
          <button
            onClick={logout}
            className="w-full flex items-center gap-2.5 px-3 py-2.5 bg-red-500/5 hover:bg-red-500/10 border border-red-500/10 hover:border-red-500/20 text-red-500 rounded-[4px] text-xs font-bold transition-all cursor-pointer active:scale-[0.98]"
          >
            <LogOut className="w-4 h-4" />
            <span>Encerrar Sessão</span>
          </button>
        </div>
      </aside>
    </>
  );
};
