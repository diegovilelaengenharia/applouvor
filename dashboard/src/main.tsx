import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App.tsx';
import './index.css';

ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);

// Registrar Service Worker para suporte PWA Offline no escopo do dashboard
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/applouvor/dashboard/sw.js', { scope: '/applouvor/dashboard/' })
      .then(registration => {
        console.log('Service Worker do Dashboard registrado com sucesso:', registration.scope);
      })
      .catch(error => {
        console.error('Falha ao registrar o Service Worker do Dashboard:', error);
      });
  });
}

