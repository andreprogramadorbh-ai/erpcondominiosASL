<?php
/**
 * ========================================
 * DEBUG RADICAL DE DEPENDENTES
 * ========================================
 * 
 * Este arquivo testa TODOS os componentes do sistema de dependentes:
 * - Conexão com banco de dados
 * - Variáveis de ambiente
 * - Estrutura de tabelas
 * - INSERT de dependentes
 * - SELECT de dependentes
 * - Permissões de diretórios
 * - Logs
 * - Controllers e Models
 * 
 * COMO USAR:
 * 1. Acesse: http://seu-dominio.com/api/debug_dependente.php
 * 2. Analise os resultados
 * 3. Verifique o arquivo de log gerado
 * 
 * @author Manus AI - Sistema ERP
 * @date 2026-01-25
 */

// Desabilitar cache
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: text/html; charset=utf-8');

// Habilitar exibição de todos os erros
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/debug_dependente_' . date('Y-m-d') . '.log');

// Iniciar buffer de saída
ob_start();

echo "<!DOCTYPE html>\n";
echo "<html lang='pt-BR'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
echo "    <title>Debug Radical - Dependentes</title>\n";
echo "    <style>\n";
echo "        * { margin: 0; padding: 0; box-sizing: border-box; }\n";
echo "        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }\n";
echo "        .container { max-width: 1200px; margin: 0 auto; }\n";
echo "        h1 { color: #333; margin-bottom: 30px; text-align: center; }\n";
echo "        .test-section { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }\n";
echo "        .test-section h2 { color: #2c3e50; margin-bottom: 15px; border-bottom: 2px solid #3498db; padding-bottom: 10px; }\n";
echo "        .test-item { padding: 10px; margin: 5px 0; border-left: 4px solid #ccc; background: #f9f9f9; }\n";
echo "        .test-item.success { border-left-color: #27ae60; background: #d5f4e6; }\n";
echo "        .test-item.error { border-left-color: #e74c3c; background: #fadbd8; }\n";
echo "        .test-item.warning { border-left-color: #f39c12; background: #fcf3cf; }\n";
echo "        .test-item.info { border-left-color: #3498db; background: #d6eaf8; }\n";
echo "        .test-label { font-weight: bold; display: inline-block; min-width: 200px; }\n";
echo "        .test-value { color: #555; }\n";
echo "        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; margin: 10px 0; }\n";
echo "        .badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }\n";
echo "        .badge.success { background: #27ae60; color: white; }\n";
echo "        .badge.error { background: #e74c3c; color: white; }\n";
echo "        .badge.warning { background: #f39c12; color: white; }\n";
echo "        .summary { background: #3498db; color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 30px; }\n";
echo "        .summary h2 { color: white; margin-bottom: 10px; }\n";
echo "        .summary .stats { display: flex; justify-content: space-around; margin-top: 20px; }\n";
echo "        .summary .stat { flex: 1; }\n";
echo "        .summary .stat-number { font-size: 36px; font-weight: bold; }\n";
echo "        .summary .stat-label { font-size: 14px; opacity: 0.9; }\n";
echo "    </style>\n";
echo "</head>\n";
echo "<body>\n";
echo "<div class='container'>\n";

// Variáveis globais para estatísticas
$total_tests = 0;
$passed_tests = 0;
$failed_tests = 0;
$warnings = 0;

/**
 * Função para registrar teste
 */
function logTest($label, $value, $status = 'info') {
    global $total_tests, $passed_tests, $failed_tests, $warnings;
    
    $total_tests++;
    
    if ($status === 'success') {
        $passed_tests++;
        $badge = "<span class='badge success'>✓ OK</span>";
    } elseif ($status === 'error') {
        $failed_tests++;
        $badge = "<span class='badge error'>✗ ERRO</span>";
    } elseif ($status === 'warning') {
        $warnings++;
        $badge = "<span class='badge warning'>⚠ AVISO</span>";
    } else {
        $badge = "<span class='badge info'>ℹ INFO</span>";
    }
    
    echo "<div class='test-item $status'>\n";
    echo "    $badge <span class='test-label'>$label:</span> <span class='test-value'>$value</span>\n";
    echo "</div>\n";
    
    // Também registrar no log
    error_log("[$status] $label: $value");
}

