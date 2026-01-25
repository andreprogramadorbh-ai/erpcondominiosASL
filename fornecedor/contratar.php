<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar se está logado
if (!isset($_SESSION['associado_id'])) {
    echo json_encode(['success' => false, 'message' => 'Você precisa estar logado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$associado_id = $_SESSION['associado_id'];
$fornecedor_id = intval($_POST['id_fornecedor'] ?? 0);

if (!$fornecedor_id) {
    echo json_encode(['success' => false, 'message' => 'ID do fornecedor é obrigatório']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Verificar se o fornecedor existe
    $stmt = $pdo->prepare("SELECT nome_empreendimento FROM fornecedores WHERE id_fornecedor = ?");
    $stmt->execute([$fornecedor_id]);
    $fornecedor = $stmt->fetch();
    
    if (!$fornecedor) {
        echo json_encode(['success' => false, 'message' => 'Fornecedor não encontrado']);
        exit;
    }
    
    // Verificar se já existe uma contratação pendente ou em execução
    $stmt = $pdo->prepare("
        SELECT id_contratacao, status 
        FROM contratacoes 
        WHERE id_associado = ? AND id_fornecedor = ? 
        AND status IN ('pendente', 'aceita', 'executando')
    ");
    $stmt->execute([$associado_id, $fornecedor_id]);
    $contratacao_existente = $stmt->fetch();
    
    if ($contratacao_existente) {
        $status_msg = [
            'pendente' => 'aguardando aprovação do fornecedor',
            'aceita' => 'aceita pelo fornecedor',
            'executando' => 'em execução'
        ];
        
        echo json_encode([
            'success' => false, 
            'message' => 'Você já possui uma contratação com este fornecedor ' . $status_msg[$contratacao_existente['status']]
        ]);
        exit;
    }
    
    // Criar nova contratação
    $stmt = $pdo->prepare("
        INSERT INTO contratacoes (id_associado, id_fornecedor, data_solicitacao, status) 
        VALUES (?, ?, NOW(), 'pendente')
    ");
    $stmt->execute([$associado_id, $fornecedor_id]);
    
    $id_contratacao = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Solicitação de contratação enviada com sucesso! O fornecedor será notificado.',
        'id_contratacao' => $id_contratacao
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao processar contratação: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>

