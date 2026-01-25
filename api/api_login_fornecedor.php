<?php
// =====================================================
// API DE LOGIN FORNECEDOR - VERSÃO FINAL
// =====================================================
// ESTRUTURA CORRETA: (tipo, descricao, usuario, ip, data_hora)
// =====================================================

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';
$metodo = $_SERVER['REQUEST_METHOD'];

// ========== FAZER LOGIN ==========
if ($acao === 'login' && $metodo === 'POST') {
    try {
        $conexao = conectar_banco();
        
        if (!$conexao) {
            throw new Exception('Erro ao conectar ao banco de dados');
        }
        
        $email = trim($_POST['email'] ?? '');
        $senha = trim($_POST['senha'] ?? '');
        
        if (empty($email) || empty($senha)) {
            throw new Exception('E-mail e senha são obrigatórios');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('E-mail inválido');
        }
        
        // Buscar fornecedor por email
        $sql = "SELECT id, email, senha, nome_estabelecimento, ativo, aprovado FROM fornecedores WHERE email = ?";
        $stmt = $conexao->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Erro ao preparar consulta: ' . $conexao->error);
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            registrar_log_login($conexao, $email, 'Fornecedor não encontrado');
            throw new Exception('E-mail ou senha incorretos');
        }
        
        $fornecedor = $result->fetch_assoc();
        $stmt->close();
        
        // Verificar se está ativo
        if (!$fornecedor['ativo']) {
            registrar_log_login($conexao, $email, 'Fornecedor inativo');
            throw new Exception('Sua conta foi desativada. Contate o administrador.');
        }
        
        // Verificar se está aprovado
        if (!$fornecedor['aprovado']) {
            registrar_log_login($conexao, $email, 'Fornecedor não aprovado');
            throw new Exception('Sua conta ainda não foi aprovada. Aguarde a análise do administrador.');
        }
        
        // Verificar senha
        if (!password_verify($senha, $fornecedor['senha'])) {
            registrar_log_login($conexao, $email, 'Senha incorreta');
            throw new Exception('E-mail ou senha incorretos');
        }
        
        // Login bem-sucedido
        session_start();
        $_SESSION['fornecedor_id'] = $fornecedor['id'];
        $_SESSION['fornecedor_email'] = $fornecedor['email'];
        $_SESSION['fornecedor_nome'] = $fornecedor['nome_estabelecimento'];
        $_SESSION['fornecedor_logado'] = true;
        $_SESSION['login_time'] = time();
        
        registrar_log_login($conexao, $email, 'Login bem-sucedido');
        
        fechar_conexao($conexao);
        
        http_response_code(200);
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Login realizado com sucesso!',
            'dados' => [
                'id' => $fornecedor['id'],
                'email' => $fornecedor['email'],
                'nome' => $fornecedor['nome_estabelecimento']
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Exception $e) {
        fechar_conexao($conexao ?? null);
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ========== LOGOUT ==========
if ($acao === 'logout' && $metodo === 'POST') {
    try {
        session_start();
        
        $email = $_SESSION['fornecedor_email'] ?? '';
        
        // Registrar logout
        if ($email) {
            $conexao = conectar_banco();
            registrar_log_login($conexao, $email, 'Logout realizado');
            fechar_conexao($conexao);
        }
        
        // Destruir sessão
        session_destroy();
        
        http_response_code(200);
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Logout realizado com sucesso'
        ], JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ========== VERIFICAR SESSÃO ==========
if ($acao === 'verificar_sessao' && $metodo === 'GET') {
    try {
        session_start();
        
        if (!isset($_SESSION['fornecedor_logado']) || !$_SESSION['fornecedor_logado']) {
            throw new Exception('Não autenticado');
        }
        
        $login_time = $_SESSION['login_time'] ?? time();
        $tempo_decorrido = time() - $login_time;
        
        http_response_code(200);
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Sessão ativa',
            'dados' => [
                'id' => $_SESSION['fornecedor_id'],
                'email' => $_SESSION['fornecedor_email'],
                'nome' => $_SESSION['fornecedor_nome'],
                'tempo_decorrido' => $tempo_decorrido
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ========== OBTER DADOS DO FORNECEDOR ==========
if ($acao === 'obter_dados' && $metodo === 'GET') {
    try {
        session_start();
        
        if (!isset($_SESSION['fornecedor_logado']) || !$_SESSION['fornecedor_logado']) {
            throw new Exception('Não autenticado');
        }
        
        $conexao = conectar_banco();
        
        $id = intval($_SESSION['fornecedor_id']);
        
        $sql = "SELECT id, email, nome_estabelecimento, telefone, endereco, ramo_atividade_id, ativo, aprovado FROM fornecedores WHERE id = ?";
        $stmt = $conexao->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Erro ao preparar consulta: ' . $conexao->error);
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Fornecedor não encontrado');
        }
        
        $fornecedor = $result->fetch_assoc();
        $stmt->close();
        fechar_conexao($conexao);
        
        http_response_code(200);
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Dados obtidos com sucesso',
            'dados' => $fornecedor
        ], JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Exception $e) {
        fechar_conexao($conexao ?? null);
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ========== RENOVAR SESSÃO ==========
if ($acao === 'renovar_sessao' && $metodo === 'POST') {
    try {
        session_start();
        
        if (!isset($_SESSION['fornecedor_logado']) || !$_SESSION['fornecedor_logado']) {
            throw new Exception('Não autenticado');
        }
        
        $_SESSION['login_time'] = time();
        
        http_response_code(200);
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Sessão renovada com sucesso'
        ], JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ========== FUNÇÃO AUXILIAR: REGISTRAR LOG DE LOGIN ==========
// ✅ FINAL: Usa estrutura correta (tipo, descricao, usuario, ip, data_hora)
function registrar_log_login($conexao, $usuario, $descricao) {
    try {
        $tipo = 'LOGIN_FORNECEDOR';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $data_hora = date('Y-m-d H:i:s');
        
        // ✅ ESTRUTURA CORRETA
        $sql = "INSERT INTO logs_sistema (tipo, descricao, usuario, ip, data_hora) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conexao->prepare($sql);
        
        if (!$stmt) {
            error_log('Erro ao preparar INSERT de log: ' . $conexao->error);
            return;
        }
        
        // ✅ BIND CORRETO: 5 parâmetros = "sssss"
        $resultado = $stmt->bind_param("sssss", $tipo, $descricao, $usuario, $ip, $data_hora);
        
        if (!$resultado) {
            error_log('Erro ao vincular parâmetros de log: ' . $stmt->error);
            $stmt->close();
            return;
        }
        
        if (!$stmt->execute()) {
            error_log('Erro ao executar INSERT de log: ' . $stmt->error);
            $stmt->close();
            return;
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        // Silenciosamente falhar se não conseguir registrar log
        error_log('Erro ao registrar log de login: ' . $e->getMessage());
    }
}

// ========== AÇÃO INVÁLIDA ==========
http_response_code(400);
echo json_encode([
    'sucesso' => false,
    'mensagem' => 'Ação inválida ou método não permitido'
], JSON_UNESCAPED_UNICODE);
exit;
?>
