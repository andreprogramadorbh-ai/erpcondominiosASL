/**
 * User Profile Sidebar Injector
 * Injeta a seção de perfil do usuário na sidebar e os estilos necessários
 */
(function () {
    'use strict';

    // 1. Injetar CSS
    const css = `
        /* User Profile Section Styles */
        .user-profile-section { background: rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; border: 1px solid rgba(255, 255, 255, 0.2); margin-left: 1rem; margin-right: 1rem; }
        .user-profile-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
        .user-avatar { width: 60px; height: 60px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.8rem; font-weight: bold; flex-shrink: 0; }
        .user-info { flex: 1; overflow: hidden; }
        .user-name { color: #fff; font-size: 1.1rem; font-weight: 600; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-function { color: #cbd5e1; font-size: 0.9rem; margin: 0.25rem 0 0 0; }
        .user-email { color: #94a3b8; font-size: 0.85rem; margin: 0.25rem 0 0 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .session-info { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255, 255, 255, 0.2); }
        .session-item { }
        .session-label { color: #94a3b8; font-size: 0.8rem; margin-bottom: 0.25rem; }
        .session-value { color: #fff; font-size: 0.95rem; font-weight: 600; }
        .session-timer { color: #10b981; font-weight: 700; }
        .session-timer.warning { color: #f97316; }
        .session-timer.critical { color: #ef4444; }
    `;

    const style = document.createElement('style');
    style.textContent = css;
    document.head.appendChild(style);

    // 2. Injetar HTML na Sidebar
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const navMenu = sidebar ? sidebar.querySelector('.nav-menu') : null;

        if (sidebar && navMenu && !document.getElementById('userProfileSection')) {
            const profileDiv = document.createElement('div');
            profileDiv.className = 'user-profile-section';
            profileDiv.id = 'userProfileSection';
            profileDiv.style.display = 'none'; // Será mostrado pelo user-display.js

            profileDiv.innerHTML = `
                <div class="user-profile-header">
                    <div class="user-avatar" id="userAvatar">-</div>
                    <div class="user-info">
                        <p class="user-name" id="userName">Carregando...</p>
                        <p class="user-function" id="userFunction">-</p>
                        <p class="user-email" id="userEmail">-</p>
                    </div>
                </div>
                <div class="session-info">
                    <div class="session-item">
                        <div class="session-label">Tempo de Sessão</div>
                        <div class="session-value session-timer" id="sessionTimer">00:00:00</div>
                    </div>
                    <div class="session-item">
                        <div class="session-label">Status</div>
                        <div class="session-value" id="sessionStatus"><i class="fas fa-circle" style="color: #10b981; font-size: 0.6rem;"></i> Ativo</div>
                    </div>
                </div>
            `;

            // Inserir antes do menu de navegação
            sidebar.insertBefore(profileDiv, navMenu);
        }
    });

})();
