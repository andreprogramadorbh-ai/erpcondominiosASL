<?php
/**
 * =====================================================
 * API: api_dependentes.php
 * =====================================================
 * 
 * API REST para gerenciar dependentes de moradores
 * 
 * Endpoints:
 * - GET    /api/api_dependentes.php?acao=listar&morador_id=X
 * - GET    /api/api_dependentes.php?acao=obter&id=X
 * - POST   /api/api_dependentes.php?acao=criar
 * - PUT    /api/api_dependentes.php?acao=atualizar&id=X
 * - DELETE /api/api_dependentes.php?acao=deletar&id=X
 * - POST   /api/api_dependentes.php?acao=ativar&id=X
 * - POST   /api/api_dependentes.php?acao=inativar&id=X
 * 
 * @author Sistema ERP Serra da Liberdade
 * @date 25/01/2026
 * @version 1.0
 */

// Limpar qualquer output anterior
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://erp.asserradaliberdade.ong.br');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config.php';
require_once 'auth_helper.php';
require_once 'controllers/DependenteController.php';

// VERIFICAÇÃO CRÍTICA DE AUTENTICAÇÃO
verificarAutenticacao(true, 'operador');

// Para operações de escrita, verificar permissão apropriada
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    verificarPermissao('operador');
}

// Função para resposta JSON
function resposta($sucesso, $mensagem, $dados = null) {
    if (ob_get_length()) ob_clean();
    
    $response = [
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
        'dados' => $dados
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Conectar ao banco de dados
    $conexao = conectar_banco();
    
    if (!$conexao) {
        resposta(false, 'Erro ao conectar ao banco de dados');
    }
    
    // Instanciar controller
    $controller = new DependenteController($conexao);
    
    // Obter ação
    $acao = isset($_GET['acao']) ? trim($_GET['acao']) : '';
    
    if (empty($acao)) {
        resposta(false, 'Ação não especificada');
    }
    
    // Processar requisição
    switch ($acao) {
        case 'listar':
            // GET /api/api_dependentes.php?acao=listar&morador_id=X
            $morador_id = isset($_GET['morador_id']) ? (int)$_GET['morador_id'] : 0;
            $apenas_ativos = isset($_GET['apenas_ativos']) ? (bool)$_GET['apenas_ativos'] : true;
            
            if ($morador_id <= 0) {
                resposta(false, 'ID do morador não especificado ou inválido');
            }
            
            $resultado = $controller->listar($morador_id, $apenas_ativos);
            resposta($resultado['sucesso'], $resultado['mensagem'], $resultado['dados'] ?? null);
            break;
        
        case 'obter':
            // GET /api/api_dependentes.php?acao=obter&id=X
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if ($id <= 0) {
                resposta(false, 'ID do dependente não especificado ou inválido');
            }
            
            $resultado = $controller->obter($id);
            resposta($resultado['sucesso'], $resultado['mensagem'], $resultado['dados'] ?? null);
            break;
        
        case 'criar':
            // POST /api/api_dependentes.php?acao=criar
            $dados = json_decode(file_get_contents('php://input'), true);
            
            if (!$dados) {
                resposta(false, 'Dados inválidos');
            }
            
            $resultado = $controller->criar($dados);
            resposta($resultado['sucesso'], $resultado['mensagem'], isset($resultado['id']) ? ['id' => $resultado['id']] : null);
            break;
        
        case 'atualizar':
            // PUT /api/api_dependentes.php?acao=atualizar&id=X
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if ($id <= 0) {
                resposta(false, 'ID do dependente não especificado ou inválido');
            }
            
            $dados = json_decode(file_get_contents('php://input'), true);
            
            if (!$dados) {
                resposta(false, 'Dados inválidos');
            }
            
            $resultado = $controller->atualizar($id, $dados);
            resposta($resultado['sucesso'], $resultado['mensagem']);
            break;
        
        case 'deletar':
            // DELETE /api/api_dependentes.php?acao=deletar&id=X
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if ($id <= 0) {
                resposta(false, 'ID do dependente não especificado ou inválido');
            }
            
            $resultado = $controller->deletar($id);
            resposta($resultado['sucesso'], $resultado['mensagem']);
            break;
        
        case 'ativar':
            // POST /api/api_dependentes.php?acao=ativar&id=X
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if ($id <= 0) {
                resposta(false, 'ID do dependente não especificado ou inválido');
            }
            
            $resultado = $controller->ativar($id);
            resposta($resultado['sucesso'], $resultado['mensagem']);
            break;
        
        case 'inativar':
            // POST /api/api_dependentes.php?acao=inativar&id=X
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if ($id <= 0) {
                resposta(false, 'ID do dependente não especificado ou inválido');
            }
            
            $resultado = $controller->inativar($id);
            resposta($resultado['sucesso'], $resultado['mensagem']);
            break;
        
        case 'listar_com_veiculos':
            // GET /api/api_dependentes.php?acao=listar_com_veiculos&morador_id=X
            $morador_id = isset($_GET['morador_id']) ? (int)$_GET['morador_id'] : 0;
            
            if ($morador_id <= 0) {
                resposta(false, 'ID do morador não especificado ou inválido');
            }
            
            $resultado = $controller->listarComVeiculos($morador_id);
            resposta($resultado['sucesso'], $resultado['mensagem'], $resultado['dados'] ?? null);
            break;
        
        default:
            resposta(false, 'Ação não reconhecida: ' . $acao);
    }
    
    $conexao->close();
    
} catch (Exception $e) {
    resposta(false, 'Erro: ' . $e->getMessage());
}
?>
