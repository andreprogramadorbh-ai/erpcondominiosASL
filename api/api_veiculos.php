<?php
// =====================================================
// API PARA CRUD DE VEÍCULOS
// =====================================================

// Limpar qualquer saída anterior
ob_start();

require_once 'config.php';

// Limpar buffer e definir headers
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

$metodo = $_SERVER['REQUEST_METHOD'];
$conexao = conectar_banco();

// ========== LISTAR VEÍCULOS ==========
if ($metodo === 'GET') {
    $sql = "SELECT v.id, v.placa, v.modelo, v.cor, v.tag, v.morador_id, v.ativo,
            m.nome as morador_nome, m.unidade as morador_unidade,
            DATE_FORMAT(v.data_cadastro, '%d/%m/%Y %H:%i') as data_cadastro
            FROM veiculos v
            INNER JOIN moradores m ON v.morador_id = m.id
            ORDER BY v.placa ASC";
    
    $resultado = $conexao->query($sql);
    $veiculos = array();
    
    if ($resultado && $resultado->num_rows > 0) {
        while ($row = $resultado->fetch_assoc()) {
            $veiculos[] = $row;
        }
    }
    
    retornar_json(true, "Veículos listados com sucesso", $veiculos);
}

// ========== CRIAR VEÍCULO ==========
if ($metodo === 'POST') {
    $dados = json_decode(file_get_contents('php://input'), true);
    
    $placa = strtoupper(sanitizar($conexao, $dados['placa'] ?? ''));
    $modelo = sanitizar($conexao, $dados['modelo'] ?? '');
    $cor = sanitizar($conexao, $dados['cor'] ?? '');
    $tag = sanitizar($conexao, $dados['tag'] ?? '');
    $morador_id = intval($dados['morador_id'] ?? 0);
    
    // Validações
    if (empty($placa) || empty($modelo) || empty($tag) || $morador_id <= 0) {
        retornar_json(false, "Todos os campos obrigatórios devem ser preenchidos");
    }
    
    // Validar formato da placa
    if (!preg_match('/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', $placa) && !preg_match('/^[A-Z]{3}-[0-9]{4}$/', $placa)) {
        retornar_json(false, "Formato de placa inválido. Use ABC1D23 ou ABC-1234");
    }
    
    // Verificar se placa já existe
    $stmt = $conexao->prepare("SELECT id FROM veiculos WHERE placa = ?");
    $stmt->bind_param("s", $placa);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->close();
        retornar_json(false, "Placa já cadastrada no sistema");
    }
    $stmt->close();
    
    // Verificar se TAG já existe
    $stmt = $conexao->prepare("SELECT id FROM veiculos WHERE tag = ?");
    $stmt->bind_param("s", $tag);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->close();
        retornar_json(false, "TAG já cadastrada no sistema. Cada TAG deve ser única.");
    }
    $stmt->close();
    
    // Verificar se morador existe
    $stmt = $conexao->prepare("SELECT nome FROM moradores WHERE id = ?");
    $stmt->bind_param("i", $morador_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 0) {
        $stmt->close();
        retornar_json(false, "Morador não encontrado");
    }
    $stmt->close();
    
    // Inserir veículo
    $stmt = $conexao->prepare("INSERT INTO veiculos (placa, modelo, cor, tag, morador_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $placa, $modelo, $cor, $tag, $morador_id);
    
    if ($stmt->execute()) {
        $id_inserido = $conexao->insert_id;
        registrar_log('VEICULO_CRIADO', "Veículo criado: $placa (TAG: $tag, ID: $id_inserido)");
        retornar_json(true, "Veículo cadastrado com sucesso", array('id' => $id_inserido));
    } else {
        retornar_json(false, "Erro ao cadastrar veículo: " . $stmt->error);
    }
    
    $stmt->close();
}

// ========== ATUALIZAR VEÍCULO ==========
if ($metodo === 'PUT') {
    $dados = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($dados['id'] ?? 0);
    $placa = strtoupper(sanitizar($conexao, $dados['placa'] ?? ''));
    $modelo = sanitizar($conexao, $dados['modelo'] ?? '');
    $cor = sanitizar($conexao, $dados['cor'] ?? '');
    $tag = sanitizar($conexao, $dados['tag'] ?? '');
    $morador_id = intval($dados['morador_id'] ?? 0);
    $ativo = intval($dados['ativo'] ?? 1);
    
    // Validações
    if ($id <= 0 || empty($placa) || empty($modelo) || empty($tag) || $morador_id <= 0) {
        retornar_json(false, "Dados inválidos para atualização");
    }
    
    // Verificar se placa já existe em outro veículo
    $stmt = $conexao->prepare("SELECT id FROM veiculos WHERE placa = ? AND id != ?");
    $stmt->bind_param("si", $placa, $id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->close();
        retornar_json(false, "Placa já cadastrada para outro veículo");
    }
    $stmt->close();
    
    // Verificar se TAG já existe em outro veículo
    $stmt = $conexao->prepare("SELECT id FROM veiculos WHERE tag = ? AND id != ?");
    $stmt->bind_param("si", $tag, $id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->close();
        retornar_json(false, "TAG já cadastrada para outro veículo");
    }
    $stmt->close();
    
    // Atualizar veículo
    $stmt = $conexao->prepare("UPDATE veiculos SET placa=?, modelo=?, cor=?, tag=?, morador_id=?, ativo=? WHERE id=?");
    $stmt->bind_param("ssssiii", $placa, $modelo, $cor, $tag, $morador_id, $ativo, $id);
    
    if ($stmt->execute()) {
        registrar_log('VEICULO_ATUALIZADO', "Veículo atualizado: $placa (TAG: $tag, ID: $id)");
        retornar_json(true, "Veículo atualizado com sucesso");
    } else {
        retornar_json(false, "Erro ao atualizar veículo: " . $stmt->error);
    }
    
    $stmt->close();
}

// ========== EXCLUIR VEÍCULO ==========
if ($metodo === 'DELETE') {
    $dados = json_decode(file_get_contents('php://input'), true);
    $id = intval($dados['id'] ?? 0);
    
    if ($id <= 0) {
        retornar_json(false, "ID inválido");
    }
    
    // Buscar dados do veículo antes de excluir
    $stmt = $conexao->prepare("SELECT placa, tag FROM veiculos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $veiculo = $resultado->fetch_assoc();
    $placa = $veiculo['placa'] ?? 'Desconhecido';
    $tag = $veiculo['tag'] ?? '';
    $stmt->close();
    
    // Excluir veículo
    $stmt = $conexao->prepare("DELETE FROM veiculos WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        registrar_log('VEICULO_EXCLUIDO', "Veículo excluído: $placa (TAG: $tag, ID: $id)");
        retornar_json(true, "Veículo excluído com sucesso");
    } else {
        retornar_json(false, "Erro ao excluir veículo: " . $stmt->error);
    }
    
    $stmt->close();
}

fechar_conexao($conexao);
