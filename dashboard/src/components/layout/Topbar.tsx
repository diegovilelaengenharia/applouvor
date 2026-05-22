import React, { useState } from 'react';
import { Sun, Moon, Menu, Bell, User, Settings, LogOut, ChevronDown } from 'lucide-react';
import { useAuth } from '../../context/AuthContext';
import { useTheme } from '../../context/ThemeContext';

interface TopbarProps {
  onMenuClick: () => void;
}

export const Topbar: React.FC<TopbarProps> = ({ onMenuClick }) => {
  const { user, logout } = useAuth();
  const { theme, toggleTheme } = useTheme();
  const [dropdownOpen, setDropdownOpen] = useState(false);

  return (
    <header className="h-16 bg-surface border-b border-border-custom px-6 flex items-center justify-between sticky top-0 z-30 shadow-xs">
      
      {/* Esquerda: Botão Responsivo + Título */}
      <div className="flex items-center gap-4">
        <button
          onClick={onMenuClick}
          className="p-2 rounded-[4px] border border-border-custom hover:bg-bg-dark text-text-muted hover:text-text-main md:hidden cursor-pointer transition-colors"
        >
          <Menu className="w-5 h-5" />
        </button>
        
        <div>
          <h2 className="text-sm font-extrabold text-text-main font-hanken tracking-tight flex items-center gap-2">
            Painel do Líder
            <span className="hidden sm:inline-block text-[10px] font-bold bg-primary/10 border border-primary/20 text-primary px-2 py-0.5 rounded-[4px] uppercase tracking-wider">
              SPA React
            </span>
          </h2>
          <p className="text-[10px] font-semibold text-text-muted hidden sm:block">
            {user?.salutation ? `${user.salutation}, ${user.name}` : 'Bem-vindo de volta'}
          </p>
        </div>
      </div>

      {/* Direita: Ações Rápidas */}
      <div className="flex items-center gap-3">
        
        {/* Toggle Dark/Light Mode */}
        <button
          onClick={toggleTheme}
          className="p-2 rounded-[4px] border border-border-custom hover:bg-bg-dark text-text-muted hover:text-text-main cursor-pointer transition-colors relative"
          title="Alternar Tema"
        >
          {theme === 'dark' ? (
            <Sun className="w-4 h-4 text-accent animate-spin-slow" />
          ) : (
            <Moon className="w-4 h-4 text-primary" />
          )}
        </button>

        {/* Notificações (Redireciona para Avisos) */}
        <a
          href="../admin/avisos.php"
          className="p-2 rounded-[4px] border border-border-custom hover:bg-bg-dark text-text-muted hover:text-text-main transition-colors relative"
          title="Ver Avisos"
        >
          <Bell className="w-4 h-4" />
          <span className="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full animate-ping" />
          <span className="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full" />
        </a>

        {/* Divisor */}
        <div className="h-6 w-px bg-border-custom" />

        {/* Dropdown de Usuário */}
        <div className="relative">
          <button
            onClick={() => setDropdownOpen(!dropdownOpen)}
            className="flex items-center gap-2.5 p-1 rounded-[4px] hover:bg-bg-dark transition-all cursor-pointer select-none text-text-muted hover:text-text-main border border-transparent hover:border-border-custom"
          >
            <img
              src={user?.photo}
              alt={user?.name}
              className="w-7 h-7 rounded-[4px] border border-border-custom object-cover"
              onError={(e) => {
                const target = e.target as HTMLImageElement;
                target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || 'M')}&background=2e7eed&color=fff`;
              }}
            />
            <span className="text-xs font-bold hidden md:block truncate max-w-[100px]">
              {user?.name}
            </span>
            <ChevronDown className="w-3.5 h-3.5 opacity-70" />
          </button>

          {/* Conteúdo do Dropdown */}
          {dropdownOpen && (
            <>
              {/* Overlay invisível para fechar o dropdown */}
              <div 
                className="fixed inset-0 z-40" 
                onClick={() => setDropdownOpen(false)}
              />
              
              <div className="absolute right-0 mt-2 w-48 bg-surface border border-border-custom rounded-[4px] shadow-lg py-1 z-50 animate-in fade-in slide-in-from-top-2 duration-150">
                <div className="px-4 py-2 border-b border-border-custom">
                  <p className="text-[10px] font-black text-text-muted uppercase tracking-widest">Líder Conectado</p>
                  <p className="text-xs font-bold text-text-main truncate">{user?.name}</p>
                </div>

                <a
                  href="../admin/perfil.php"
                  className="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-text-muted hover:text-text-main hover:bg-bg-dark transition-colors"
                >
                  <User className="w-3.5 h-3.5" />
                  <span>Meu Perfil</span>
                </a>

                <a
                  href="../admin/indisponibilidade.php"
                  className="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-text-muted hover:text-text-main hover:bg-bg-dark transition-colors"
                >
                  <Settings className="w-3.5 h-3.5" />
                  <span>Configurações</span>
                </a>

                <div className="border-t border-border-custom my-1" />

                <button
                  onClick={logout}
                  className="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-red-500 hover:bg-red-500/5 transition-colors cursor-pointer text-left"
                >
                  <LogOut className="w-3.5 h-3.5" />
                  <span>Encerrar Sessão</span>
                </button>
              </div>
            </>
          )}
        </div>

      </div>
    </header>
  );
};
