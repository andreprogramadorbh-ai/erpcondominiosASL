/**
 * =====================================================
 * AUTH GUARD - Proteção de Páginas HTML
 * =====================================================
 * Verifica se o usuário está logado antes de exibir a página
 */

(function() {
    'use strict';
    
    // Páginas que não precisam de autenticação
    const paginasPublicas = ['login.html', 'login_morador.html', 'index.html'];
    
    // Obter nome da página atual
    const paginaAtual = window.location.pathname.split('/').pop() || 'index.html';
    
    // Se for página pública, não verificar
    if (paginasPublicas.includes(paginaAtual)) {
        return;
    }
    
    // Flag para evitar múltiplas verificações simultâneas
    let verificandoSessao = false;
    
    // Função para verificar sessão
    function verificarSessao(mostrarAlerta = false) {
        if (verificandoSessao) {
            return;
        }
        
        verificandoSessao = true;
        
        fetch('../api/api_verificar_sessao.php', {
            method: 'GET',
            cache: 'no-cache',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Sessão inválida');
            }
            return response.json();
        })
        .then(data => {
            verificandoSessao = false;
            
            if (!data.sucesso || !data.logado) {
                // Usuário não está logado - redirecionar para login
                console.log('Sessão inválida, redirecionando para login...');
                sessionStorage.clear();
                window.location.replace('login.html');
            } else {
                // Usuário logado - armazenar dados no sessionStorage
                sessionStorage.setItem('usuario_nome', data.dados.nome);
                sessionStorage.setItem('usuario_email', data.dados.email);
                sessionStorage.setItem('usuario_permissao', data.dados.permissao);
                sessionStorage.setItem('sessao_verificada', 'true');
                
                // Disparar evento personalizado informando que usuário está autenticado
                const event = new CustomEvent('usuarioAutenticado', {
                    detail: data.dados
                });
                document.dispatchEvent(event);
            }
        })
        .catch(error => {
            verificandoSessao = false;
            console.error('Erro ao verificar sessão:', error);
            
            // Verificar se já tentou antes (evitar loop infinito)
            const tentativas = parseInt(sessionStorage.getItem('tentativas_verificacao') || '0');
            
            if (tentativas < 2) {
                // Tentar novamente
                sessionStorage.setItem('tentativas_verificacao', (tentativas + 1).toString());
                setTimeout(() => verificarSessao(mostrarAlerta), 1000);
            } else {
                // Muitas tentativas falhadas, redirecionar para login
                sessionStorage.clear();
                window.location.replace('login.html');
            }
        });
    }
    
    // Verificar sessão ao carregar a página
    verificarSessao(false);
    
    // Verificar sessão periodicamente (a cada 2 minutos)
    setInterval(function() {
        verificarSessao(true);
    }, 120000); // 120 segundos = 2 minutos
    
    // Resetar contador de tentativas quando houver interação do usuário
    ['click', 'keypress', 'mousemove', 'scroll'].forEach(evento => {
        document.addEventListener(evento, function() {
            sessionStorage.setItem('tentativas_verificacao', '0');
        }, { once: true });
    });
    
})();

