/**
 * =====================================================
 * JAVASCRIPT: dependentes.js
 * =====================================================
 * 
 * Gerencia a funcionalidade de dependentes:
 * - Carregamento de dependentes
 * - Validações de formulário
 * - Requisições AJAX
 * - Manipulação do DOM
 * 
 * @author Sistema ERP Serra da Liberdade
 * @date 25/01/2026
 * @version 1.0
 */

// Variáveis globais
let moradorIdAtual = null;
let dependenteEmEdicao = null;

/**
 * Inicializar funcionalidade de dependentes
 * @param {number} moradorId ID do morador
 */
function inicializarDependentes(moradorId) {
    moradorIdAtual = moradorId;
    dependenteEmEdicao = null;
    
    // Limpar formulário
    limparFormularioDependente();
    
    // Carregar lista de dependentes
    carregarDependentes();
    
    // Adicionar listener ao formulário
    const form = document.getElementById('dependenteForm');
    if (form) {
        form.addEventListener('submit', salvarDependente);
    }
}

/**
 * Carregar lista de dependentes via AJAX
 */
function carregarDependentes() {
    if (!moradorIdAtual) {
        console.error('ID do morador não definido');
        return;
    }
    
    const loading = document.getElementById('loadingDependentes');
    if (loading) loading.classList.add('active');
    
    fetch(`../api/api_dependentes.php?acao=listar&morador_id=${moradorIdAtual}`, {
        method: 'GET',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (loading) loading.classList.remove('active');
        
        if (data.sucesso) {
            preencherTabelaDependentes(data.dados);
        } else {
            mostrarAlerta('Erro ao carregar dependentes: ' + data.mensagem, 'error');
        }
    })
    .catch(error => {
        if (loading) loading.classList.remove('active');
        console.error('Erro:', error);
        mostrarAlerta('Erro ao carregar dependentes', 'error');
    });
}

/**
 * Preencher tabela de dependentes
 * @param {array} dependentes Array de dependentes
 */
