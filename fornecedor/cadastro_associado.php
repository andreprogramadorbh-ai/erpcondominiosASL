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
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$unidade = trim($_POST['unidade'] ?? '');

// Validações básicas
if (empty($nome) || empty($email) || empty($telefone) || empty($endereco) || empty($unidade)) {
    echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
    exit;
}

// Validar e-mail
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'E-mail inválido']);
    exit;
}

// Validar nome (mínimo 2 palavras)
$palavras_nome = explode(' ', trim($nome));
if (count($palavras_nome) < 2) {
    echo json_encode(['success' => false, 'message' => 'Digite o nome completo (nome e sobrenome)']);
    exit;
}

// Validar telefone (deve ter pelo menos 10 dígitos)
$telefone_numeros = preg_replace('/\D/', '', $telefone);
if (strlen($telefone_numeros) < 10) {
    echo json_encode(['success' => false, 'message' => 'Telefone deve ter pelo menos 10 dígitos']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Verificar se e-mail já existe
    $stmt = $pdo->prepare("SELECT id_associado FROM associados WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'E-mail já cadastrado']);
        exit;
    }
    
    // Inserir associado
    $stmt = $pdo->prepare("
        INSERT INTO associados (nome, email, telefone, endereco, unidade) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$nome, $email, $telefone, $endereco, $unidade]);
    
    $id_associado = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true, 
        'message' => "Associado cadastrado com sucesso!<br><strong>Seu ID de associado é: $id_associado</strong><br>Guarde este número para usar nos fornecedores e acessar o sistema.",
        'id_associado' => $id_associado
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao cadastrar associado: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>

