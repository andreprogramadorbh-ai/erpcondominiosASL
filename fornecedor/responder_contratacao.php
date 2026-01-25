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
$contratacao_id = intval($_POST['id_contratacao'] ?? 0);
$acao = $_POST['acao'] ?? '';

if (!$contratacao_id || !$acao) {
    echo json_encode(['success' => false, 'message' => 'Dados obrigatórios não informados']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Verificar se a contratação pertence ao fornecedor
    $stmt = $pdo->prepare("SELECT * FROM contratacoes WHERE id_contratacao = ? AND id_fornecedor = ?");
    $stmt->execute([$contratacao_id, $fornecedor_id]);
    $contratacao = $stmt->fetch();
    
    if (!$contratacao) {
        echo json_encode(['success' => false, 'message' => 'Contratação não encontrada']);
        exit;
    }
    
    if ($acao === 'aceitar') {
        // Verificar se está pendente
        if ($contratacao['status'] !== 'pendente') {
            echo json_encode(['success' => false, 'message' => 'Esta contratação não está pendente']);
            exit;
        }
        
        // Aceitar contratação
        $stmt = $pdo->prepare("UPDATE contratacoes SET status = 'aceita', data_aceitacao = NOW() WHERE id_contratacao = ?");
        $stmt->execute([$contratacao_id]);
        
        echo json_encode(['success' => true, 'message' => 'Contratação aceita com sucesso!']);
        
    } elseif ($acao === 'recusar') {
        // Verificar se está pendente
        if ($contratacao['status'] !== 'pendente') {
            echo json_encode(['success' => false, 'message' => 'Esta contratação não está pendente']);
            exit;
        }
        
        // Recusar contratação
        $stmt = $pdo->prepare("UPDATE contratacoes SET status = 'cancelada' WHERE id_contratacao = ?");
        $stmt->execute([$contratacao_id]);
        
        echo json_encode(['success' => true, 'message' => 'Contratação recusada.']);
        
    } elseif ($acao === 'iniciar') {
        // Verificar se está aceita
        if ($contratacao['status'] !== 'aceita') {
            echo json_encode(['success' => false, 'message' => 'Esta contratação não está aceita']);
            exit;
        }
        
        // Iniciar execução
        $stmt = $pdo->prepare("UPDATE contratacoes SET status = 'executando' WHERE id_contratacao = ?");
        $stmt->execute([$contratacao_id]);
        
        echo json_encode(['success' => true, 'message' => 'Execução do serviço iniciada!']);
        
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

