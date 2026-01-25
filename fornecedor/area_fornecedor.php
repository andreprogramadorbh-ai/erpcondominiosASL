<?php
session_start();
require_once 'config.php';

// Verificar se é um fornecedor (usando ID do fornecedor na sessão)
if (!isset($_SESSION['fornecedor_id'])) {
    header('Location: login_fornecedor.html');
    exit;
}

$fornecedor_id = $_SESSION['fornecedor_id'];
$fornecedor_nome = $_SESSION['fornecedor_nome'];

try {
    $pdo = getConnection();
    
    // Buscar dados do fornecedor
    $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id_fornecedor = ?");
    $stmt->execute([$fornecedor_id]);
    $fornecedor = $stmt->fetch();
    
    // Buscar contratações pendentes
    $stmt = $pdo->prepare("
        SELECT c.*, a.nome, a.unidade, a.telefone, a.email
        FROM contratacoes c
        JOIN associados a ON c.id_associado = a.id_associado
        WHERE c.id_fornecedor = ? AND c.status = 'pendente'
        ORDER BY c.data_solicitacao DESC
    ");
    $stmt->execute([$fornecedor_id]);
    $contratacoes_pendentes = $stmt->fetchAll();
    
    // Buscar contratações em andamento
    $stmt = $pdo->prepare("
        SELECT c.*, a.nome, a.unidade, a.telefone, a.email
        FROM contratacoes c
        JOIN associados a ON c.id_associado = a.id_associado
        WHERE c.id_fornecedor = ? AND c.status IN ('aceita', 'executando')
        ORDER BY c.data_solicitacao DESC
    ");
    $stmt->execute([$fornecedor_id]);
    $contratacoes_andamento = $stmt->fetchAll();
    
    // Buscar avaliações recentes
    $stmt = $pdo->prepare("
        SELECT av.*, a.nome, c.data_finalizacao
        FROM avaliacoes av
        JOIN associados a ON av.id_associado = a.id_associado
        JOIN contratacoes c ON av.id_contratacao = c.id_contratacao
        WHERE av.id_fornecedor = ?
        ORDER BY av.data_avaliacao DESC
        LIMIT 5
    ");
    $stmt->execute([$fornecedor_id]);
    $avaliacoes_recentes = $stmt->fetchAll();
    
} catch (Exception $e) {
    $erro = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Fornecedor - Sistema de Fornecedores</title>
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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

        .user-info {
            text-align: right;
        }

        .user-info p {
            margin: 5px 0;
            opacity: 0.9;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 5px;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 1.1em;
        }

        .section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .section h2 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .contratacao-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #28a745;
        }

        .contratacao-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .associado-nome {
            font-weight: bold;
            color: #333;
            font-size: 1.1em;
        }

        .contratacao-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin: 10px 0;
            color: #666;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .avaliacao-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .estrelas {
            color: #ffc107;
            font-size: 1.2em;
            margin-bottom: 5px;
        }

        .no-items {
            text-align: center;
            color: #666;
            padding: 20px;
        }

        .desconto-section {
            background: #e8f5e8;
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .desconto-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .desconto-form input {
            padding: 10px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
        }

        .desconto-form input:focus {
            outline: none;
            border-color: #28a745;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .contratacao-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .desconto-form {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div>
                <h1>🏢 Área do Fornecedor</h1>
                <p><?php echo htmlspecialchars($fornecedor_nome); ?></p>
            </div>
            <div class="user-info">
                <p><strong>ID:</strong> <?php echo $fornecedor_id; ?></p>
                <p><strong>Avaliação:</strong> ⭐ <?php echo number_format($fornecedor['media_avaliacao'], 1); ?> (<?php echo $fornecedor['total_avaliacoes']; ?>)</p>
                <a href="logout_fornecedor.php" class="logout-btn">Sair</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($contratacoes_pendentes); ?></div>
                <div class="stat-label">Solicitações Pendentes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($contratacoes_andamento); ?></div>
                <div class="stat-label">Serviços em Andamento</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($fornecedor['media_avaliacao'], 1); ?></div>
                <div class="stat-label">Avaliação Média</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $fornecedor['total_avaliacoes']; ?></div>
                <div class="stat-label">Total de Avaliações</div>
            </div>
        </div>

        <!-- Sistema de Desconto -->
        <div class="desconto-section">
            <h3>💰 Sistema de Desconto para Associados</h3>
            <p style="margin-bottom: 15px;">Digite o ID do associado para aplicar desconto e registrar a venda:</p>
            <div class="desconto-form">
                <input type="number" id="idAssociado" placeholder="ID do Associado" min="1">
                <button class="btn btn-success" onclick="aplicarDesconto()">Aplicar Desconto</button>
            </div>
            <div id="resultadoDesconto" style="margin-top: 15px;"></div>
        </div>

        <!-- Solicitações Pendentes -->
        <div class="section">
            <h2>⏳ Solicitações Pendentes</h2>
            <?php if (empty($contratacoes_pendentes)): ?>
                <div class="no-items">Nenhuma solicitação pendente</div>
            <?php else: ?>
                <?php foreach ($contratacoes_pendentes as $contratacao): ?>
                    <div class="contratacao-item">
                        <div class="contratacao-header">
                            <div class="associado-nome"><?php echo htmlspecialchars($contratacao['nome']); ?></div>
                        </div>
                        <div class="contratacao-info">
                            <div>📍 <?php echo htmlspecialchars($contratacao['unidade']); ?></div>
                            <div>📱 <?php echo htmlspecialchars($contratacao['telefone']); ?></div>
                            <div>📧 <?php echo htmlspecialchars($contratacao['email']); ?></div>
                            <div>📅 <?php echo date('d/m/Y H:i', strtotime($contratacao['data_solicitacao'])); ?></div>
                        </div>
                        <div class="actions">
                            <button class="btn btn-success" onclick="responderContratacao(<?php echo $contratacao['id_contratacao']; ?>, 'aceitar')">
                                Aceitar
                            </button>
                            <button class="btn btn-danger" onclick="responderContratacao(<?php echo $contratacao['id_contratacao']; ?>, 'recusar')">
                                Recusar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Serviços em Andamento -->
        <div class="section">
            <h2>🔄 Serviços em Andamento</h2>
            <?php if (empty($contratacoes_andamento)): ?>
                <div class="no-items">Nenhum serviço em andamento</div>
            <?php else: ?>
                <?php foreach ($contratacoes_andamento as $contratacao): ?>
                    <div class="contratacao-item">
                        <div class="contratacao-header">
                            <div class="associado-nome"><?php echo htmlspecialchars($contratacao['nome']); ?></div>
                            <div style="color: #28a745; font-weight: bold;">
                                <?php echo $contratacao['status'] === 'aceita' ? 'Aceita' : 'Em Execução'; ?>
                            </div>
                        </div>
                        <div class="contratacao-info">
                            <div>📍 <?php echo htmlspecialchars($contratacao['unidade']); ?></div>
                            <div>📱 <?php echo htmlspecialchars($contratacao['telefone']); ?></div>
                            <div>📧 <?php echo htmlspecialchars($contratacao['email']); ?></div>
                            <div>📅 Aceita em: <?php echo date('d/m/Y H:i', strtotime($contratacao['data_aceitacao'])); ?></div>
                        </div>
                        <div class="actions">
                            <?php if ($contratacao['status'] === 'aceita'): ?>
                                <button class="btn btn-primary" onclick="iniciarExecucao(<?php echo $contratacao['id_contratacao']; ?>)">
                                    Iniciar Execução
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Avaliações Recentes -->
        <div class="section">
            <h2>⭐ Avaliações Recentes</h2>
            <?php if (empty($avaliacoes_recentes)): ?>
                <div class="no-items">Nenhuma avaliação ainda</div>
            <?php else: ?>
                <?php foreach ($avaliacoes_recentes as $avaliacao): ?>
                    <div class="avaliacao-item">
                        <div class="estrelas">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $avaliacao['pontuacao'] ? '★' : '☆';
                            }
                            ?>
                        </div>
                        <div style="font-weight: bold; margin-bottom: 5px;">
                            <?php echo htmlspecialchars($avaliacao['nome']); ?>
                        </div>
                        <?php if ($avaliacao['feedback']): ?>
                            <div style="color: #666; margin-bottom: 5px;">
                                "<?php echo htmlspecialchars($avaliacao['feedback']); ?>"
                            </div>
                        <?php endif; ?>
                        <div style="color: #999; font-size: 0.9em;">
                            <?php echo date('d/m/Y', strtotime($avaliacao['data_avaliacao'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function aplicarDesconto() {
            const idAssociado = document.getElementById('idAssociado').value;
            const resultadoDiv = document.getElementById('resultadoDesconto');
            
            if (!idAssociado) {
                resultadoDiv.innerHTML = '<div style="color: #dc3545;">Digite o ID do associado</div>';
                return;
            }
            
            fetch('aplicar_desconto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id_associado=' + idAssociado
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultadoDiv.innerHTML = `
                        <div style="color: #28a745; font-weight: bold;">
                            ✅ ${data.message}
                        </div>
                    `;
                    document.getElementById('idAssociado').value = '';
                } else {
                    resultadoDiv.innerHTML = `<div style="color: #dc3545;">❌ ${data.message}</div>`;
                }
            })
            .catch(error => {
                resultadoDiv.innerHTML = `<div style="color: #dc3545;">Erro: ${error.message}</div>`;
            });
        }

        function responderContratacao(idContratacao, acao) {
            const mensagem = acao === 'aceitar' ? 'aceitar' : 'recusar';
            
            if (confirm(`Confirma ${mensagem} esta contratação?`)) {
                fetch('responder_contratacao.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id_contratacao=${idContratacao}&acao=${acao}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Erro: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Erro: ' + error.message);
                });
            }
        }

        function iniciarExecucao(idContratacao) {
            if (confirm('Confirma o início da execução deste serviço?')) {
                fetch('responder_contratacao.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id_contratacao=${idContratacao}&acao=iniciar`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Erro: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Erro: ' + error.message);
                });
            }
        }
    </script>
</body>
</html>

