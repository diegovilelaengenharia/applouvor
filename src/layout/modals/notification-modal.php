<?php
// src/layout/modals/notification-modal.php
?>
<!-- MODAL DETALHES NOTIFICAÇÃO -->
<div id="notificationDetailModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 3050; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div style="background: var(--bg-surface); border-radius: 16px; width: 90%; max-width: 500px; box-shadow: var(--shadow-lg); animation: fadeInUp 0.2s cubic-bezier(0.16, 1, 0.3, 1); overflow: hidden; display: flex; flex-direction: column;">
        <div style="padding: 16px 20px; border-bottom: 0px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: var(--bg-surface);">
            <h3 style="margin: 0; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Notificação</h3>
            <button onclick="closeNotificationDetail()" style="background: transparent; border: none; cursor: pointer; color: var(--text-muted); display: flex;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div style="padding: 0 24px 24px 24px;">
            <div style="display: flex; gap: 16px; margin-bottom: 20px; align-items: flex-start;">
                <div id="notifDetailIcon" style="width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"></div>
                <div>
                    <h4 id="notifDetailTitle" style="margin: 0 0 6px 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main); line-height: 1.3;"></h4>
                    <span id="notifDetailDate" style="font-size: 0.85rem; color: var(--text-muted);"></span>
                </div>
            </div>
            <div style="background: var(--bg-body); padding: 16px; border-radius: 12px; margin-bottom: 20px;">
                <div id="notifDetailMessage" style="font-size: 0.95rem; line-height: 1.6; color: var(--text-main); white-space: pre-wrap;"></div>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                 <button onclick="closeNotificationDetail()" style="padding: 12px 24px; border: 1px solid var(--border-color); background: transparent; border-radius: 10px; cursor: pointer; color: var(--text-main); font-weight: 600;">Fechar</button>
                 <a id="notifDetailLink" href="#" style="padding: 12px 24px; background: var(--primary); color: white; border-radius: 10px; text-decoration: none; font-weight: 600; display: none; align-items: center; gap: 8px; box-shadow: var(--shadow-sm);">
                    Ver Completo <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                 </a>
            </div>
        </div>
    </div>
</div>