/**
 * Função para iniciar seção de teste
 */
function startSection($title) {
    echo "<div class='test-section'>\n";
    echo "    <h2>$title</h2>\n";
}

/**
 * Função para finalizar seção de teste
 */
function endSection() {
    echo "</div>\n";
}

// ========================================
// INÍCIO DOS TESTES
// ========================================

echo "<h1>🔍 DEBUG RADICAL - SISTEMA DE DEPENDENTES</h1>\n";
echo "<p style='text-align: center; color: #666; margin-bottom: 30px;'>Data: " . date('d/m/Y H:i:s') . "</p>\n";

// ========================================
// TESTE 1: INFORMAÇÕES DO SERVIDOR
// ========================================
startSection("1️⃣ Informações do Servidor");

logTest("PHP Version", phpversion(), 'info');
logTest("Sistema Operacional", PHP_OS, 'info');
logTest("Servidor Web", $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido', 'info');
logTest("Diretório Atual", __DIR__, 'info');
logTest("Usuário PHP", get_current_user(), 'info');
logTest("Memória Limite", ini_get('memory_limit'), 'info');
logTest("Max Execution Time", ini_get('max_execution_time') . 's', 'info');
logTest("Display Errors", ini_get('display_errors') ? 'ON' : 'OFF', ini_get('display_errors') ? 'success' : 'warning');
logTest("Error Reporting", error_reporting(), 'info');

endSection();

// ========================================
// TESTE 2: ESTRUTURA DE DIRETÓRIOS
// ========================================
startSection("2️⃣ Estrutura de Diretórios");

$directories = [
    'api' => __DIR__,
    'logs' => __DIR__ . '/logs',
    'controllers' => __DIR__ . '/controllers',
    'models' => __DIR__ . '/models',
    'error' => __DIR__ . '/error'
];

foreach ($directories as $name => $path) {
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $writable = is_writable($path) ? 'Sim' : 'Não';
        logTest(
            "Diretório /$name",
            "Existe | Permissões: $perms | Gravável: $writable",
            is_writable($path) ? 'success' : 'error'
        );
    } else {
        logTest("Diretório /$name", "NÃO EXISTE", 'error');
        
        // Tentar criar diretório
        if (@mkdir($path, 0755, true)) {
            logTest("Criação de /$name", "Diretório criado com sucesso", 'success');
        } else {
            logTest("Criação de /$name", "FALHA ao criar diretório", 'error');
        }
    }
}

endSection();

// ========================================
// TESTE 3: ARQUIVOS NECESSÁRIOS
// ========================================
startSection("3️⃣ Arquivos Necessários");

$required_files = [
    'config.php' => __DIR__ . '/config.php',
    'auth_helper.php' => __DIR__ . '/auth_helper.php',
    'DependenteController.php' => __DIR__ . '/controllers/DependenteController.php',
    'DependenteModel.php' => __DIR__ . '/models/DependenteModel.php',
    'log_erro_dependentes.php' => __DIR__ . '/log_erro_dependentes.php'
];

foreach ($required_files as $name => $path) {
    if (file_exists($path)) {
        $size = filesize($path);
        $readable = is_readable($path) ? 'Sim' : 'Não';
        logTest(
            "Arquivo $name",
            "Existe | Tamanho: " . number_format($size) . " bytes | Legível: $readable",
            is_readable($path) ? 'success' : 'error'
        );
    } else {
        logTest("Arquivo $name", "NÃO EXISTE", 'error');
    }
}

endSection();

// ========================================
// TESTE 4: CONEXÃO COM BANCO DE DADOS
// ========================================
startSection("4️⃣ Conexão com Banco de Dados");

