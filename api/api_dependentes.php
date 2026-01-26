<?php
// =====================================================
// API PARA CRUD DE DEPENDENTES
// Reescrito replicando estrutura funcional de moradores
// =====================================================

// Limpar qualquer saída anterior
ob_start();

require_once 'config.php';
require_once 'auth_helper.php';

// Limpar buffer e definir headers
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: http://erp.asserradaliberdade.ong.br');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar autenticação
verificarAutenticacao(true, 'operador');

$metodo = $_SERVER['REQUEST_METHOD'];
$conexao = conectar_banco();

// Para operações de escrita, verificar permissão de operador
if ($metodo !== 'GET') {
    verificarPermissao('operador');
}

// ========== LISTAR DEPENDENTES ==========
if ($metodo === 'GET') {
    // Filtros de busca
    $filtro_morador_id = isset($_GET['morador_id']) ? intval($_GET['morador_id']) : 0;
    $filtro_nome = isset($_GET['nome']) ? trim($_GET['nome']) : '';
    $filtro_cpf = isset($_GET['cpf']) ? trim($_GET['cpf']) : '';
    
    $sql = "SELECT d.id, d.morador_id, d.nome_completo, d.cpf, d.email, d.telefone, d.celular, 
            d.data_nascimento, d.parentesco, d.ativo, d.observacao,
            DATE_FORMAT(d.data_cadastro, '%d/%m/%Y %H:%i') as data_cadastro,
            m.nome as nome_morador
            FROM dependentes d
            INNER JOIN moradores m ON d.morador_id = m.id
            WHERE 1=1";
    
    // Aplicar filtros
    if ($filtro_morador_id > 0) {
        $sql .= " AND d.morador_id = " . intval($filtro_morador_id);
    }
    
    if ($filtro_nome) {
        $sql .= " AND d.nome_completo LIKE '%" . $conexao->real_escape_string($filtro_nome) . "%'";
    }
    
    if ($filtro_cpf) {
        // Remover pontuação do CPF para busca
        $cpf_limpo = preg_replace('/[^0-9]/', '', $filtro_cpf);
        $sql .= " AND REPLACE(REPLACE(REPLACE(d.cpf, '.', ''), '-', ''), ' ', '') LIKE '%" . $conexao->real_escape_string($cpf_limpo) . "%'";
    }
    
    $sql .= " ORDER BY d.nome_completo ASC";
    
    $resultado = $conexao->query($sql);
    $dependentes = array();
    
    if ($resultado && $resultado->num_rows > 0) {
        while ($row = $resultado->fetch_assoc()) {
            $dependentes[] = $row;
        }
    }
    
    retornar_json(true, "Dependentes listados com sucesso", $dependentes);
}

