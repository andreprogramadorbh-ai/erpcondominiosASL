<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Receber dados do formulário
$nome_empreendimento = trim($_POST['nome_empreendimento'] ?? '');
$cpf_cnpj = preg_replace('/\D/', '', $_POST['cpf_cnpj'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$cep = preg_replace('/\D/', '', $_POST['cep'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$segmento = trim($_POST['segmento'] ?? '');
$site = trim($_POST['site'] ?? '');
$instagram = trim($_POST['instagram'] ?? '');

// Validações básicas
if (empty($nome_empreendimento) || empty($cpf_cnpj) || empty($email) || 
    empty($telefone) || empty($cep) || empty($endereco) || empty($segmento)) {
    echo json_encode(['success' => false, 'message' => 'Todos os campos obrigatórios devem ser preenchidos']);
    exit;
}

// Validar e-mail
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'E-mail inválido']);
    exit;
}

// Validar CPF/CNPJ
function validarCPF($cpf) {
    if (strlen($cpf) != 11) return false;
    
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += intval($cpf[$i]) * (10 - $i);
    }
    $resto = 11 - ($soma % 11);
    if ($resto == 10 || $resto == 11) $resto = 0;
    if ($resto != intval($cpf[9])) return false;
    
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += intval($cpf[$i]) * (11 - $i);
    }
    $resto = 11 - ($soma % 11);
    if ($resto == 10 || $resto == 11) $resto = 0;
    if ($resto != intval($cpf[10])) return false;
    
    return true;
}

function validarCNPJ($cnpj) {
    if (strlen($cnpj) != 14) return false;
    
    $tamanho = strlen($cnpj) - 2;
    $numeros = substr($cnpj, 0, $tamanho);
    $digitos = substr($cnpj, $tamanho);
    $soma = 0;
    $pos = $tamanho - 7;
    
    for ($i = $tamanho; $i >= 1; $i--) {
        $soma += intval($numeros[$tamanho - $i]) * $pos--;
        if ($pos < 2) $pos = 9;
    }
    
    $resultado = $soma % 11 < 2 ? 0 : 11 - $soma % 11;
    if ($resultado != intval($digitos[0])) return false;
    
    $tamanho = $tamanho + 1;
    $numeros = substr($cnpj, 0, $tamanho);
    $soma = 0;
    $pos = $tamanho - 7;
    
    for ($i = $tamanho; $i >= 1; $i--) {
        $soma += intval($numeros[$tamanho - $i]) * $pos--;
        if ($pos < 2) $pos = 9;
    }
    
    $resultado = $soma % 11 < 2 ? 0 : 11 - $soma % 11;
    if ($resultado != intval($digitos[1])) return false;
    
    return true;
}

// Validar CPF ou CNPJ
if (strlen($cpf_cnpj) == 11) {
    if (!validarCPF($cpf_cnpj)) {
        echo json_encode(['success' => false, 'message' => 'CPF inválido']);
        exit;
    }
    $cpf_cnpj_formatado = substr($cpf_cnpj, 0, 3) . '.' . substr($cpf_cnpj, 3, 3) . '.' . substr($cpf_cnpj, 6, 3) . '-' . substr($cpf_cnpj, 9, 2);
} elseif (strlen($cpf_cnpj) == 14) {
    if (!validarCNPJ($cpf_cnpj)) {
        echo json_encode(['success' => false, 'message' => 'CNPJ inválido']);
        exit;
    }
    $cpf_cnpj_formatado = substr($cpf_cnpj, 0, 2) . '.' . substr($cpf_cnpj, 2, 3) . '.' . substr($cpf_cnpj, 5, 3) . '/' . substr($cpf_cnpj, 8, 4) . '-' . substr($cpf_cnpj, 12, 2);
} else {
    echo json_encode(['success' => false, 'message' => 'CPF/CNPJ deve ter 11 ou 14 dígitos']);
    exit;
}

// Validar CEP
if (strlen($cep) != 8) {
    echo json_encode(['success' => false, 'message' => 'CEP deve ter 8 dígitos']);
    exit;
}
$cep_formatado = substr($cep, 0, 5) . '-' . substr($cep, 5, 3);

try {
    $pdo = getConnection();
    
    // Verificar se CPF/CNPJ já existe
    $stmt = $pdo->prepare("SELECT id_fornecedor FROM fornecedores WHERE cpf_cnpj = ?");
    $stmt->execute([$cpf_cnpj_formatado]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'CPF/CNPJ já cadastrado']);
        exit;
    }
    
    // Verificar se e-mail já existe
    $stmt = $pdo->prepare("SELECT id_fornecedor FROM fornecedores WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'E-mail já cadastrado']);
        exit;
    }
    
    // Inserir fornecedor
    $stmt = $pdo->prepare("
        INSERT INTO fornecedores 
        (nome_empreendimento, cpf_cnpj, email, telefone, cep, endereco, segmento, site, instagram) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $nome_empreendimento,
        $cpf_cnpj_formatado,
        $email,
        $telefone,
        $cep_formatado,
        $endereco,
        $segmento,
        $site ?: null,
        $instagram ?: null
    ]);
    
    $id_fornecedor = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true, 
        'message' => "Fornecedor cadastrado com sucesso! ID: $id_fornecedor",
        'id_fornecedor' => $id_fornecedor
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao cadastrar fornecedor: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>

