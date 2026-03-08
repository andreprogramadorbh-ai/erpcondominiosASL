<?php
/**
 * =====================================================
 * API DE INTEGRAÇÃO DE E-MAIL (SMTP) - ERP INLAUDO
 * =====================================================
 * 
 * Ações disponíveis:
 * - buscar    : Retorna configuração atual (sem expor senha)
 * - salvar    : Salva configuração com senha criptografada
 * - testar    : Envia e-mail de teste
 * - gerar_key : Gera uma APP_ENCRYPTION_KEY segura (uso administrativo)
 * 
 * A senha SMTP é armazenada criptografada com AES-256-CBC.
 * A chave de criptografia deve estar definida no .env como:
 *   APP_ENCRYPTION_KEY=<string de 32+ caracteres>
 */

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Sao_Paulo');

require_once 'config.php';

// =====================================================
// FUNÇÕES DE CRIPTOGRAFIA
// =====================================================

/**
 * Obtém a chave de criptografia do ambiente
 */
function obterChaveCriptografia(): string {
    $key = getenv('APP_ENCRYPTION_KEY') ?: getenv('APP_KEY') ?: '';
    if (empty($key)) {
        retornar_json(false, 'Criptografia não configurada. Defina APP_KEY ou APP_ENCRYPTION_KEY no .env.');
    }
    // Garante 32 bytes para AES-256
    return substr(hash('sha256', $key, true), 0, 32);
}

/**
 * Criptografa um valor com AES-256-CBC
 */
function criptografar(string $valor): string {
    $chave = obterChaveCriptografia();
    $iv    = random_bytes(16);
    $cifra = openssl_encrypt($valor, 'AES-256-CBC', $chave, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $cifra);
}

/**
 * Descriptografa um valor com AES-256-CBC
 */
function descriptografar(string $valorCifrado): string {
    $chave  = obterChaveCriptografia();
    $dados  = base64_decode($valorCifrado);
    $iv     = substr($dados, 0, 16);
    $cifra  = substr($dados, 16);
    $plain  = openssl_decrypt($cifra, 'AES-256-CBC', $chave, OPENSSL_RAW_DATA, $iv);
    return $plain !== false ? $plain : '';
}

// =====================================================
// RESPOSTA PADRÃO
// =====================================================