// ========== CRIAR DEPENDENTE ==========
if ($metodo === 'POST') {
    $dados = json_decode(file_get_contents('php://input'), true);
    
    // Log de entrada
    error_log("[DEPENDENTE] POST recebido: " . json_encode($dados));
    
    $morador_id = intval($dados['morador_id'] ?? 0);
    $nome_completo = sanitizar($conexao, $dados['nome_completo'] ?? '');
    $cpf = sanitizar($conexao, $dados['cpf'] ?? '');
    $email = sanitizar($conexao, $dados['email'] ?? '');
    $telefone = sanitizar($conexao, $dados['telefone'] ?? '');
    $celular = sanitizar($conexao, $dados['celular'] ?? '');
    $data_nascimento = sanitizar($conexao, $dados['data_nascimento'] ?? '');
    $parentesco = sanitizar($conexao, $dados['parentesco'] ?? '');
    $observacao = sanitizar($conexao, $dados['observacao'] ?? '');
    
    // Limpar máscaras de CPF, telefone e celular
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    $celular = preg_replace('/[^0-9]/', '', $celular);
    
    // Validações
    if ($morador_id <= 0) {
        error_log("[DEPENDENTE] Erro: morador_id inválido");
        retornar_json(false, "Morador não informado");
    }
    
    if (empty($nome_completo)) {
        error_log("[DEPENDENTE] Erro: nome_completo vazio");
        retornar_json(false, "Nome completo é obrigatório");
    }
    
    if (empty($cpf)) {
        error_log("[DEPENDENTE] Erro: CPF vazio");
        retornar_json(false, "CPF é obrigatório");
    }
    
    if (empty($parentesco)) {
        error_log("[DEPENDENTE] Erro: parentesco vazio");
        retornar_json(false, "Parentesco é obrigatório");
    }
    
    // Verificar se morador existe
    $stmt = $conexao->prepare("SELECT id, nome FROM moradores WHERE id = ?");
    $stmt->bind_param("i", $morador_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 0) {
        $stmt->close();
        error_log("[DEPENDENTE] Erro: Morador ID $morador_id não encontrado");
        retornar_json(false, "Morador não encontrado");
    }
    
    $morador = $resultado->fetch_assoc();
    $nome_morador = $morador['nome'];
    $stmt->close();
    
    // Verificar se CPF já existe
    $stmt = $conexao->prepare("SELECT id FROM dependentes WHERE cpf = ?");
    $stmt->bind_param("s", $cpf);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->close();
        error_log("[DEPENDENTE] Erro: CPF $cpf já cadastrado");
        retornar_json(false, "CPF já cadastrado como dependente");
    }
    $stmt->close();
    
    // Verificar se CPF existe em visitantes
    $stmt = $conexao->prepare("SELECT id FROM visitantes WHERE cpf = ?");
    $stmt->bind_param("s", $cpf);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->close();
        error_log("[DEPENDENTE] Aviso: CPF $cpf já cadastrado como visitante");
        retornar_json(false, "CPF já cadastrado como visitante. Não é possível cadastrar como dependente.");
    }
    $stmt->close();
    
    // Inserir dependente
    $sql = "INSERT INTO dependentes (morador_id, nome_completo, cpf, email, telefone, celular, data_nascimento, parentesco, observacao) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conexao->prepare($sql);
    
    if (!$stmt) {
        error_log("[DEPENDENTE] Erro no prepare: " . $conexao->error);
        retornar_json(false, "Erro ao preparar inserção: " . $conexao->error);
    }
    
    $stmt->bind_param("issssssss", $morador_id, $nome_completo, $cpf, $email, $telefone, $celular, $data_nascimento, $parentesco, $observacao);
    
    if ($stmt->execute()) {
        $id_inserido = $conexao->insert_id;
        $affected_rows = $stmt->affected_rows;
        
        error_log("[DEPENDENTE] Sucesso: ID inserido = $id_inserido, Affected rows = $affected_rows");
        
        // Verificar se realmente foi inserido
        $stmt_check = $conexao->prepare("SELECT id, nome_completo FROM dependentes WHERE id = ?");
        $stmt_check->bind_param("i", $id_inserido);
        $stmt_check->execute();
        $resultado_check = $stmt_check->get_result();
        
        if ($resultado_check->num_rows > 0) {
            $dependente = $resultado_check->fetch_assoc();
            $stmt_check->close();
            
            registrar_log('DEPENDENTE_CRIADO', "Dependente criado: $nome_completo (ID: $id_inserido) para morador $nome_morador", $nome_completo);
            
            retornar_json(true, "Dependente cadastrado com sucesso", array(
                'id' => $id_inserido,
                'nome_completo' => $dependente['nome_completo'],
                'confirmado' => true
            ));
        } else {
            error_log("[DEPENDENTE] Erro: Registro não encontrado após INSERT (ID: $id_inserido)");
            retornar_json(false, "Erro: Dependente não foi salvo no banco de dados");
        }
    } else {
        $erro = $stmt->error;
        error_log("[DEPENDENTE] Erro no execute: $erro");
        retornar_json(false, "Erro ao cadastrar dependente: $erro");
    }
    
    $stmt->close();
}