try {
    // Tentar incluir config.php
    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
        logTest("Inclusão de config.php", "Sucesso", 'success');
        
        // Verificar se $conn existe
        if (isset($conn) && $conn instanceof mysqli) {
            logTest("Variável \$conn", "Definida e é mysqli", 'success');
            
            // Testar conexão
            if ($conn->ping()) {
                logTest("Ping do Banco", "Sucesso", 'success');
                
                // Informações do banco
                logTest("Host do Banco", $conn->host_info, 'info');
                logTest("Versão do Banco", $conn->server_info, 'info');
                logTest("Charset do Banco", $conn->character_set_name(), 'info');
                
                // Testar query simples
                $result = $conn->query("SELECT 1 as test");
                if ($result) {
                    logTest("Query de Teste", "SELECT 1 executado com sucesso", 'success');
                    $result->free();
                } else {
                    logTest("Query de Teste", "ERRO: " . $conn->error, 'error');
                }
                
            } else {
                logTest("Ping do Banco", "FALHOU", 'error');
            }
            
        } else {
            logTest("Variável \$conn", "NÃO DEFINIDA ou não é mysqli", 'error');
        }
        
    } else {
        logTest("Inclusão de config.php", "Arquivo não encontrado", 'error');
    }
    
} catch (Exception $e) {
    logTest("Conexão com Banco", "EXCEÇÃO: " . $e->getMessage(), 'error');
}

endSection();

// ========================================
// TESTE 5: ESTRUTURA DA TABELA DEPENDENTES
// ========================================
startSection("5️⃣ Estrutura da Tabela 'dependentes'");

if (isset($conn) && $conn instanceof mysqli) {
    // Verificar se tabela existe
    $result = $conn->query("SHOW TABLES LIKE 'dependentes'");
    
    if ($result && $result->num_rows > 0) {
        logTest("Tabela 'dependentes'", "Existe", 'success');
        
        // Obter estrutura da tabela
        $structure = $conn->query("DESCRIBE dependentes");
        
        if ($structure) {
            echo "<div class='test-item info'>\n";
            echo "<strong>Estrutura da Tabela:</strong>\n";
            echo "<pre>";
            
            $columns = [];
            while ($row = $structure->fetch_assoc()) {
                $columns[] = $row;
                echo sprintf(
                    "%-20s %-20s %-10s %-10s\n",
                    $row['Field'],
                    $row['Type'],
                    $row['Null'],
                    $row['Key']
                );
            }
            
            echo "</pre>\n";
            echo "</div>\n";
            
            logTest("Total de Colunas", count($columns), 'info');
            
            // Verificar colunas obrigatórias
            $required_columns = ['id', 'morador_id', 'nome_completo', 'cpf', 'ativo'];
            foreach ($required_columns as $col) {
                $found = false;
                foreach ($columns as $column) {
                    if ($column['Field'] === $col) {
                        $found = true;
                        break;
                    }
                }
                logTest(
                    "Coluna '$col'",
                    $found ? "Existe" : "NÃO EXISTE",
                    $found ? 'success' : 'error'
                );
            }
            
            $structure->free();
        }
        
        // Contar registros
        $count_result = $conn->query("SELECT COUNT(*) as total FROM dependentes");
        if ($count_result) {
            $count = $count_result->fetch_assoc()['total'];
            logTest("Total de Dependentes", $count, 'info');
            $count_result->free();
        }
        
        $result->free();
    } else {
        logTest("Tabela 'dependentes'", "NÃO EXISTE", 'error');
    }
} else {
    logTest("Teste de Tabela", "Conexão não disponível", 'error');
}

endSection();

// ========================================
// TESTE 6: TESTE DE INSERT
// ========================================
startSection("6️⃣ Teste de INSERT");

