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
$contratacao_id = intval($_POST['id_contratacao'] ?? 0);
$acao = $_POST['acao'] ?? '';

if (!$contratacao_id || !$acao) {
    echo json_encode(['success' => false, 'message' => 'Dados obrigatórios não informados']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Verificar se a contratação pertence ao associado
    $stmt = $pdo->prepare("SELECT * FROM contratacoes WHERE id_contratacao = ? AND id_associado = ?");
    $stmt->execute([$contratacao_id, $associado_id]);
    $contratacao = $stmt->fetch();
    
    if (!$contratacao) {
        echo json_encode(['success' => false, 'message' => 'Contratação não encontrada']);
        exit;
    }
    
    if ($acao === 'finalizar') {
        // Verificar se está em execução
        if ($contratacao['status'] !== 'executando') {
            echo json_encode(['success' => false, 'message' => 'Esta contratação não está em execução']);
            exit;
        }
        
        // Finalizar contratação
        $stmt = $pdo->prepare("UPDATE contratacoes SET status = 'finalizada', data_finalizacao = NOW() WHERE id_contratacao = ?");
        $stmt->execute([$contratacao_id]);
        
        echo json_encode(['success' => true, 'message' => 'Contratação finalizada com sucesso!']);
        
    } elseif ($acao === 'cancelar') {
        // Verificar se está pendente
        if ($contratacao['status'] !== 'pendente') {
            echo json_encode(['success' => false, 'message' => 'Esta contratação não pode ser cancelada']);
            exit;
        }
        
        // Cancelar contratação
        $stmt = $pdo->prepare("UPDATE contratacoes SET status = 'cancelada' WHERE id_contratacao = ?");
        $stmt->execute([$contratacao_id]);
        
        echo json_encode(['success' => true, 'message' => 'Contratação cancelada com sucesso!']);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao processar solicitação: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>

