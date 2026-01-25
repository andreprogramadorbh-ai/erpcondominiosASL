(function() {
    'use strict';
    
    const CONFIG = {
        apiUrl: '../api/api_usuario_logado.php',
        updateInterval: 1000,
        warningThreshold: 300,
        autoRenewThreshold: 600,
        enableAutoRenew: true
    };
    
    let intervaloAtualizacao = null;
    
    /**
     * Inicializar o script
     */
    function inicializar() {
        console.log('🔧 User Display inicializado');
        
        carregarDadosUsuario();
        intervaloAtualizacao = setInterval(carregarDadosUsuario, CONFIG.updateInterval);
        
        document.addEventListener('visibilitychange', handleVisibilidadeChange);
        window.addEventListener('beforeunload', limpar);
        
        console.log('✅ User Display pronto');
    }
    
    /**
     * Carregar dados do usuário logado
     */
    function carregarDadosUsuario() {
        fetch(CONFIG.apiUrl, {
            method: 'GET',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso && data.logado) {
                atualizarExibicao(data);
                verificarAvisos(data.sessao.tempo_restante);
                
                if (CONFIG.enableAutoRenew && data.sessao.tempo_restante < CONFIG.autoRenewThreshold) {
                    renovarSessaoAutomaticamente();
                }
            } else {
                handleSessaoExpirada();
            }
        })
        .catch(error => console.warn('Erro ao carregar dados da sessão:', error));
    }
    
    /**
     * Atualizar exibição na interface
     */
    function atualizarExibicao(data) {
        atualizarPerfilUsuario(data.usuario);
        atualizarTempoSessao(data.sessao);
        atualizarStatusVisual(data.sessao.tempo_restante);
    }
    
    /**
     * Atualizar perfil do usuário
     */
    function atualizarPerfilUsuario(usuario) {
        const section = document.getElementById('userProfileSection');
        if (!section) return;
        
        section.style.display = 'block';
        
        const avatarEl = document.getElementById('userAvatar');
        if (avatarEl) {
            const inicial = usuario.nome.charAt(0).toUpperCase();
            avatarEl.textContent = inicial;
        }

        const nameEl = document.getElementById('userName');
        if (nameEl) nameEl.textContent = usuario.nome;

        const funcEl = document.getElementById('userFunction');
        if (funcEl) funcEl.textContent = usuario.funcao || usuario.permissao || 'Usuário';

        const emailEl = document.getElementById('userEmail');
        if (emailEl) emailEl.textContent = usuario.email || '-';
    }
    
    /**
     * Atualizar tempo de sessão
     */
    function atualizarTempoSessao(sessao) {
        const tempoElement = document.getElementById('sessionTimer');
        if (!tempoElement) return;
        
        const tempoRestante = sessao.tempo_restante_formatado || formatarTempo(sessao.tempo_restante);
        tempoElement.textContent = tempoRestante;
        
        // Atualizar classe de cor
        tempoElement.classList.remove('warning', 'critical');
        if (sessao.tempo_restante < CONFIG.warningThreshold) {
            tempoElement.classList.add('critical');
        } else if (sessao.tempo_restante < 1800) {
            tempoElement.classList.add('warning');
        }
    }
    
    /**
     * Atualizar status visual
     */
    function atualizarStatusVisual(tempoRestante) {
        const section = document.getElementById('userProfileSection');
        if (!section) return;
        
        section.style.borderColor = 'rgba(255, 255, 255, 0.2)';
        
        if (tempoRestante < CONFIG.warningThreshold) {
            section.style.borderColor = 'rgba(239, 68, 68, 0.3)';
            section.style.background = 'rgba(239, 68, 68, 0.1)';
        } else if (tempoRestante < 1800) {
            section.style.borderColor = 'rgba(249, 115, 22, 0.3)';
            section.style.background = 'rgba(249, 115, 22, 0.1)';
        } else {
            section.style.borderColor = 'rgba(255, 255, 255, 0.2)';
            section.style.background = 'rgba(255, 255, 255, 0.1)';
        }
    }
    
    /**
     * Verificar avisos
     */
    function verificarAvisos(tempoRestante) {
        if (tempoRestante < CONFIG.warningThreshold && tempoRestante > CONFIG.warningThreshold - 60) {
            mostrarNotificacao('warning', 'Sua sessão expirará em 5 minutos');
        }
        
        if (tempoRestante < 60 && tempoRestante > 50) {
            mostrarNotificacao('critical', 'Sua sessão expirará em menos de 1 minuto!');
        }
    }
    
    /**
     * Mostrar notificação
     */
    function mostrarNotificacao(tipo, mensagem) {
        if (document.querySelector(`[data-notif-sessao="${tipo}"]`)) {
            return;
        }
        
        const notifHTML = `
            <div class="session-notification session-notification-${tipo}" data-notif-sessao="${tipo}" style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: #fff;
                padding: 1rem;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                z-index: 9999;
                border-left: 4px solid ${tipo === 'critical' ? '#ef4444' : '#f97316'};
                animation: slideIn 0.3s ease;
            ">
                <i class="fas fa-exclamation-triangle" style="color: ${tipo === 'critical' ? '#ef4444' : '#f97316'}; margin-right: 0.5rem;"></i>
                <span style="color: #1f2937;">${mensagem}</span>
            </div>
        `;
        
        document.body.insertAdjacentHTML('afterbegin', notifHTML);
        
        setTimeout(() => {
            const notif = document.querySelector(`[data-notif-sessao="${tipo}"]`);
            if (notif) notif.remove();
        }, 8000);
    }
    
    /**
     * Renovar sessão automaticamente
     */
    function renovarSessaoAutomaticamente() {
        fetch(CONFIG.apiUrl + '?acao=renovar', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                console.log('✅ Sessão renovada automaticamente');
                carregarDadosUsuario();
            }
        })
        .catch(error => console.warn('Erro ao renovar sessão:', error));
    }
    
    /**
     * Lidar com mudança de visibilidade
     */
    function handleVisibilidadeChange() {
        if (document.hidden) {
            if (intervaloAtualizacao) clearInterval(intervaloAtualizacao);
        } else {
            carregarDadosUsuario();
            intervaloAtualizacao = setInterval(carregarDadosUsuario, CONFIG.updateInterval);
        }
    }
    
    /**
     * Lidar com sessão expirada
     */
    function handleSessaoExpirada() {
        console.warn('⚠️ Sessão expirada ou inválida');
        // Redirecionar se não estiver na página de login
        if (!window.location.pathname.includes('login.html')) {
             window.location.href = 'login.html';
        }
    }
    
    /**
     * Formatar tempo
     */
    function formatarTempo(segundos) {
        const horas = Math.floor(segundos / 3600);
        const minutos = Math.floor((segundos % 3600) / 60);
        const segs = segundos % 60;
        
        return `${String(horas).padStart(2, '0')}:${String(minutos).padStart(2, '0')}:${String(segs).padStart(2, '0')}`;
    }
    
    /**
     * Limpar recursos
     */
    function limpar() {
        if (intervaloAtualizacao) {
            clearInterval(intervaloAtualizacao);
        }
    }
    
    /**
     * Inicializar
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializar);
    } else {
        inicializar();
    }
})();