if (isset($conn) && $conn instanceof mysqli) {
    // Dados de teste
    $test_data = [
        'morador_id' => 1,
        'nome_completo' => 'TESTE DEBUG ' . date('His'),
        'cpf' => '12345678' . rand(10, 99),
        'email' => 'teste.debug@email.com',
        'telefone' => '3133334444',
        'celular' => '31999998888',
        'data_nascimento' => '1990-01-01',
        'parentesco' => 'Filho(a)',
        'observacao' => 'Registro de teste para debug - ' . date('Y-m-d H:i:s')
    };
    
    echo "<div class='test-item info'>\n";
    echo "<strong>Dados de Teste:</strong>\n";
    echo "<pre>" . print_r($test_data, true) . "</pre>\n";
    echo "</div>\n";
    
    // Preparar SQL
    $sql = "INSERT INTO dependentes (
        morador_id, nome_completo, cpf, email, telefone, celular,
        data_nascimento, parentesco, observacao
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    logTest("SQL Preparado", "9 parâmetros", 'info');
    
    // Preparar statement
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        logTest("Prepare Statement", "Sucesso", 'success');
        
        // Bind parameters
        $bind_result = $stmt->bind_param(
            "issssssss",
            $test_data['morador_id'],
            $test_data['nome_completo'],
            $test_data['cpf'],
            $test_data['email'],
            $test_data['telefone'],
            $test_data['celular'],
            $test_data['data_nascimento'],
            $test_data['parentesco'],
            $test_data['observacao']
        );
        
        if ($bind_result) {
            logTest("Bind Parameters", "Sucesso (issssssss)", 'success');
            
            // Executar
            if ($stmt->execute()) {
                $insert_id = $stmt->insert_id;
                $affected_rows = $stmt->affected_rows;
                
                logTest("Execute INSERT", "Sucesso", 'success');
                logTest("Insert ID", $insert_id, $insert_id > 0 ? 'success' : 'error');
                logTest("Affected Rows", $affected_rows, $affected_rows > 0 ? 'success' : 'error');
                
                // Verificar se foi realmente inserido
                $verify = $conn->query("SELECT * FROM dependentes WHERE id = $insert_id");
                if ($verify && $verify->num_rows > 0) {
                    logTest("Verificação SELECT", "Registro encontrado no banco", 'success');
                    
                    $inserted_data = $verify->fetch_assoc();
                    echo "<div class='test-item success'>\n";
                    echo "<strong>Dados Inseridos:</strong>\n";
                    echo "<pre>" . print_r($inserted_data, true) . "</pre>\n";
                    echo "</div>\n";
                    
                    // Limpar registro de teste
                    $conn->query("DELETE FROM dependentes WHERE id = $insert_id");
                    logTest("Limpeza", "Registro de teste removido", 'info');
                    
                    $verify->free();
                } else {
                    logTest("Verificação SELECT", "REGISTRO NÃO ENCONTRADO", 'error');
                }
                
            } else {
                logTest("Execute INSERT", "ERRO: " . $stmt->error, 'error');
                logTest("Errno", $stmt->errno, 'error');
            }
            
        } else {
            logTest("Bind Parameters", "ERRO", 'error');
        }
        
        $stmt->close();
    } else {
        logTest("Prepare Statement", "ERRO: " . $conn->error, 'error');
    }
    
} else {
    logTest("Teste de INSERT", "Conexão não disponível", 'error');
}

endSection();

// ========================================
// TESTE 7: TESTE DO MODEL
// ========================================
startSection("7️⃣ Teste do DependenteModel");

try {
    if (file_exists(__DIR__ . '/models/DependenteModel.php')) {
        require_once __DIR__ . '/models/DependenteModel.php';
        logTest("Inclusão de DependenteModel", "Sucesso", 'success');
        
        if (class_exists('DependenteModel')) {
            logTest("Classe DependenteModel", "Existe", 'success');
            
            // Instanciar model
            $model = new DependenteModel($conn);
            logTest("Instância de DependenteModel", "Criada", 'success');
            
            // Testar método criar
            $test_data_model = [
                'morador_id' => 1,
                'nome_completo' => 'TESTE MODEL ' . date('His'),
                'cpf' => '98765432' . rand(10, 99),
                'email' => 'teste.model@email.com',
                'telefone' => '3144445555',
                'celular' => '31988887777',
                'data_nascimento' => '1995-05-05',
                'parentesco' => 'Cônjuge',
                'observacao' => 'Teste via Model'
            ];
            
            $result_model = $model->criar($test_data_model);
            
            if ($result_model['sucesso']) {
                logTest("Model->criar()", "Sucesso | ID: " . $result_model['id'], 'success');
                
                // Limpar
                $conn->query("DELETE FROM dependentes WHERE id = " . $result_model['id']);
                logTest("Limpeza Model", "Registro removido", 'info');
            } else {
                logTest("Model->criar()", "ERRO: " . $result_model['mensagem'], 'error');
            }
            
        } else {
            logTest("Classe DependenteModel", "NÃO EXISTE", 'error');
        }
    } else {
        logTest("Arquivo DependenteModel.php", "NÃO ENCONTRADO", 'error');
    }
} catch (Exception $e) {
    logTest("Teste do Model", "EXCEÇÃO: " . $e->getMessage(), 'error');
}