// ========== ATUALIZAR DEPENDENTE ==========
if ($metodo === 'PUT') {
    $dados = json_decode(file_get_contents('php://input'), true);
    
    error_log("[DEPENDENTE] PUT recebido: " . json_encode($dados));
    
    $id = intval($dados['id'] ?? 0);
    $morador_id = intval($dados['morador_id'] ?? 0);
    $nome_completo = sanitizar($conexao, $dados['nome_completo'] ?? '');
    $cpf = sanitizar($conexao, $dados['cpf'] ?? '');
    $email = sanitizar($conexao, $dados['email'] ?? '');
    $telefone = sanitizar($conexao, $dados['telefone'] ?? '');
    $celular = sanitizar($conexao, $dados['celular'] ?? '');
    $data_nascimento = sanitizar($conexao, $dados['data_nascimento'] ?? '');
    $parentesco = sanitizar($conexao, $dados['parentesco'] ?? '');
    $observacao = sanitizar($conexao, $dados['observacao'] ?? '');
    
    // Limpar máscaras
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    $celular = preg_replace('/[^0-9]/', '', $celular);
    
    // Validações
    if ($id <= 0) {
        error_log("[DEPENDENTE] Erro: ID inválido");
        retornar_json(false, "ID inválido");
    }
    
    if ($morador_id <= 0) {
        error_log("[DEPENDENTE] Erro: morador_id inválido");
        retornar_json(false, "Morador não informado");
    }
    
    if (empty($nome_completo) || empty($cpf) || empty($parentesco)) {
        error_log("[DEPENDENTE] Erro: Dados obrigatórios vazios");
        retornar_json(false, "Dados inválidos para atualização");
    }
    
    // Verificar se CPF já existe em outro dependente
    $stmt = $conexao->prepare("SELECT id FROM dependentes WHERE cpf = ? AND id != ?");
    $stmt->bind_param("si", $cpf, $id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->close();
        error_log("[DEPENDENTE] Erro: CPF $cpf já cadastrado para outro dependente");
        retornar_json(false, "CPF já cadastrado para outro dependente");
    }
    $stmt->close();
    
    // Atualizar dependente
    $sql = "UPDATE dependentes SET morador_id=?, nome_completo=?, cpf=?, email=?, telefone=?, celular=?, data_nascimento=?, parentesco=?, observacao=? WHERE id=?";
    
    $stmt = $conexao->prepare($sql);
    
    if (!$stmt) {
        error_log("[DEPENDENTE] Erro no prepare UPDATE: " . $conexao->error);
        retornar_json(false, "Erro ao preparar atualização: " . $conexao->error);
    }
    
    $stmt->bind_param("issssssssi", $morador_id, $nome_completo, $cpf, $email, $telefone, $celular, $data_nascimento, $parentesco, $observacao, $id);
    
    if ($stmt->execute()) {
        error_log("[DEPENDENTE] Sucesso: Dependente ID $id atualizado");
        registrar_log('DEPENDENTE_ATUALIZADO', "Dependente atualizado: $nome_completo (ID: $id)", $nome_completo);
        retornar_json(true, "Dependente atualizado com sucesso");
    } else {
        $erro = $stmt->error;
        error_log("[DEPENDENTE] Erro no execute UPDATE: $erro");
        retornar_json(false, "Erro ao atualizar dependente: $erro");
    }
    
    $stmt->close();
}

// ========== EXCLUIR DEPENDENTE ==========
if ($metodo === 'DELETE') {
    $dados = json_decode(file_get_contents('php://input'), true);
    $id = intval($dados['id'] ?? 0);
    
    error_log("[DEPENDENTE] DELETE recebido: ID = $id");
    
    if ($id <= 0) {
        error_log("[DEPENDENTE] Erro: ID inválido");
        retornar_json(false, "ID inválido");
    }
    
    // Buscar nome do dependente antes de excluir
    $stmt = $conexao->prepare("SELECT nome_completo FROM dependentes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $dependente = $resultado->fetch_assoc();
    $nome_dependente = $dependente['nome_completo'] ?? 'Desconhecido';
    $stmt->close();
    
    // Excluir dependente
    $stmt = $conexao->prepare("DELETE FROM dependentes WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        error_log("[DEPENDENTE] Sucesso: Dependente ID $id excluído");
        registrar_log('DEPENDENTE_EXCLUIDO', "Dependente excluído: $nome_dependente (ID: $id)", $nome_dependente);
        retornar_json(true, "Dependente excluído com sucesso");
    } else {
        $erro = $stmt->error;
        error_log("[DEPENDENTE] Erro no execute DELETE: $erro");
        retornar_json(false, "Erro ao excluir dependente: $erro");
    }
    
    $stmt->close();
}

// ========== ATIVAR/INATIVAR DEPENDENTE ==========
if ($metodo === 'PATCH') {
    $dados = json_decode(file_get_contents('php://input'), true);
    $id = intval($dados['id'] ?? 0);
    $ativo = intval($dados['ativo'] ?? 1);
    
    error_log("[DEPENDENTE] PATCH recebido: ID = $id, Ativo = $ativo");
    
    if ($id <= 0) {
        error_log("[DEPENDENTE] Erro: ID inválido");
        retornar_json(false, "ID inválido");
    }
    
    // Buscar nome do dependente
    $stmt = $conexao->prepare("SELECT nome_completo FROM dependentes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $dependente = $resultado->fetch_assoc();
    $nome_dependente = $dependente['nome_completo'] ?? 'Desconhecido';
    $stmt->close();
    
    // Atualizar status
    $stmt = $conexao->prepare("UPDATE dependentes SET ativo = ? WHERE id = ?");
    $stmt->bind_param("ii", $ativo, $id);
    
    if ($stmt->execute()) {
        $status_texto = $ativo ? 'ativado' : 'inativado';
        error_log("[DEPENDENTE] Sucesso: Dependente ID $id $status_texto");
        registrar_log('DEPENDENTE_STATUS', "Dependente $status_texto: $nome_dependente (ID: $id)", $nome_dependente);
        retornar_json(true, "Dependente $status_texto com sucesso");
    } else {
        $erro = $stmt->error;
        error_log("[DEPENDENTE] Erro no execute PATCH: $erro");
        retornar_json(false, "Erro ao alterar status: $erro");
    }
    
    $stmt->close();
}

$conexao->close();
?>