function preencherTabelaDependentes(dependentes) {
    const tbody = document.querySelector('#tabelaDependentes tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (dependentes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem;"><i class="fas fa-inbox"></i> Nenhum dependente cadastrado</td></tr>';
        return;
    }
    
    dependentes.forEach(dependente => {
        const statusBadge = dependente.ativo === 1 
            ? '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Ativo</span>'
            : '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Inativo</span>';
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${dependente.id}</td>
            <td><strong>${dependente.nome_completo}</strong></td>
            <td>${formatarCPF(dependente.cpf)}</td>
            <td>${dependente.parentesco || 'Outro'}</td>
            <td>${dependente.celular || '-'}</td>
            <td>${statusBadge}</td>
            <td class="actions">
                <button class="btn-edit" onclick="editarDependente(${dependente.id})" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-toggle" onclick="alternarStatusDependente(${dependente.id}, ${dependente.ativo})" title="${dependente.ativo === 1 ? 'Inativar' : 'Ativar'}">
                    <i class="fas fa-${dependente.ativo === 1 ? 'ban' : 'check'}"></i>
                </button>
                <button class="btn-delete" onclick="deletarDependente(${dependente.id})" title="Deletar">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

/**
 * Editar dependente
 * @param {number} id ID do dependente
 */
function editarDependente(id) {
    fetch(`../api/api_dependentes.php?acao=obter&id=${id}`, {
        method: 'GET',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            const dependente = data.dados;
            dependenteEmEdicao = dependente.id;
            
            // Preencher formulário
            document.getElementById('dependenteId').value = dependente.id;
            document.getElementById('nomeCompleto').value = dependente.nome_completo;
            document.getElementById('cpf').value = dependente.cpf;
            document.getElementById('email').value = dependente.email || '';
            document.getElementById('telefone').value = dependente.telefone || '';
            document.getElementById('celular').value = dependente.celular || '';
            document.getElementById('dataNascimento').value = dependente.data_nascimento || '';
            document.getElementById('parentesco').value = dependente.parentesco || 'Outro';
            document.getElementById('observacao').value = dependente.observacao || '';
            
            // Atualizar botões
            document.getElementById('formTitleDependente').textContent = 'Editar Dependente';
            document.getElementById('btnSalvarDependente').innerHTML = '<i class="fas fa-save"></i> Atualizar Dependente';
            document.getElementById('btnCancelarDependente').style.display = 'inline-block';
            
            // Desabilitar CPF em edição
            document.getElementById('cpf').disabled = true;
            
            // Scroll para formulário
            document.getElementById('formDependente').scrollIntoView({ behavior: 'smooth' });
        } else {
            mostrarAlerta('Erro ao carregar dependente: ' + data.mensagem, 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarAlerta('Erro ao carregar dependente', 'error');
    });
}

/**
 * Salvar dependente (criar ou atualizar)
 * @param {event} event Evento do formulário
 */
function salvarDependente(event) {
    event.preventDefault();
    
    // Validar formulário
    if (!validarFormularioDependente()) {
        return;
    }
    
    // Preparar dados
    const dados = {
        nome_completo: document.getElementById('nomeCompleto').value.trim(),
        cpf: document.getElementById('cpf').value.trim(),
        email: document.getElementById('email').value.trim(),
        telefone: document.getElementById('telefone').value.trim(),
        celular: document.getElementById('celular').value.trim(),
        data_nascimento: document.getElementById('dataNascimento').value,
        parentesco: document.getElementById('parentesco').value,
        observacao: document.getElementById('observacao').value.trim()
    };
    
    if (!dependenteEmEdicao) {
        dados.morador_id = moradorIdAtual;
    }
    
    // Determinar ação
    const acao = dependenteEmEdicao ? 'atualizar' : 'criar';
    const url = dependenteEmEdicao 
        ? `../api/api_dependentes.php?acao=${acao}&id=${dependenteEmEdicao}`
        : `../api/api_dependentes.php?acao=${acao}`;
    
    const metodo = dependenteEmEdicao ? 'PUT' : 'POST';
    
    // Enviar requisição
    fetch(url, {
        method: metodo,
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(dados)
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            mostrarAlerta(data.mensagem, 'success');
            limparFormularioDependente();
            carregarDependentes();
        } else {
            mostrarAlerta('Erro: ' + data.mensagem, 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarAlerta('Erro ao salvar dependente', 'error');
    });
}

/**
 * Alterar status do dependente (ativar/inativar)
 * @param {number} id ID do dependente
 * @param {number} statusAtual Status atual (1=ativo, 0=inativo)
 */
function alternarStatusDependente(id, statusAtual) {
    const novoStatus = statusAtual === 1 ? 0 : 1;
    const acao = novoStatus === 1 ? 'ativar' : 'inativar';
    const confirmacao = novoStatus === 1 
        ? 'Tem certeza que deseja ATIVAR este dependente?'
        : 'Tem certeza que deseja INATIVAR este dependente? Seus veículos também serão inativados.';
    
    if (!confirm(confirmacao)) {
        return;
    }
    
    fetch(`../api/api_dependentes.php?acao=${acao}&id=${id}`, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            mostrarAlerta(data.mensagem, 'success');
            carregarDependentes();
        } else {
            mostrarAlerta('Erro: ' + data.mensagem, 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarAlerta('Erro ao alterar status', 'error');
    });
}

/**
 * Deletar dependente
 * @param {number} id ID do dependente
 */
function deletarDependente(id) {
    if (!confirm('Tem certeza que deseja DELETAR este dependente? Seus veículos também serão deletados.')) {
        return;
    }
    
    fetch(`../api/api_dependentes.php?acao=deletar&id=${id}`, {
        method: 'DELETE',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            mostrarAlerta(data.mensagem, 'success');
            carregarDependentes();
        } else {
            mostrarAlerta('Erro: ' + data.mensagem, 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarAlerta('Erro ao deletar dependente', 'error');
    });
}

/**
 * Validar formulário de dependente
 * @returns {boolean} True se válido
 */
function validarFormularioDependente() {
    const nomeCompleto = document.getElementById('nomeCompleto').value.trim();
    const cpf = document.getElementById('cpf').value.trim();
    const celular = document.getElementById('celular').value.trim();
    const email = document.getElementById('email').value.trim();
    
    // Validar nome
    if (!nomeCompleto || nomeCompleto.length < 3) {
        mostrarAlerta('Nome completo deve ter pelo menos 3 caracteres', 'error');
        return false;
    }
    
    // Validar CPF
    if (!cpf || cpf.length < 11) {
        mostrarAlerta('CPF é obrigatório e deve ter 11 dígitos', 'error');
        return false;
    }
    
    if (!validarCPF(cpf)) {
        mostrarAlerta('CPF inválido', 'error');
        return false;
    }
    
    // Validar email se preenchido
    if (email && !validarEmail(email)) {
        mostrarAlerta('Email inválido', 'error');
        return false;
    }
    
    // Validar celular se preenchido
    if (celular && celular.length < 10) {
        mostrarAlerta('Celular deve ter pelo menos 10 dígitos', 'error');
        return false;
    }
    
    return true;
}

/**
 * Validar CPF (algoritmo módulo 11)
 * @param {string} cpf CPF a validar
 * @returns {boolean} True se válido
 */
function validarCPF(cpf) {
    // Remove caracteres especiais
    cpf = cpf.replace(/[^\d]/g, '');
    
    // Verifica se tem 11 dígitos
    if (cpf.length !== 11) {
        return false;
    }
    
    // Verifica se não é uma sequência de números iguais
    if (/^(\d)\1{10}$/.test(cpf)) {
        return false;
    }
    
    // Calcula primeiro dígito verificador
    let soma = 0;
    for (let i = 0; i < 9; i++) {
        soma += cpf[i] * (10 - i);
    }
    let resto = soma % 11;
    let digito1 = resto < 2 ? 0 : 11 - resto;
    
    if (cpf[9] != digito1) {
        return false;
    }
    
    // Calcula segundo dígito verificador
    soma = 0;
    for (let i = 0; i < 10; i++) {
        soma += cpf[i] * (11 - i);
    }
    resto = soma % 11;
    let digito2 = resto < 2 ? 0 : 11 - resto;
    
    if (cpf[10] != digito2) {
        return false;
    }
    
    return true;
}

/**
 * Validar email
 * @param {string} email Email a validar
 * @returns {boolean} True se válido
 */
function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Limpar formulário de dependente
 */
function limparFormularioDependente() {
    dependenteEmEdicao = null;
    
    const form = document.getElementById('dependenteForm');
    if (form) {
        form.reset();
    }
    
    document.getElementById('dependenteId').value = '';
    document.getElementById('formTitleDependente').textContent = 'Novo Dependente';
    document.getElementById('btnSalvarDependente').innerHTML = '<i class="fas fa-save"></i> Salvar Dependente';
    document.getElementById('btnCancelarDependente').style.display = 'none';
    document.getElementById('cpf').disabled = false;
}

/**
 * Formatar CPF para exibição
 * @param {string} cpf CPF a formatar
 * @returns {string} CPF formatado
 */
function formatarCPF(cpf) {
    if (!cpf) return '-';
    cpf = cpf.replace(/[^\d]/g, '');
    if (cpf.length !== 11) return cpf;
    return cpf.substring(0, 3) + '.' + cpf.substring(3, 6) + '.' + cpf.substring(6, 9) + '-' + cpf.substring(9);
}

/**
 * Formatar entrada de CPF em tempo real
 * @param {element} element Elemento input
 */
function formatarInputCPF(element) {
    let valor = element.value.replace(/[^\d]/g, '');
    
    if (valor.length > 11) {
        valor = valor.substring(0, 11);
    }
    
    if (valor.length > 9) {
        valor = valor.substring(0, 3) + '.' + valor.substring(3, 6) + '.' + valor.substring(6, 9) + '-' + valor.substring(9);
    } else if (valor.length > 6) {
        valor = valor.substring(0, 3) + '.' + valor.substring(3, 6) + '.' + valor.substring(6);
    } else if (valor.length > 3) {
        valor = valor.substring(0, 3) + '.' + valor.substring(3);
    }
    
    element.value = valor;
}

/**
 * Mostrar alerta
 * @param {string} mensagem Mensagem a exibir
 * @param {string} tipo Tipo de alerta (success, error, warning)
 */
function mostrarAlerta(mensagem, tipo = 'info') {
    const alertBox = document.getElementById('alertBoxDependentes');
    if (!alertBox) return;
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${tipo}`;
    alert.innerHTML = `
        <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        ${mensagem}
    `;
    
    alertBox.innerHTML = '';
    alertBox.appendChild(alert);
    
    // Auto-remover após 5 segundos
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// Exportar funções para uso global
window.inicializarDependentes = inicializarDependentes;
window.carregarDependentes = carregarDependentes;
window.editarDependente = editarDependente;
window.alternarStatusDependente = alternarStatusDependente;
window.deletarDependente = deletarDependente;
window.limparFormularioDependente = limparFormularioDependente;
window.formatarInputCPF = formatarInputCPF;
