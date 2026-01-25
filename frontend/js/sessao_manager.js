/**
 * =====================================================
 * GERENCIADOR DE SESSÃO AUTOMÁTICO - CORRIGIDO
 * =====================================================
 * 
 * Inclua este arquivo em todas as páginas do frontend:
 * <script src="js/sessao_manager_corrigido.js"></script>
 * 
 * CORREÇÕES IMPLEMENTADAS:
 * - Caminho correto da API (../api/ em vez de ../../api/)
 * - Detecção automática de tipo de usuário
 * - Redirecionamento correto para login apropriado
 * - Compatibilidade com fornecedores e usuários comuns
 */

class SessaoManager {
    constructor() {
        this.intervaloVerificacao = 60000; // Verificar a cada 1 minuto
        this.intervaloRenovacao = 300000; // Renovar a cada 5 minutos
        this.apiBase = '../api/'; // ✅ CORRIGIDO: Caminho correto
        this.timeoutId = null;
        this.renovacaoId = null;
        this.sessaoAtiva = false;
        this.tipoUsuario = this.detectarTipoUsuario(); // ✅ NOVO: Detectar tipo
        
        // Iniciar automaticamente
        this.iniciar();
    }
    
    /**
     * Detectar tipo de usuário baseado na página atual
     */
    detectarTipoUsuario() {
        const caminhoAtual = window.location.pathname;
        
        // Se está em página de fornecedor
        if (caminhoAtual.includes('painel_fornecedor') || 
            caminhoAtual.includes('login_fornecedor')) {
            console.log('[SessaoManager] Tipo de usuário: FORNECEDOR');
            return 'fornecedor';
        }
        
        // Padrão: usuário comum
        console.log('[SessaoManager] Tipo de usuário: COMUM');
        return 'comum';
    }
    
    /**
     * Obter URL de login apropriada
     */
    obterUrlLogin() {
        if (this.tipoUsuario === 'fornecedor') {
            return 'login_fornecedor.html'; // ✅ CORRIGIDO: Para fornecedor
        }
        return 'login.html'; // Para usuário comum
    }
    
    /**
     * Iniciar gerenciador de sessão
     */
    iniciar() {
        console.log('[SessaoManager] Iniciando gerenciador de sessão');
        console.log('[SessaoManager] API Base:', this.apiBase);
        console.log('[SessaoManager] Tipo de usuário:', this.tipoUsuario);
        
        // Verificar sessão imediatamente
        this.verificarSessao();
        
        // Configurar verificação periódica
        this.timeoutId = setInterval(() => {
            this.verificarSessao();
        }, this.intervaloVerificacao);
        
        // Configurar renovação automática
        this.renovacaoId = setInterval(() => {
            this.renovarSessao();
        }, this.intervaloRenovacao);
        
        // Renovar sessão em atividade do usuário
        this.configurarRenovacaoPorAtividade();
    }
    
    /**
     * Parar gerenciador de sessão
     */
    parar() {
        if (this.timeoutId) {
            clearInterval(this.timeoutId);
            this.timeoutId = null;
        }
        
        if (this.renovacaoId) {
            clearInterval(this.renovacaoId);
            this.renovacaoId = null;
        }
        
        console.log('[SessaoManager] Gerenciador parado');
    }
    