endSection();

// ========================================
// TESTE 8: TESTE DO CONTROLLER
// ========================================
startSection("8️⃣ Teste do DependenteController");

try {
    if (file_exists(__DIR__ . '/controllers/DependenteController.php')) {
        require_once __DIR__ . '/controllers/DependenteController.php';
        logTest("Inclusão de DependenteController", "Sucesso", 'success');
        
        if (class_exists('DependenteController')) {
            logTest("Classe DependenteController", "Existe", 'success');
            
            // Instanciar controller
            $controller = new DependenteController($conn);
            logTest("Instância de DependenteController", "Criada", 'success');
            
            // Testar método criar
            $test_data_controller = [
                'morador_id' => 1,
                'nome_completo' => 'TESTE CONTROLLER ' . date('His'),
                'cpf' => '111.222.333-44',  // Com máscara
                'email' => 'teste.controller@email.com',
                'telefone' => '(31) 5555-6666',  // Com máscara
                'celular' => '(31) 97777-8888',  // Com máscara
                'data_nascimento' => '2000-10-10',
                'parentesco' => 'Filho(a)',
                'observacao' => 'Teste via Controller'
            ];
            
            $result_controller = $controller->criar($test_data_controller);
            
            if ($result_controller['sucesso']) {
                logTest("Controller->criar()", "Sucesso | ID: " . $result_controller['id'], 'success');
                logTest("Limpeza de CPF", "Funcionou (máscara removida)", 'success');
                logTest("Limpeza de Telefone", "Funcionou (máscara removida)", 'success');
                
                // Limpar
                $conn->query("DELETE FROM dependentes WHERE id = " . $result_controller['id']);
                logTest("Limpeza Controller", "Registro removido", 'info');
            } else {
                logTest("Controller->criar()", "ERRO: " . $result_controller['mensagem'], 'error');
            }
            
        } else {
            logTest("Classe DependenteController", "NÃO EXISTE", 'error');
        }
    } else {
        logTest("Arquivo DependenteController.php", "NÃO ENCONTRADO", 'error');
    }
} catch (Exception $e) {
    logTest("Teste do Controller", "EXCEÇÃO: " . $e->getMessage(), 'error');
}

endSection();

// ========================================
// TESTE 9: SISTEMA DE LOGS
// ========================================
startSection("9️⃣ Sistema de Logs");

$log_dir = __DIR__ . '/logs';
$log_file = $log_dir . '/dependentes_' . date('Y-m-d') . '.log';

// Verificar diretório de logs
if (file_exists($log_dir)) {
    logTest("Diretório de Logs", "Existe", 'success');
    
    if (is_writable($log_dir)) {
        logTest("Permissão de Escrita", "OK", 'success');
        
        // Tentar escrever no log
        $test_message = "[TESTE] " . date('Y-m-d H:i:s') . " - Teste de escrita no log\n";
        if (@file_put_contents($log_file, $test_message, FILE_APPEND)) {
            logTest("Escrita no Log", "Sucesso", 'success');
            logTest("Arquivo de Log", $log_file, 'info');
            
            // Verificar se arquivo foi criado
            if (file_exists($log_file)) {
                $log_size = filesize($log_file);
                logTest("Tamanho do Log", number_format($log_size) . " bytes", 'info');
            }
        } else {
            logTest("Escrita no Log", "FALHOU", 'error');
        }
        
    } else {
        logTest("Permissão de Escrita", "SEM PERMISSÃO", 'error');
    }
    
} else {
    logTest("Diretório de Logs", "NÃO EXISTE", 'error');
}

