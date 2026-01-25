<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar se é fornecedor logado
if (!isset($_SESSION['fornecedor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Você precisa estar logado como fornecedor']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$fornecedor_id = $_SESSION['fornecedor_id'];
$associado_id = intval($_POST['id_associado'] ?? 0);

if (!$associado_id) {
    echo json_encode(['success' => false, 'message' => 'ID do associado é obrigatório']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Verificar se o associado existe
    $stmt = $pdo->prepare("SELECT nome, unidade FROM associados WHERE id_associado = ?");
    $stmt->execute([$associado_id]);
    $associado = $stmt->fetch();
    
    if (!$associado) {
        echo json_encode(['success' => false, 'message' => 'Associado não encontrado. Verifique o ID informado.']);
        exit;
    }
    
    // Buscar nome do fornecedor
    $stmt = $pdo->prepare("SELECT nome_empreendimento FROM fornecedores WHERE id_fornecedor = ?");
    $stmt->execute([$fornecedor_id]);
    $fornecedor = $stmt->fetch();
    
    echo json_encode([
        'success' => true, 
        'message' => "Desconto aplicado para: {$associado['nome']} - {$associado['unidade']}. O associado pode avaliar seu serviço após a finalização.",
        'associado' => [
            'id' => $associado_id,
            'nome' => $associado['nome'],
            'unidade' => $associado['unidade']
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao processar desconto: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>

