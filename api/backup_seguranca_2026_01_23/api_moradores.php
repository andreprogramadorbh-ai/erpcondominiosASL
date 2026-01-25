<?php
// =====================================================
// API PARA CRUD DE MORADORES
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

// Para operações de escrita, verificar permissão de admin
if ($metodo !== 'GET') {
    verificarPermissao('admin');
}

// ========== LISTAR MORADORES ==========
if ($metodo === 'GET') {
    // Filtros de busca
    $filtro_unidade = isset($_GET['unidade']) ? trim($_GET['unidade']) : '';
    $filtro_nome = isset($_GET['nome']) ? trim($_GET['nome']) : '';
    $filtro_email = isset($_GET['email']) ? trim($_GET['email']) : '';
    $filtro_cpf = isset($_GET['cpf']) ? trim($_GET['cpf']) : '';
    
    $sql = "SELECT id, nome, cpf, unidade, email, telefone, celular, ativo, 
            DATE_FORMAT(data_cadastro, '%d/%m/%Y %H:%i') as data_cadastro 
            FROM moradores WHERE 1=1";
    
    $condicoes = array();
    
    // Aplicar filtros
    if ($filtro_unidade) {
        $sql .= " AND unidade = '" . $conexao->real_escape_string($filtro_unidade) . "'";
    }
    
    if ($filtro_nome) {
        $sql .= " AND nome LIKE '%" . $conexao->real_escape_string($filtro_nome) . "%'";
    }
    
    if ($filtro_email) {
        $sql .= " AND email LIKE '%" . $conexao->real_escape_string($filtro_email) . "%'";
    }
    
    if ($filtro_cpf) {
        // Remover pontuação do CPF para busca
        $cpf_limpo = preg_replace('/[^0-9]/', '', $filtro_cpf);
        $sql .= " AND REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') LIKE '%" . $conexao->real_escape_string($cpf_limpo) . "%'";
    }
    
    $sql .= " ORDER BY nome ASC";
    
    $resultado = $conexao->query($sql);
    $moradores = array();
    
    if ($resultado && $resultado->num_rows > 0) {
        while ($row = $resultado->fetch_assoc()) {
            $moradores[] = $row;
        }
    }
    
    retornar_json(true, "Moradores listados com sucesso", $moradores);
}

// ========== CRIAR MORADOR ==========
if ($metodo === 'POST') {
    $dados = json_decode(file_get_contents('php://input'), true);
    
    $nome = sanitizar($conexao, $dados['nome'] ?? '');
    $cpf = sanitizar($conexao, $dados['cpf'] ?? '');
    $unidade = sanitizar($conexao, $dados['unidade'] ?? '');
    $email = sanitizar($conexao, $dados['email'] ?? '');
    $senha = $dados['senha'] ?? '';
    $telefone = sanitizar($conexao, $dados['telefone'] ?? '');
    $celular = sanitizar($conexao, $dados['celular'] ?? '');
    
    // Validações
    if (empty($nome) || empty($cpf) || empty($unidade) || empty($email) || empty($senha)) {
        retornar_json(false, "Todos os campos obrigatórios devem ser preenchidos");
    }
    
    // Verificar se CPF já existe
    $stmt = $conexao->prepare("SELECT id FROM moradores WHERE cpf = ?");
    $stmt->bind_param("s", $cpf);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->close();
        retornar_json(false, "CPF já cadastrado no sistema");
    }
    $stmt->close();
    
    // Criptografar senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    
    // Inserir morador
    $stmt = $conexao->prepare("INSERT INTO moradores (nome, cpf, unidade, email, senha, telefone, celular) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $nome, $cpf, $unidade, $email, $senha_hash, $telefone, $celular);
    
    if ($stmt->execute()) {
        $id_inserido = $conexao->insert_id;
        registrar_log('MORADOR_CRIADO', "Morador criado: $nome (ID: $id_inserido)", $nome);
        retornar_json(true, "Morador cadastrado com sucesso", array('id' => $id_inserido));
    } else {
        retornar_json(false, "Erro ao cadastrar morador: " . $stmt->error);
    }
    
    $stmt->close();
}

// ========== ATUALIZAR MORADOR ==========
if ($metodo === 'PUT') {
    $dados = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($dados['id'] ?? 0);
    $nome = sanitizar($conexao, $dados['nome'] ?? '');
    $cpf = sanitizar($conexao, $dados['cpf'] ?? '');
    $unidade = sanitizar($conexao, $dados['unidade'] ?? '');
    $email = sanitizar($conexao, $dados['email'] ?? '');
    $telefone = sanitizar($conexao, $dados['telefone'] ?? '');
    $celular = sanitizar($conexao, $dados['celular'] ?? '');
    
    // Validações
    if ($id <= 0 || empty($nome) || empty($cpf) || empty($unidade) || empty($email)) {
        retornar_json(false, "Dados inválidos para atualização");
    }
    
    // Verificar se CPF já existe em outro morador
    $stmt = $conexao->prepare("SELECT id FROM moradores WHERE cpf = ? AND id != ?");
    $stmt->bind_param("si", $cpf, $id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->close();
        retornar_json(false, "CPF já cadastrado para outro morador");
    }
    $stmt->close();
    
    // Verificar se a senha foi enviada para atualização
    if (isset($dados['senha']) && !empty($dados['senha'])) {
        $senha = $dados['senha'];
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        
        // Atualizar morador com senha
        $stmt = $conexao->prepare("UPDATE moradores SET nome=?, cpf=?, unidade=?, email=?, telefone=?, celular=?, senha=? WHERE id=?");
        $stmt->bind_param("sssssssi", $nome, $cpf, $unidade, $email, $telefone, $celular, $senha_hash, $id);
    } else {
        // Atualizar morador sem senha
        $stmt = $conexao->prepare("UPDATE moradores SET nome=?, cpf=?, unidade=?, email=?, telefone=?, celular=? WHERE id=?");
        $stmt->bind_param("ssssssi", $nome, $cpf, $unidade, $email, $telefone, $celular, $id);
    }
    
    if ($stmt->execute()) {
        registrar_log('MORADOR_ATUALIZADO', "Morador atualizado: $nome (ID: $id)", $nome);
        retornar_json(true, "Morador atualizado com sucesso");
    } else {
        retornar_json(false, "Erro ao atualizar morador: " . $stmt->error);
    }
    
    $stmt->close();
}

// ========== EXCLUIR MORADOR ==========
if ($metodo === 'DELETE') {
    $dados = json_decode(file_get_contents('php://input'), true);
    $id = intval($dados['id'] ?? 0);
    
    if ($id <= 0) {
        retornar_json(false, "ID inválido");
    }
    
    // Buscar nome do morador antes de excluir
    $stmt = $conexao->prepare("SELECT nome FROM moradores WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $morador = $resultado->fetch_assoc();
    $nome_morador = $morador['nome'] ?? 'Desconhecido';
    $stmt->close();
    
    // Excluir morador (veículos serão excluídos automaticamente por CASCADE)
    $stmt = $conexao->prepare("DELETE FROM moradores WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        registrar_log('MORADOR_EXCLUIDO', "Morador excluído: $nome_morador (ID: $id)", $nome_morador);
        retornar_json(true, "Morador excluído com sucesso");
    } else {
        retornar_json(false, "Erro ao excluir morador: " . $stmt->error);
    }
    
    $stmt->close();
}

fechar_conexao($conexao);
