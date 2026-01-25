<?php
session_start();
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
$email = trim($_POST['email'] ?? '');

// Validações básicas
if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'E-mail é obrigatório']);
    exit;
}

// Validar e-mail
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'E-mail inválido']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Buscar associado pelo e-mail
    $stmt = $pdo->prepare("SELECT * FROM associados WHERE email = ?");
    $stmt->execute([$email]);
    $associado = $stmt->fetch();
    
    if (!$associado) {
        echo json_encode(['success' => false, 'message' => 'E-mail não encontrado. Verifique se você está cadastrado.']);
        exit;
    }
    
    // Criar sessão
    $_SESSION['associado_id'] = $associado['id_associado'];
    $_SESSION['associado_nome'] = $associado['nome'];
    $_SESSION['associado_email'] = $associado['email'];
    $_SESSION['associado_unidade'] = $associado['unidade'];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Login realizado com sucesso!',
        'associado' => [
            'id' => $associado['id_associado'],
            'nome' => $associado['nome'],
            'unidade' => $associado['unidade']
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao fazer login: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>