function resposta(bool $sucesso, string $mensagem, $dados = null): void {
    echo json_encode([
        'sucesso'  => $sucesso,
        'mensagem' => $mensagem,
        'dados'    => $dados,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// =====================================================
// CONEXÃO COM O BANCO
// =====================================================

$conexao = conectar_banco();
if (!$conexao) {
    resposta(false, 'Erro ao conectar ao banco de dados.');
}

// =====================================================
// ROTEAMENTO DE AÇÕES
// =====================================================

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

switch ($acao) {
    case 'buscar':
        buscarConfig($conexao);
        break;
    case 'salvar':
        salvarConfig($conexao);
        break;
    case 'testar':
        testarSMTP($conexao);
        break;
    case 'gerar_key':
        gerarChave();
        break;
    default:
        resposta(false, 'Ação inválida ou não informada.');
}

// =====================================================
// BUSCAR CONFIGURAÇÃO
// =====================================================

function buscarConfig($conexao): void {
    $sql = "SELECT id, smtp_host, smtp_port, smtp_usuario, smtp_de_email,
                   smtp_de_nome, smtp_seguranca, smtp_ativo,
                   data_atualizacao
            FROM configuracao_smtp
            LIMIT 1";

    $resultado = mysqli_query($conexao, $sql);

    if (!$resultado) {
        error_log('[api_integracao_email] Erro ao buscar config: ' . mysqli_error($conexao));
        resposta(false, 'Erro ao buscar configuração.');
    }

    if (mysqli_num_rows($resultado) === 0) {
        resposta(true, 'Nenhuma configuração encontrada.', null);
    }

    $dados = mysqli_fetch_assoc($resultado);

    // Indica se há senha salva (sem expô-la)
    $sqlSenha = "SELECT smtp_senha FROM configuracao_smtp WHERE id = " . intval($dados['id']);
    $resSenha = mysqli_query($conexao, $sqlSenha);
    $rowSenha = mysqli_fetch_assoc($resSenha);
    $dados['tem_senha'] = !empty($rowSenha['smtp_senha']);

    resposta(true, 'Configuração carregada com sucesso.', $dados);
}

// =====================================================
// SALVAR CONFIGURAÇÃO
// =====================================================

function salvarConfig($conexao): void {
    // Coletar e sanitizar campos
    $smtp_host      = sanitizar($conexao, $_POST['smtp_host']      ?? '');
    $smtp_port      = intval($_POST['smtp_port']                   ?? 587);
    $smtp_usuario   = sanitizar($conexao, $_POST['smtp_usuario']   ?? '');
    $smtp_senha_raw = trim($_POST['smtp_senha']                    ?? '');
    $smtp_de_email  = sanitizar($conexao, $_POST['smtp_de_email']  ?? '');
    $smtp_de_nome   = sanitizar($conexao, $_POST['smtp_de_nome']   ?? '');
    $smtp_seguranca = sanitizar($conexao, $_POST['smtp_seguranca'] ?? 'tls');
    $smtp_ativo     = intval($_POST['smtp_ativo']                  ?? 1);

    // Validações
    if (empty($smtp_host))     { resposta(false, 'Servidor SMTP é obrigatório.'); }
    if (empty($smtp_usuario))  { resposta(false, 'Usuário/E-mail é obrigatório.'); }
    if (empty($smtp_de_email)) { resposta(false, 'E-mail remetente é obrigatório.'); }
    if (empty($smtp_de_nome))  { resposta(false, 'Nome remetente é obrigatório.'); }
    if (!in_array($smtp_seguranca, ['tls', 'ssl', 'none'])) {
        resposta(false, 'Protocolo de segurança inválido.');
    }

    // Verificar se já existe registro
    $sqlCheck = "SELECT id, smtp_senha FROM configuracao_smtp LIMIT 1";
    $resCheck  = mysqli_query($conexao, $sqlCheck);
    $rowCheck  = $resCheck ? mysqli_fetch_assoc($resCheck) : null;

    // Criptografar senha apenas se uma nova foi fornecida
    if (!empty($smtp_senha_raw)) {
        $smtp_senha_cifrada = criptografar($smtp_senha_raw);
    } elseif ($rowCheck && !empty($rowCheck['smtp_senha'])) {
        // Manter a senha existente
        $smtp_senha_cifrada = $rowCheck['smtp_senha'];
    } else {
        resposta(false, 'Senha/Token do App é obrigatória.');
    }

    $smtp_senha_escaped = sanitizar($conexao, $smtp_senha_cifrada);

    if ($rowCheck) {
        // UPDATE
        $id  = intval($rowCheck['id']);
        $sql = "UPDATE configuracao_smtp SET
                    smtp_host      = '$smtp_host',
                    smtp_port      = $smtp_port,
                    smtp_usuario   = '$smtp_usuario',
                    smtp_senha     = '$smtp_senha_escaped',
                    smtp_de_email  = '$smtp_de_email',
                    smtp_de_nome   = '$smtp_de_nome',
                    smtp_seguranca = '$smtp_seguranca',
                    smtp_ativo     = $smtp_ativo
                WHERE id = $id";
    } else {
        // INSERT
        $sql = "INSERT INTO configuracao_smtp
                    (smtp_host, smtp_port, smtp_usuario, smtp_senha,
                     smtp_de_email, smtp_de_nome, smtp_seguranca, smtp_ativo)
                VALUES
                    ('$smtp_host', $smtp_port, '$smtp_usuario', '$smtp_senha_escaped',
                     '$smtp_de_email', '$smtp_de_nome', '$smtp_seguranca', $smtp_ativo)";
    }

    if (mysqli_query($conexao, $sql)) {
        $id_salvo = $rowCheck ? $rowCheck['id'] : mysqli_insert_id($conexao);
        error_log('[api_integracao_email] Configuração salva. ID: ' . $id_salvo);
        resposta(true, 'Configuração salva com sucesso!', ['id' => $id_salvo]);
    } else {
        $erro = mysqli_error($conexao);
        error_log('[api_integracao_email] Erro ao salvar: ' . $erro);
        resposta(false, 'Erro ao salvar configuração: ' . $erro);
    }
}

// =====================================================
// TESTAR SMTP
// =====================================================

function testarSMTP($conexao): void {
    $email_destino = trim($_GET['email'] ?? $_POST['email'] ?? '');

    if (empty($email_destino) || !filter_var($email_destino, FILTER_VALIDATE_EMAIL)) {
        resposta(false, 'Informe um e-mail de destino válido para o teste.');
    }

    // Buscar configuração com senha criptografada
    $sql = "SELECT * FROM configuracao_smtp LIMIT 1";
    $res = mysqli_query($conexao, $sql);

    if (!$res || mysqli_num_rows($res) === 0) {
        resposta(false, 'Nenhuma configuração SMTP encontrada. Salve as configurações primeiro.');
    }

    $config = mysqli_fetch_assoc($res);

    // Descriptografar a senha
    $config['smtp_senha'] = descriptografar($config['smtp_senha']);

    if (empty($config['smtp_senha'])) {
        resposta(false, 'Não foi possível descriptografar a senha. Verifique a APP_ENCRYPTION_KEY no .env.');
    }

    try {
        require_once __DIR__ . '/../PHPMailer/Exception.php';
        require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
        require_once __DIR__ . '/../PHPMailer/SMTP.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_usuario'];
        $mail->Password   = $config['smtp_senha'];
        $mail->Port       = intval($config['smtp_port']);

        if ($config['smtp_seguranca'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($config['smtp_seguranca'] === 'tls') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->setFrom($config['smtp_de_email'], $config['smtp_de_nome']);
        $mail->addAddress($email_destino);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = '✅ Teste de E-mail - ERP InLaudo';
        $mail->Body    = '
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;">
            <div style="background:linear-gradient(135deg,#1e40af,#3b82f6);padding:24px;border-radius:12px 12px 0 0;text-align:center;">
                <h1 style="color:#fff;margin:0;font-size:1.5rem;">✅ Teste de E-mail</h1>
                <p style="color:#bfdbfe;margin:8px 0 0;">ERP InLaudo</p>
            </div>
            <div style="background:#f8fafc;padding:24px;border-radius:0 0 12px 12px;border:1px solid #e2e8f0;">
                <p style="color:#1e293b;">Parabéns! Se você recebeu este e-mail, o servidor SMTP está configurado corretamente.</p>
                <table style="width:100%;border-collapse:collapse;margin-top:16px;">
                    <tr><td style="padding:8px;color:#64748b;font-size:0.9rem;">Servidor</td><td style="padding:8px;font-weight:600;">' . htmlspecialchars($config['smtp_host']) . '</td></tr>
                    <tr style="background:#fff;"><td style="padding:8px;color:#64748b;font-size:0.9rem;">Porta</td><td style="padding:8px;font-weight:600;">' . intval($config['smtp_port']) . '</td></tr>
                    <tr><td style="padding:8px;color:#64748b;font-size:0.9rem;">Segurança</td><td style="padding:8px;font-weight:600;">' . strtoupper(htmlspecialchars($config['smtp_seguranca'])) . '</td></tr>
                    <tr style="background:#fff;"><td style="padding:8px;color:#64748b;font-size:0.9rem;">Data/Hora</td><td style="padding:8px;font-weight:600;">' . date('d/m/Y H:i:s') . '</td></tr>
                </table>
                <p style="color:#94a3b8;font-size:0.8rem;margin-top:20px;text-align:center;">Este é um e-mail automático gerado pelo ERP InLaudo. Não responda.</p>
            </div>
        </div>';

        $mail->send();

        // Registrar no log
        $dest_escaped = sanitizar($conexao, $email_destino);
        mysqli_query($conexao, "INSERT INTO email_log (destinatario, assunto, tipo, status)
            VALUES ('$dest_escaped', 'Teste de E-mail - ERP InLaudo', 'teste', 'enviado')");

        error_log('[api_integracao_email] E-mail de teste enviado para: ' . $email_destino);
        resposta(true, 'E-mail de teste enviado com sucesso para ' . $email_destino . '!');

    } catch (Exception $e) {
        $erro = $e->getMessage();
        error_log('[api_integracao_email] Erro ao enviar e-mail de teste: ' . $erro);

        // Registrar erro no log
        $dest_escaped = sanitizar($conexao, $email_destino);
        $erro_escaped = sanitizar($conexao, $erro);
        mysqli_query($conexao, "INSERT INTO email_log (destinatario, assunto, tipo, status, erro_mensagem)
            VALUES ('$dest_escaped', 'Teste de E-mail - ERP InLaudo', 'teste', 'erro', '$erro_escaped')");

        resposta(false, 'Erro ao enviar e-mail: ' . $erro);
    }
}

// =====================================================
// GERAR CHAVE DE CRIPTOGRAFIA
// =====================================================

function gerarChave(): void {
    $bytes = random_bytes(32);
    $key   = base64_encode($bytes);
    resposta(true, 'Chave gerada com sucesso. Adicione ao seu .env e reinicie o servidor.', [
        'app_encryption_key' => $key,
        'env_line'           => 'APP_ENCRYPTION_KEY=' . $key,
        'aviso'              => 'ATENÇÃO: Guarde esta chave com segurança. Perder a chave significa perder acesso às senhas criptografadas.',
    ]);
}
?>
