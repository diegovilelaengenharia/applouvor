import React, { createContext, useContext, useState, useEffect } from 'react';

export interface User {
  name: string;
  role: string;
  photo: string;
  salutation: string;
}

interface AuthContextType {
  user: User | null;
  loading: boolean;
  logout: () => void;
  refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  const fetchUserData = async () => {
    try {
      // Como rodamos em /dashboard/, a API está em ../api/admin/... em produção
      // No ambiente de desenvolvimento, o proxy do Vite redirecionará /api/admin para o backend
      const response = await fetch('/api/admin/dashboard_data_api.php');
      
      if (response.status === 401) {
        // Sessão expirou ou usuário não está logado
        redirectToLogin();
        return;
      }

      if (!response.ok) {
        throw new Error('Falha ao carregar dados do usuário');
      }

      const res = await response.json();
      
      if (res.success && res.data) {
        setUser({
          name: res.data.userName,
          role: res.data.userRole,
          photo: res.data.userPhoto,
          salutation: res.data.salutation,
        });
      } else {
        // Redireciona em caso de falha de autenticação lógica
        redirectToLogin();
      }
    } catch (error) {
      console.error('Erro na autenticação:', error);
      // Fallback em caso de erro grave (ex: offline temporário) ou redireciona
      // No ambiente de desenvolvimento sem o PHP local ativo, podemos deixar um usuário mockado se em dev
      if (import.meta.env.DEV) {
        setUser({
          name: 'Líder (Mock Dev)',
          role: 'admin',
          photo: 'https://ui-avatars.com/api/?name=Lider+Mock&background=2e7eed&color=fff',
          salutation: 'Olá, em desenvolvimento local',
        });
      } else {
        redirectToLogin();
      }
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchUserData();
  }, []);

  const redirectToLogin = () => {
    if (import.meta.env.DEV) {
      window.location.href = 'http://localhost:8080/index.php';
      return;
    }
    const pathname = window.location.pathname;
    if (pathname.includes('/dist/')) {
      window.location.href = '../../index.php';
    } else {
      window.location.href = '../index.php';
    }
  };

  const logout = () => {
    if (import.meta.env.DEV) {
      window.location.href = 'http://localhost:8080/logout.php';
      return;
    }
    const pathname = window.location.pathname;
    if (pathname.includes('/dist/')) {
      window.location.href = '../../logout.php';
    } else {
      window.location.href = '../logout.php';
    }
  };

  const refreshUser = async () => {
    await fetchUserData();
  };

  return (
    <AuthContext.Provider value={{ user, loading, logout, refreshUser }}>
      {loading ? (
        <div className="min-h-screen bg-bg-dark flex flex-col items-center justify-center select-none">
          <div className="flex flex-col items-center gap-4">
            <div className="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
            <h1 className="text-sm font-bold text-slate-400 font-hanken tracking-wider uppercase">
              Carregando Templo Digital<span className="text-accent">.</span>
            </h1>
          </div>
        </div>
      ) : (
        children
      )}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth deve ser usado dentro de um AuthProvider');
  }
  return context;
};
