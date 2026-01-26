/**
 * =====================================================
 * JAVASCRIPT: visitantes.js
 * =====================================================
 * 
 * Gerencia funcionalidade de visitantes com:
 * - Popups informativos (sucesso/erro)
 * - Tratamento robusto de erros
 * - Logging detalhado
 * - Validações completas
 * 
 * @author Sistema ERP Serra da Liberdade
 * @date 26/01/2026
 * @version 1.0
 */

// Variável global para controle de edição
let editandoId = null;

/**
 * Inicializar sistema de visitantes
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('[VISITANTES] Inicializando sistema de visitantes');
    
    // Carregar visitantes
    carregarVisitantes();
    
    // Adicionar listener ao formulário
    const form = document.getElementById('visitanteForm');
    if (form) {
        form.addEventListener('submit', salvarVisitante);
    }
    
    console.log('[VISITANTES] Sistema inicializado com sucesso');
});

/**
 * Carregar lista de visitantes
 */
function carregarVisitantes() {
    console.log('[VISITANTES] Carregando lista de visitantes');
    
    const loading = document.getElementById('loading');
    if (loading) loading.classList.add('active');
    
    fetch('../api/api_visitantes.php', {
        method: 'GET',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        console.log('[VISITANTES] Resposta recebida - Status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return response.json();
    })
    .then(data => {
        if (loading) loading.classList.remove('active');
        
        console.log('[VISITANTES] Dados recebidos:', data);
        
        if (data.sucesso) {
            renderizarTabela(data.dados);
            console.log('[VISITANTES] Tabela renderizada com', data.dados.length, 'visitantes');
        } else {
            const mensagem = data.mensagem || 'Erro ao carregar visitantes';
            console.error('[VISITANTES] Erro na resposta:', mensagem);
            mostrarPopupErro('Erro ao Carregar', mensagem);
        }
    })
    .catch(error => {
        if (loading) loading.classList.remove('active');
        console.error('[VISITANTES] Erro ao carregar visitantes:', error);
        mostrarPopupErro('Erro de Conexão', `Erro ao carregar visitantes: ${error.message}`);
    });
}

/**
 * Buscar visitantes
 */
function buscarVisitantes() {
    const busca = document.getElementById('campoBusca').value;
    console.log('[VISITANTES] Buscando visitantes:', busca);
    
    const loading = document.getElementById('loading');
    if (loading) loading.classList.add('active');
    
    fetch(`../api/api_visitantes.php?busca=${encodeURIComponent(busca)}`, {
        method: 'GET',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (loading) loading.classList.remove('active');
        
        console.log('[VISITANTES] Resultados da busca:', data);
        
        if (data.sucesso) {
            renderizarTabela(data.dados);
        } else {
            const mensagem = data.mensagem || 'Erro ao buscar visitantes';
            console.error('[VISITANTES] Erro na resposta:', mensagem);
            mostrarPopupErro('Erro ao Buscar', mensagem);
        }
    })
    .catch(error => {
        if (loading) loading.classList.remove('active');
        console.error('[VISITANTES] Erro ao buscar visitantes:', error);
        mostrarPopupErro('Erro de Conexão', `Erro ao buscar visitantes: ${error.message}`);
    });
}

/**
 * Limpar busca
 */
function limparBusca() {
    document.getElementById('campoBusca').value = '';
    carregarVisitantes();
}

/**
 * Renderizar tabela de visitantes
 * @param {array} visitantes Array de visitantes
 */
function renderizarTabela(visitantes) {
    const tbody = document.querySelector('#visitantesTable tbody');
    tbody.innerHTML = '';

    if (visitantes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Nenhum visitante cadastrado</td></tr>';
        return;
    }

    visitantes.forEach(v => {
        const tr = document.createElement('tr');
        const cidadeUF = v.cidade && v.estado ? `${v.cidade}/${v.estado}` : (v.cidade || v.estado || '-');
        
        tr.innerHTML = `
            <td>${v.id}</td>
            <td><strong>${v.nome_completo}</strong></td>
            <td>${v.tipo_documento}: ${v.documento}</td>
            <td>${v.telefone || '-'}</td>
            <td>${v.celular || '-'}</td>
            <td>${cidadeUF}</td>
            <td>
                <button class="btn-edit" onclick="editarVisitante(${v.id})"><i class="fas fa-edit"></i></button>
                <button class="btn-delete" onclick="excluirVisitante(${v.id}, '${v.nome_completo}')"><i class="fas fa-trash"></i></button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

/**
 * Salvar visitante (criar ou atualizar)
 * @param {event} e Evento do formulário
 */
function salvarVisitante(e) {
    e.preventDefault();
    
    console.log('[VISITANTES] Iniciando salvamento de visitante');

    const dados = {
        nome_completo: document.getElementById('nomeCompleto').value,
        documento: document.getElementById('documento').value.replace(/\D/g, ''),
        tipo_documento: document.getElementById('tipoDocumento').value,
        cep: document.getElementById('cep').value.replace(/\D/g, ''),
        endereco: document.getElementById('endereco').value,
        numero: document.getElementById('numero').value,
        complemento: document.getElementById('complemento').value,
        bairro: document.getElementById('bairro').value,
        cidade: document.getElementById('cidade').value,
        estado: document.getElementById('estado').value,
        telefone: document.getElementById('telefone').value,
        celular: document.getElementById('celular').value,
        email: document.getElementById('email').value,
        observacao: document.getElementById('observacao').value
    };

    const metodo = editandoId ? 'PUT' : 'POST';
    if (editandoId) {
        dados.id = editandoId;
    }
    
    const acao = editandoId ? 'atualizar' : 'criar';
    console.log('[VISITANTES] Dados preparados:', dados);
    console.log('[VISITANTES] Método:', metodo, '- Ação:', acao);

    fetch('../api/api_visitantes.php', {
        method: metodo,
        credentials: 'include',
        headers: { 
            'Content-Type': 'application/json' 
        },
        body: JSON.stringify(dados)
    })
    .then(response => {
        console.log('[VISITANTES] Resposta recebida - Status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return response.json();
    })
    .then(data => {
        console.log('[VISITANTES] Dados da resposta:', data);
        
        if (data.sucesso) {
            const mensagem = data.mensagem || (editandoId ? 'Visitante atualizado com sucesso!' : 'Visitante cadastrado com sucesso!');
            console.log('[VISITANTES] Sucesso:', mensagem);
            
            mostrarPopupSucesso('✅ Sucesso!', mensagem);
            
            // Limpar formulário
            limparFormulario();
            
            // Recarregar lista
            carregarVisitantes();
            
        } else {
            const mensagem = data.mensagem || 'Erro desconhecido ao salvar visitante';
            console.error('[VISITANTES] Erro na resposta:', mensagem);
            mostrarPopupErro('❌ Erro ao Salvar', mensagem);
        }
    })
    .catch(error => {
        console.error('[VISITANTES] Erro ao salvar visitante:', error);
        mostrarPopupErro('❌ Erro de Conexão', `Erro ao salvar visitante: ${error.message}`);
    });
}

/**
 * Editar visitante
 * @param {number} id ID do visitante
 */
function editarVisitante(id) {
    console.log('[VISITANTES] Iniciando edição do visitante ID:', id);
    
    fetch('../api/api_visitantes.php', {
        method: 'GET',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.sucesso) {
            const visitante = data.dados.find(v => v.id == id);
            if (visitante) {
                console.log('[VISITANTES] Visitante encontrado:', visitante);
                
                // Preencher formulário
                document.getElementById('visitanteId').value = visitante.id;
                document.getElementById('nomeCompleto').value = visitante.nome_completo;
                document.getElementById('documento').value = visitante.documento;
                document.getElementById('tipoDocumento').value = visitante.tipo_documento;
                document.getElementById('cep').value = visitante.cep;
                document.getElementById('endereco').value = visitante.endereco;
                document.getElementById('numero').value = visitante.numero;
                document.getElementById('complemento').value = visitante.complemento;
                document.getElementById('bairro').value = visitante.bairro;
                document.getElementById('cidade').value = visitante.cidade;
                document.getElementById('estado').value = visitante.estado;
                document.getElementById('telefone').value = visitante.telefone;
                document.getElementById('celular').value = visitante.celular;
                document.getElementById('email').value = visitante.email;
                document.getElementById('observacao').value = visitante.observacao;
                
                editandoId = id;
                document.getElementById('formTitle').textContent = 'Editar Visitante';
                
                // Scroll para o formulário
                document.getElementById('visitanteForm').scrollIntoView({ behavior: 'smooth' });
                
                console.log('[VISITANTES] Formulário preenchido para edição');
            } else {
                console.error('[VISITANTES] Visitante não encontrado na lista');
                mostrarPopupErro('Erro', 'Visitante não encontrado');
            }
        } else {
            const mensagem = data.mensagem || 'Erro ao carregar visitante';
            console.error('[VISITANTES] Erro na resposta:', mensagem);
            mostrarPopupErro('Erro', mensagem);
        }
    })
    .catch(error => {
        console.error('[VISITANTES] Erro ao editar visitante:', error);
        mostrarPopupErro('Erro', `Erro ao carregar visitante: ${error.message}`);
    });
}

/**
 * Excluir visitante
 * @param {number} id ID do visitante
 * @param {string} nome Nome do visitante
 */
function excluirVisitante(id, nome) {
    if (!confirm(`Tem certeza que deseja excluir o visitante "${nome}"?`)) {
        return;
    }
    
    console.log('[VISITANTES] Excluindo visitante ID:', id);

    fetch('../api/api_visitantes.php', {
        method: 'DELETE',
        credentials: 'include',
        headers: { 
            'Content-Type': 'application/json' 
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('[VISITANTES] Resposta ao excluir:', data);
        
        if (data.sucesso) {
            const mensagem = data.mensagem || 'Visitante excluído com sucesso!';
            console.log('[VISITANTES] Sucesso:', mensagem);
            mostrarPopupSucesso('✅ Sucesso!', mensagem);
            carregarVisitantes();
        } else {
            const mensagem = data.mensagem || 'Erro ao excluir visitante';
            console.error('[VISITANTES] Erro na resposta:', mensagem);
            mostrarPopupErro('❌ Erro ao Excluir', mensagem);
        }
    })
    .catch(error => {
        console.error('[VISITANTES] Erro ao excluir visitante:', error);
        mostrarPopupErro('❌ Erro de Conexão', `Erro ao excluir visitante: ${error.message}`);
    });
}

/**
 * Limpar formulário
 */
function limparFormulario() {
    document.getElementById('visitanteForm').reset();
    document.getElementById('visitanteId').value = '';
    editandoId = null;
    document.getElementById('formTitle').textContent = 'Novo Visitante';
    console.log('[VISITANTES] Formulário limpo');
}

/**
 * Buscar CEP via ViaCEP
 */
function buscarCEP() {
    const cep = document.getElementById('cep').value.replace(/\D/g, '');
    
    if (cep.length !== 8) {
        mostrarPopupAviso('Atenção', 'CEP deve ter 8 dígitos');
        return;
    }
    
    console.log('[VISITANTES] Buscando CEP:', cep);
    
    const loading = document.querySelector('.cep-loading');
    if (loading) loading.classList.add('active');
    
    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
            if (loading) loading.classList.remove('active');
            
            if (data.erro) {
                mostrarPopupErro('CEP não encontrado', 'Verifique o CEP digitado');
                return;
            }
            
            document.getElementById('endereco').value = data.logradouro;
            document.getElementById('bairro').value = data.bairro;
            document.getElementById('cidade').value = data.localidade;
            document.getElementById('estado').value = data.uf;
            
            console.log('[VISITANTES] CEP encontrado:', data);
            mostrarPopupSucesso('CEP encontrado!', 'Endereço preenchido automaticamente');
        })
        .catch(error => {
            if (loading) loading.classList.remove('active');
            console.error('[VISITANTES] Erro ao buscar CEP:', error);
            mostrarPopupErro('Erro', 'Erro ao buscar CEP. Tente novamente.');
        });
}

// =====================================================
// FUNÇÕES DE POPUP
// =====================================================

/**
 * Mostrar popup de sucesso
 * @param {string} titulo Título do popup
 * @param {string} mensagem Mensagem
 */
function mostrarPopupSucesso(titulo, mensagem) {
    mostrarPopup(titulo, mensagem, 'success');
}

/**
 * Mostrar popup de erro
 * @param {string} titulo Título do popup
 * @param {string} mensagem Mensagem
 */
function mostrarPopupErro(titulo, mensagem) {
    mostrarPopup(titulo, mensagem, 'error');
}

/**
 * Mostrar popup de aviso
 * @param {string} titulo Título do popup
 * @param {string} mensagem Mensagem
 */
function mostrarPopupAviso(titulo, mensagem) {
    mostrarPopup(titulo, mensagem, 'warning');
}

/**
 * Mostrar popup genérico
 * @param {string} titulo Título
 * @param {string} mensagem Mensagem
 * @param {string} tipo Tipo (success, error, warning, info)
 */
function mostrarPopup(titulo, mensagem, tipo = 'info') {
    try {
        // Remover popup anterior se existir
        const popupAnterior = document.querySelector('.popup-overlay');
        if (popupAnterior) {
            popupAnterior.remove();
        }
        
        // Criar overlay
        const overlay = document.createElement('div');
        overlay.className = 'popup-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        `;
        
        // Cores por tipo
        const cores = {
            success: { bg: '#dcfce7', border: '#22c55e', icon: 'fa-check-circle', color: '#166534' },
            error: { bg: '#fee2e2', border: '#ef4444', icon: 'fa-exclamation-circle', color: '#991b1b' },
            warning: { bg: '#fef3c7', border: '#f59e0b', icon: 'fa-exclamation-triangle', color: '#92400e' },
            info: { bg: '#dbeafe', border: '#3b82f6', icon: 'fa-info-circle', color: '#1e40af' }
        };
        
        const cor = cores[tipo] || cores.info;
        
        // Criar popup
        const popup = document.createElement('div');
        popup.className = 'popup-modal';
        popup.style.cssText = `
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 90%;
            padding: 2rem;
            border-left: 5px solid ${cor.border};
            animation: slideIn 0.3s ease-out;
        `;
        
        popup.innerHTML = `
            <div style="display: flex; align-items: flex-start; gap: 1rem;">
                <div style="font-size: 2rem; color: ${cor.color};">
                    <i class="fas ${cor.icon}"></i>
                </div>
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 0.5rem 0; color: #1e293b; font-size: 1.25rem;">
                        ${titulo}
                    </h3>
                    <p style="margin: 0 0 1.5rem 0; color: #64748b; line-height: 1.5;">
                        ${mensagem}
                    </p>
                    <button onclick="fecharPopup()" style="
                        background: ${cor.border};
                        color: white;
                        border: none;
                        padding: 0.6rem 1.5rem;
                        border-radius: 8px;
                        cursor: pointer;
                        font-weight: 600;
                        transition: 0.2s;
                    " onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                        OK
                    </button>
                </div>
            </div>
        `;
        
        overlay.appendChild(popup);
        document.body.appendChild(overlay);
        
        // Adicionar estilos de animação
        if (!document.querySelector('style[data-popup-styles]')) {
            const style = document.createElement('style');
            style.setAttribute('data-popup-styles', 'true');
            style.textContent = `
                @keyframes slideIn {
                    from {
                        transform: translateY(-20px);
                        opacity: 0;
                    }
                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        // Fechar ao clicar no overlay
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                fecharPopup();
            }
        });
        
        // Fechar com ESC
        const fecharComESC = function(e) {
            if (e.key === 'Escape') {
                fecharPopup();
                document.removeEventListener('keydown', fecharComESC);
            }
        };
        document.addEventListener('keydown', fecharComESC);
        
        console.log('[VISITANTES] Popup exibido:', tipo, '-', titulo);
        
    } catch (error) {
        console.error('[VISITANTES] Erro ao mostrar popup:', error);
        // Fallback para alert nativo
        alert(`${titulo}\n\n${mensagem}`);
    }
}

/**
 * Fechar popup
 */
function fecharPopup() {
    const overlay = document.querySelector('.popup-overlay');
    if (overlay) {
        overlay.remove();
        console.log('[VISITANTES] Popup fechado');
    }
}

// Expor funções globalmente
window.carregarVisitantes = carregarVisitantes;
window.buscarVisitantes = buscarVisitantes;
window.limparBusca = limparBusca;
window.salvarVisitante = salvarVisitante;
window.editarVisitante = editarVisitante;
window.excluirVisitante = excluirVisitante;
window.limparFormulario = limparFormulario;
window.buscarCEP = buscarCEP;
window.mostrarPopup = mostrarPopup;
window.mostrarPopupSucesso = mostrarPopupSucesso;
window.mostrarPopupErro = mostrarPopupErro;
window.mostrarPopupAviso = mostrarPopupAviso;
window.fecharPopup = fecharPopup;

console.log('[VISITANTES] Script carregado com sucesso');