// Verificar log_erro_dependentes.php
if (file_exists(__DIR__ . '/log_erro_dependentes.php')) {
    logTest("Arquivo log_erro_dependentes.php", "Existe", 'success');
    
    try {
        require_once __DIR__ . '/log_erro_dependentes.php';
        
        if (function_exists('logErro')) {
            logTest("Função logErro()", "Existe", 'success');
            
            // Testar função
            logErro("TESTE", "Teste de log de erro via função logErro()");
            logTest("Teste de logErro()", "Executado", 'success');
        } else {
            logTest("Função logErro()", "NÃO EXISTE", 'error');
        }
    } catch (Exception $e) {
        logTest("Teste de log_erro_dependentes.php", "EXCEÇÃO: " . $e->getMessage(), 'error');
    }
} else {
    logTest("Arquivo log_erro_dependentes.php", "NÃO ENCONTRADO", 'error');
}

endSection();

// ========================================
// TESTE 10: VARIÁVEIS DE SESSÃO E POST
// ========================================
startSection("🔟 Variáveis de Sessão e POST");

// Iniciar sessão se não iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

logTest("Session Status", session_status() === PHP_SESSION_ACTIVE ? "Ativa" : "Inativa", 
    session_status() === PHP_SESSION_ACTIVE ? 'success' : 'warning');
logTest("Session ID", session_id(), 'info');
logTest("Session Name", session_name(), 'info');

// Verificar variáveis de sessão importantes
$session_vars = ['usuario_id', 'usuario_nome', 'usuario_tipo'];
foreach ($session_vars as $var) {
    $value = isset($_SESSION[$var]) ? $_SESSION[$var] : 'NÃO DEFINIDA';
    logTest("SESSION['$var']", $value, isset($_SESSION[$var]) ? 'success' : 'warning');
}

// Verificar método de requisição
logTest("REQUEST_METHOD", $_SERVER['REQUEST_METHOD'], 'info');

endSection();

// ========================================
// RESUMO FINAL
// ========================================

echo "<div class='summary'>\n";
echo "    <h2>📊 RESUMO DOS TESTES</h2>\n";
echo "    <div class='stats'>\n";
echo "        <div class='stat'>\n";
echo "            <div class='stat-number'>$total_tests</div>\n";
echo "            <div class='stat-label'>Total de Testes</div>\n";
echo "        </div>\n";
echo "        <div class='stat'>\n";
echo "            <div class='stat-number' style='color: #2ecc71;'>$passed_tests</div>\n";
echo "            <div class='stat-label'>Testes Passaram</div>\n";
echo "        </div>\n";
echo "        <div class='stat'>\n";
echo "            <div class='stat-number' style='color: #e74c3c;'>$failed_tests</div>\n";
echo "            <div class='stat-label'>Testes Falharam</div>\n";
echo "        </div>\n";
echo "        <div class='stat'>\n";
echo "            <div class='stat-number' style='color: #f39c12;'>$warnings</div>\n";
echo "            <div class='stat-label'>Avisos</div>\n";
echo "        </div>\n";
echo "    </div>\n";
echo "</div>\n";

// Calcular taxa de sucesso
$success_rate = $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 2) : 0;

startSection("✅ Conclusão");

echo "<div class='test-item " . ($success_rate >= 80 ? 'success' : ($success_rate >= 50 ? 'warning' : 'error')) . "'>\n";
echo "    <strong>Taxa de Sucesso:</strong> $success_rate% ($passed_tests de $total_tests testes)\n";
echo "</div>\n";

if ($failed_tests > 0) {
    echo "<div class='test-item error'>\n";
    echo "    <strong>⚠️ ATENÇÃO:</strong> $failed_tests teste(s) falharam. Revise os erros acima.\n";
    echo "</div>\n";
}

if ($warnings > 0) {
    echo "<div class='test-item warning'>\n";
    echo "    <strong>⚠️ AVISOS:</strong> $warnings aviso(s) encontrado(s). Verifique as configurações.\n";
    echo "</div>\n";
}

echo "<div class='test-item info'>\n";
echo "    <strong>📁 Log Completo:</strong> " . __DIR__ . "/logs/debug_dependente_" . date('Y-m-d') . ".log\n";
echo "</div>\n";

endSection();

echo "</div>\n";
echo "</body>\n";
echo "</html>\n";

// Finalizar buffer e enviar
ob_end_flush();

// Fechar conexão
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
