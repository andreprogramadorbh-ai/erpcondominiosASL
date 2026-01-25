<?php
session_start();
require_once 'config.php';

// Verificar se está logado
if (!isset($_SESSION['associado_id'])) {
    header('Location: login_associado.html');
    exit;
}

$associado_id = $_SESSION['associado_id'];
$associado_nome = $_SESSION['associado_nome'];

try {
    $pdo = getConnection();
    
    // Buscar contratações do associado
    $stmt = $pdo->prepare("
        SELECT c.*, f.nome_empreendimento, f.telefone, f.email, f.segmento
        FROM contratacoes c
        JOIN fornecedores f ON c.id_fornecedor = f.id_fornecedor
        WHERE c.id_associado = ?
        ORDER BY c.data_solicitacao DESC
    ");
    $stmt->execute([$associado_id]);
    $contratacoes = $stmt->fetchAll();
    
} catch (Exception $e) {
    $erro = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Contratações - Sistema de Fornecedores</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 2em;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            background: rgba(255,255,255,0.2);
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .contratacao-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .contratacao-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .fornecedor-nome {
            font-size: 1.3em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9em;
        }

        .status-pendente {
            background: #fff3cd;
            color: #856404;
        }

        .status-aceita {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-executando {
            background: #d4edda;
            color: #155724;
        }

        .status-finalizada {
            background: #e2e3e5;
            color: #383d41;
        }

        .status-cancelada {
            background: #f8d7da;
            color: #721c24;
        }

        .contratacao-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .contratacao-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .contratacao-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div>
                <h1>📋 Minhas Contratações</h1>
                <p>Acompanhe seus serviços contratados</p>
            </div>
            <div class="nav-links">
                <a href="area_associado.php">← Voltar</a>
                <a href="logout.php">Sair</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($contratacoes)): ?>
            <div class="no-results">
                <h3>📝 Nenhuma contratação encontrada</h3>
                <p>Você ainda não fez nenhuma contratação.</p>
                <a href="area_associado.php" class="btn btn-primary" style="margin-top: 20px;">Ver Fornecedores</a>
            </div>
        <?php else: ?>
            <?php foreach ($contratacoes as $contratacao): ?>
                <div class="contratacao-card">
                    <div class="contratacao-header">
                        <div>
                            <div class="fornecedor-nome"><?php echo htmlspecialchars($contratacao['nome_empreendimento']); ?></div>
                            <p style="color: #666; margin: 5px 0;"><?php echo htmlspecialchars($contratacao['segmento']); ?></p>
                        </div>
                        <div class="status status-<?php echo $contratacao['status']; ?>">
                            <?php
                            $status_labels = [
                                'pendente' => 'Aguardando Aprovação',
                                'aceita' => 'Aceita',
                                'executando' => 'Em Execução',
                                'finalizada' => 'Finalizada',
                                'cancelada' => 'Cancelada'
                            ];
                            echo $status_labels[$contratacao['status']] ?? $contratacao['status'];
                            ?>
                        </div>
                    </div>

                    <div class="contratacao-info">
                        <div class="info-item">
                            <span>📅</span>
                            <span>Solicitado em: <?php echo date('d/m/Y H:i', strtotime($contratacao['data_solicitacao'])); ?></span>
                        </div>
                        
                        <?php if ($contratacao['data_aceitacao']): ?>
                            <div class="info-item">
                                <span>✅</span>
                                <span>Aceito em: <?php echo date('d/m/Y H:i', strtotime($contratacao['data_aceitacao'])); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($contratacao['data_finalizacao']): ?>
                            <div class="info-item">
                                <span>🏁</span>
                                <span>Finalizado em: <?php echo date('d/m/Y H:i', strtotime($contratacao['data_finalizacao'])); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="info-item">
                            <span>📱</span>
                            <span><?php echo htmlspecialchars($contratacao['telefone']); ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span>📧</span>
                            <span><?php echo htmlspecialchars($contratacao['email']); ?></span>
                        </div>
                    </div>

                    <div class="actions">
                        <?php if ($contratacao['status'] === 'executando'): ?>
                            <button class="btn btn-success" onclick="finalizarContratacao(<?php echo $contratacao['id_contratacao']; ?>)">
                                Finalizar Serviço
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($contratacao['status'] === 'finalizada'): ?>
                            <a href="avaliar.php?id=<?php echo $contratacao['id_contratacao']; ?>" class="btn btn-primary">
                                Avaliar Fornecedor
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($contratacao['status'] === 'pendente'): ?>
                            <button class="btn btn-danger" onclick="cancelarContratacao(<?php echo $contratacao['id_contratacao']; ?>)">
                                Cancelar Solicitação
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function finalizarContratacao(idContratacao) {
            if (confirm('Confirma a finalização deste serviço?')) {
                fetch('finalizar_contratacao.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id_contratacao=' + idContratacao + '&acao=finalizar'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Serviço finalizado com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Erro ao finalizar: ' + error.message);
                });
            }
        }

        function cancelarContratacao(idContratacao) {
            if (confirm('Confirma o cancelamento desta solicitação?')) {
                fetch('finalizar_contratacao.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id_contratacao=' + idContratacao + '&acao=cancelar'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Solicitação cancelada com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Erro ao cancelar: ' + error.message);
                });
            }
        }
    </script>
</body>
</html>