    /**
     * Verificar se sessão está ativa
     */
    async verificarSessao() {
        try {
            const response = await fetch(this.apiBase + 'verificar_sessao_completa.php', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            // Verificar se resposta é válida
            if (!response.ok) {
                console.warn('[SessaoManager] Erro HTTP:', response.status);
                this.sessaoAtiva = false;
                return false;
            }
            
            // Tentar fazer parse do JSON
            let data;
            try {
                data = await response.json();
            } catch (parseError) {
                console.error('[SessaoManager] Erro ao fazer parse do JSON:', parseError);
                console.error('[SessaoManager] Resposta:', response.text());
                this.sessaoAtiva = false;
                return false;
            }
            
            if (data.sucesso && data.sessao_ativa) {
                this.sessaoAtiva = true;
                console.log('[SessaoManager] Sessão ativa:', data.usuario.nome);
                console.log('[SessaoManager] Tempo restante:', data.tempo_restante_formatado);
                
                // Atualizar informações do usuário na interface (se houver)
                this.atualizarInterfaceUsuario(data.usuario);
                
                // Avisar se sessão está prestes a expirar (menos de 10 minutos)
                if (data.tempo_restante_segundos < 600) {
                    this.alertarExpiracaoProxima(data.tempo_restante_segundos);
                }
                
                return true;
            } else {
                this.sessaoAtiva = false;
                console.warn('[SessaoManager] Sessão inválida ou expirada');
                this.redirecionarParaLogin();
                return false;
            }
        } catch (error) {
            console.error('[SessaoManager] Erro ao verificar sessão:', error);
            this.sessaoAtiva = false;
            return false;
        }
    }
    
    /**
     * Renovar sessão
     */
    async renovarSessao() {
        if (!this.sessaoAtiva) {
            console.log('[SessaoManager] Sessão inativa, não renovando');
            return false;
        }
        
        try {
            const formData = new FormData();
            formData.append('acao', 'renovar');
            
            const response = await fetch(this.apiBase + 'verificar_sessao_completa.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            
            if (!response.ok) {
                console.warn('[SessaoManager] Erro ao renovar sessão:', response.status);
                return false;
            }
            
            let data;
            try {
                data = await response.json();
            } catch (parseError) {
                console.error('[SessaoManager] Erro ao fazer parse do JSON:', parseError);
                return false;
            }
            
            if (data.sucesso) {
                console.log('[SessaoManager] Sessão renovada com sucesso');
                return true;
            } else {
                console.warn('[SessaoManager] Falha ao renovar sessão');
                return false;
            }
        } catch (error) {
            console.error('[SessaoManager] Erro ao renovar sessão:', error);
            return false;
        }
    }
    
    /**
     * Fazer logout
     */
    async logout() {
        try {
            const formData = new FormData();
            formData.append('acao', 'logout');
            
            const response = await fetch(this.apiBase + 'verificar_sessao_completa.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            
            if (!response.ok) {
                console.warn('[SessaoManager] Erro ao fazer logout:', response.status);
                // Mesmo com erro, redirecionar para login
                this.parar();
                this.redirecionarParaLogin();
                return false;
            }
            
            let data;
            try {
                data = await response.json();
            } catch (parseError) {
                console.error('[SessaoManager] Erro ao fazer parse do JSON:', parseError);
                // Mesmo com erro, redirecionar para login
                this.parar();
                this.redirecionarParaLogin();
                return false;
            }
            
            if (data.sucesso) {
                console.log('[SessaoManager] Logout realizado');
                this.parar();
                this.redirecionarParaLogin();
                return true;
            }
        } catch (error) {
            console.error('[SessaoManager] Erro ao fazer logout:', error);
            // Mesmo com erro, redirecionar para login
            this.parar();
            this.redirecionarParaLogin();
        }
        
        return false;
    }
    
    /**
     * Configurar renovação por atividade do usuário
     */
    configurarRenovacaoPorAtividade() {
        let ultimaAtividade = Date.now();
        const eventos = ['mousedown', 'keydown', 'scroll', 'touchstart'];
        
        const atualizarAtividade = () => {
            const agora = Date.now();
            const tempoDecorrido = agora - ultimaAtividade;
            
            // Se passou mais de 5 minutos desde última atividade, renovar
            if (tempoDecorrido > 300000) {
                console.log('[SessaoManager] Atividade detectada, renovando sessão');
                this.renovarSessao();
            }
            
            ultimaAtividade = agora;
        };
        
        // Adicionar listeners
        eventos.forEach(evento => {
            document.addEventListener(evento, atualizarAtividade, { passive: true });
        });
    }
    
    /**
     * Alertar que sessão está prestes a expirar
     */
    alertarExpiracaoProxima(segundosRestantes) {
        const minutos = Math.floor(segundosRestantes / 60);
        
        // Mostrar alerta apenas uma vez quando chegar em 10 minutos
        if (minutos === 10 && !this.alertaMostrado) {
            this.alertaMostrado = true;
            
            console.warn('[SessaoManager] Sessão expira em', minutos, 'minutos');
            
            // Mostrar notificação (se houver elemento na página)
            const notificacao = document.getElementById('notificacao-sessao');
            if (notificacao) {
                notificacao.textContent = `Sua sessão expira em ${minutos} minutos. Salve seu trabalho.`;
                notificacao.style.display = 'block';
            }
        }
    }
    
    /**
     * Redirecionar para login
     */
    redirecionarParaLogin() {
        console.log('[SessaoManager] Redirecionando para login');
        
        // Parar gerenciador
        this.parar();
        
        // Obter URL de login apropriada
        const urlLogin = this.obterUrlLogin();
        
        // Aguardar 2 segundos e redirecionar
        setTimeout(() => {
            window.location.href = urlLogin;
        }, 2000);
    }
    
    /**
     * Atualizar interface com informações do usuário
     */
    atualizarInterfaceUsuario(usuario) {
        // Atualizar nome do usuário (se houver elemento)
        const nomeUsuario = document.getElementById('nome-usuario');
        if (nomeUsuario) {
            nomeUsuario.textContent = usuario.nome;
        }
        
        // Atualizar email (se houver elemento)
        const emailUsuario = document.getElementById('email-usuario');
        if (emailUsuario) {
            emailUsuario.textContent = usuario.email;
        }
        
        // Atualizar função (se houver elemento)
        const funcaoUsuario = document.getElementById('funcao-usuario');
        if (funcaoUsuario) {
            funcaoUsuario.textContent = usuario.funcao;
        }
    }
}

// Criar instância global
let sessaoManager = null;

// Iniciar automaticamente quando página carregar
document.addEventListener('DOMContentLoaded', function() {
    // Não iniciar na página de login
    if (!window.location.pathname.includes('login.html') && 
        !window.location.pathname.includes('login_fornecedor.html')) {
        sessaoManager = new SessaoManager();
        
        // Disponibilizar globalmente
        window.sessaoManager = sessaoManager;
        
        console.log('[SessaoManager] Gerenciador iniciado automaticamente');
    }
});

// Adicionar botão de logout (se houver)
document.addEventListener('DOMContentLoaded', function() {
    const btnLogout = document.getElementById('btn-logout');
    if (btnLogout && sessaoManager) {
        btnLogout.addEventListener('click', function(e) {
            e.preventDefault();
            sessaoManager.logout();
        });
    }
});
