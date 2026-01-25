<?php
// =====================================================
// TEMPLATE BASE PARA TODAS AS APIs
// =====================================================
// 
// Inclua este arquivo no início de todas as APIs:
// require_once 'api_base.php';

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 7200); // 2 horas

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Headers para API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://erp.asserradaliberdade.ong.br');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir configurações
require_once 'config.php';

/**
 * Classe base para APIs
 */
class ApiBase {
    protected $conexao = null;
    protected $requer_autenticacao = true;
    
    /**
     * Construtor
     */
    public function __construct($requer_autenticacao = true) {
        $this->requer_autenticacao = $requer_autenticacao;
        
        // Verificar autenticação se necessário
        if ($this->requer_autenticacao) {
            $this->verificarAutenticacao();
        }
    }
    
    /**
     * Verificar se usuário está autenticado
     */
    protected function verificarAutenticacao() {
        if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
            $this->retornarErro('Sessão inválida ou expirada. Faça login novamente.', 401);
        }
        
        if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
            $this->retornarErro('Usuário não identificado. Faça login novamente.', 401);
        }
        
        // Verificar timeout da sessão (2 horas)
        if (isset($_SESSION['login_timestamp'])) {
            $tempo_decorrido = time() - $_SESSION['login_timestamp'];
            
            if ($tempo_decorrido > 7200) {
                $this->retornarErro('Sessão expirada. Faça login novamente.', 401);
            }
            
            // Atualizar timestamp se passou mais de 5 minutos
            if ($tempo_decorrido > 300) {
                $_SESSION['login_timestamp'] = time();
            }
        }
    }
    
    /**
     * Obter conexão com banco
     */
    protected function getConexao() {
        if ($this->conexao === null) {
            $this->conexao = conectar_banco();
        }
        return $this->conexao;
    }
    
    /**
     * Fechar conexão
     */
    protected function fecharConexao() {
        if ($this->conexao !== null) {
            fechar_conexao($this->conexao);
            $this->conexao = null;
        }
    }
    
    /**
     * Obter ID do usuário logado
     */
    protected function getUsuarioId() {
        return $_SESSION['usuario_id'] ?? null;
    }
    
    /**
     * Obter dados do usuário logado
     */
    protected function getUsuario() {
        return [
            'id' => $_SESSION['usuario_id'] ?? null,
            'nome' => $_SESSION['usuario_nome'] ?? null,
            'email' => $_SESSION['usuario_email'] ?? null,
            'funcao' => $_SESSION['usuario_funcao'] ?? null,
            'departamento' => $_SESSION['usuario_departamento'] ?? null,
            'permissao' => $_SESSION['usuario_permissao'] ?? null
        ];
    }
    
    /**
     * Verificar permissão do usuário
     */
    protected function verificarPermissao($permissao_necessaria) {
        $permissao_usuario = $_SESSION['usuario_permissao'] ?? 'usuario';
        
        $hierarquia = ['usuario' => 1, 'moderador' => 2, 'admin' => 3];
        
        $nivel_usuario = $hierarquia[$permissao_usuario] ?? 1;
        $nivel_necessario = $hierarquia[$permissao_necessaria] ?? 1;
        
        if ($nivel_usuario < $nivel_necessario) {
            $this->retornarErro('Você não tem permissão para realizar esta ação.', 403);
        }
    }
    
    /**
     * Validar campos obrigatórios
     */
    protected function validarCampos($dados, $campos_obrigatorios) {
        $campos_faltando = [];
        
        foreach ($campos_obrigatorios as $campo) {
            if (!isset($dados[$campo]) || empty(trim($dados[$campo]))) {
                $campos_faltando[] = $campo;
            }
        }
        
        if (!empty($campos_faltando)) {
            $this->retornarErro('Campos obrigatórios não preenchidos: ' . implode(', ', $campos_faltando), 400);
        }
    }
    
    /**
     * Retornar sucesso
     */
    protected function retornarSucesso($mensagem, $dados = null, $codigo = 200) {
        http_response_code($codigo);
        
        $resposta = [
            'sucesso' => true,
            'mensagem' => $mensagem
        ];
        
        if ($dados !== null) {
            $resposta['dados'] = $dados;
        }
        
        echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
        
        // Fechar conexão se aberta
        $this->fecharConexao();
        
        exit;
    }
    
    /**
     * Retornar erro
     */
    protected function retornarErro($mensagem, $codigo = 400, $dados = null) {
        http_response_code($codigo);
        
        $resposta = [
            'sucesso' => false,
            'mensagem' => $mensagem
        ];
        
        if ($dados !== null) {
            $resposta['dados'] = $dados;
        }
        
        echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
        
        // Fechar conexão se aberta
        $this->fecharConexao();
        
        exit;
    }
    
    /**
     * Executar query preparada
     */
    protected function executarQuery($query, $params = [], $tipos = '') {
        try {
            $conexao = $this->getConexao();
            $stmt = $conexao->prepare($query);
            
            if (!$stmt) {
                throw new Exception('Erro ao preparar query: ' . $conexao->error);
            }
            
            // Bind params se houver
            if (!empty($params)) {
                $stmt->bind_param($tipos, ...$params);
            }
            
            $stmt->execute();
            
            return $stmt;
            
        } catch (Exception $e) {
            error_log('Erro ao executar query: ' . $e->getMessage());
            $this->retornarErro('Erro ao processar requisição.', 500);
        }
    }
    
    /**
     * Buscar um registro
     */
    protected function buscarUm($query, $params = [], $tipos = '') {
        $stmt = $this->executarQuery($query, $params, $tipos);
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows > 0) {
            $registro = $resultado->fetch_assoc();
            $stmt->close();
            return $registro;
        }
        
        $stmt->close();
        return null;
    }
    
    /**
     * Buscar múltiplos registros
     */
    protected function buscarTodos($query, $params = [], $tipos = '') {
        $stmt = $this->executarQuery($query, $params, $tipos);
        $resultado = $stmt->get_result();
        
        $registros = [];
        while ($row = $resultado->fetch_assoc()) {
            $registros[] = $row;
        }
        
        $stmt->close();
        return $registros;
    }
    
    /**
     * Inserir registro
     */
    protected function inserir($query, $params = [], $tipos = '') {
        $stmt = $this->executarQuery($query, $params, $tipos);
        $insert_id = $stmt->insert_id;
        $stmt->close();
        
        return $insert_id;
    }
    
    /**
     * Atualizar registro
     */
    protected function atualizar($query, $params = [], $tipos = '') {
        $stmt = $this->executarQuery($query, $params, $tipos);
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        
        return $affected_rows;
    }
    
    /**
     * Deletar registro
     */
    protected function deletar($query, $params = [], $tipos = '') {
        $stmt = $this->executarQuery($query, $params, $tipos);
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        
        return $affected_rows;
    }
    
    /**
     * Sanitizar entrada
     */
    protected function sanitizar($valor) {
        $conexao = $this->getConexao();
        return $conexao->real_escape_string(trim($valor));
    }
    
    /**
     * Registrar log
     */
    protected function registrarLog($tipo, $descricao) {
        $usuario = $this->getUsuario();
        registrar_log($tipo, $descricao, $usuario['nome']);
    }
}

/**
 * Destrutor para garantir fechamento de conexão
 */
register_shutdown_function(function() {
    // Fechar todas as conexões abertas
});
?>
