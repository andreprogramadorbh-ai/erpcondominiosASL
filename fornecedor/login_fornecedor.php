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
$id_fornecedor = intval($_POST['id_fornecedor'] ?? 0);

// Validações básicas
if (!$id_fornecedor) {
    echo json_encode(['success' => false, 'message' => 'ID do fornecedor é obrigatório']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Buscar fornecedor pelo ID
    $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id_fornecedor = ?");
    $stmt->execute([$id_fornecedor]);
    $fornecedor = $stmt->fetch();
    
    if (!$fornecedor) {
        echo json_encode(['success' => false, 'message' => 'Fornecedor não encontrado. Verifique o ID informado.']);
        exit;
    }
    
    // Criar sessão
    $_SESSION['fornecedor_id'] = $fornecedor['id_fornecedor'];
    $_SESSION['fornecedor_nome'] = $fornecedor['nome_empreendimento'];
    $_SESSION['fornecedor_email'] = $fornecedor['email'];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Login realizado com sucesso!',
        'fornecedor' => [
            'id' => $fornecedor['id_fornecedor'],
            'nome' => $fornecedor['nome_empreendimento'],
            'segmento' => $fornecedor['segmento']
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

