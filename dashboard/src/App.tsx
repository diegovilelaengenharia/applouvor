import { useState } from 'react';
import { AuthProvider } from './context/AuthContext';
import { ThemeProvider } from './context/ThemeContext';
import { Sidebar } from './components/layout/Sidebar';
import { Topbar } from './components/layout/Topbar';
import { DashboardView } from './views/DashboardView';
import { EscalasView } from './views/EscalasView';
import { RepertorioView } from './views/RepertorioView';
import { DevocionaisView } from './views/DevocionaisView';
import { AvisosView } from './views/AvisosView';
import { SugestoesView } from './views/SugestoesView';
import { MetronomoView } from './views/MetronomoView';
import { MembrosView } from './views/MembrosView';

function AppContent() {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [currentView, setCurrentView] = useState('dashboard');

  const renderView = () => {
    switch (currentView) {
      case 'dashboard':
        return <DashboardView />;
      case 'escalas':
        return <EscalasView />;
      case 'repertorio':
        return <RepertorioView />;
      case 'devocionais':
        return <DevocionaisView />;
      case 'avisos':
        return <AvisosView />;
      case 'sugestoes':
        return <SugestoesView />;
      case 'metronomo':
        return <MetronomoView />;
      case 'membros':
        return <MembrosView />;
      default:
        return (
          <div className="min-h-[400px] flex flex-col items-center justify-center text-center p-6 border border-border-custom bg-surface/10 rounded-[4px] animate-fade-in">
            <div className="w-12 h-12 rounded-full bg-primary/5 flex items-center justify-center mb-4 text-primary animate-pulse">
              <span className="w-2 h-2 rounded-full bg-primary" />
            </div>
            <h2 className="text-base font-extrabold text-text-main font-hanken capitalize">
              Tela de {currentView}
            </h2>
            <p className="text-xs font-bold text-text-muted font-hanken mt-1.5 max-w-xs leading-relaxed">
              Esta tela está sendo integrada ao banco de dados e estará disponível instantaneamente em instantes!
            </p>
          </div>
        );
    }
  };

  return (
    <div className="min-h-screen bg-bg-dark flex">
      {/* Menu Lateral de Navegação (Sidebar) */}
      <Sidebar 
        isOpen={sidebarOpen} 
        setIsOpen={setSidebarOpen} 
        currentView={currentView}
        onViewChange={setCurrentView}
      />

      {/* Área Principal de Conteúdo */}
      <div className="flex-1 flex flex-col min-h-screen md:pl-64 transition-all duration-300">
        {/* Barra Superior (Topbar) */}
        <Topbar onMenuClick={() => setSidebarOpen(true)} />

        {/* Corpo Principal da SPA */}
        <main className="flex-1 p-6 md:p-8 max-w-6xl w-full mx-auto">
          {renderView()}
        </main>

        {/* Rodapé Administrativo */}
        <footer className="py-6 px-8 border-t border-border-custom bg-surface/20 flex flex-col sm:flex-row items-center justify-between gap-4 text-[10px] font-bold text-text-muted uppercase tracking-wider select-none">
          <div className="flex items-center gap-2">
            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500" />
            <span>Sessão Segura ativa via Hostinger</span>
          </div>
          <div className="flex items-center gap-4">
            <span>PIB Oliveira © 2026</span>
            <span className="text-accent">Milestone 1 Concluído</span>
          </div>
        </footer>
      </div>
    </div>
  );
}

function App() {
  return (
    <ThemeProvider>
      <AuthProvider>
        <AppContent />
      </AuthProvider>
    </ThemeProvider>
  );
}

export default App;
