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
$associado_unidade = $_SESSION['associado_unidade'];

try {
    $pdo = getConnection();
    
    // Buscar fornecedores
    $segmento_filtro = $_GET['segmento'] ?? '';
    
    if ($segmento_filtro) {
        $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE segmento = ? ORDER BY nome_empreendimento");
        $stmt->execute([$segmento_filtro]);
    } else {
        $stmt = $pdo->query("SELECT * FROM fornecedores ORDER BY nome_empreendimento");
    }
    
    $fornecedores = $stmt->fetchAll();
    
    // Buscar segmentos únicos
    $stmt = $pdo->query("SELECT DISTINCT segmento FROM fornecedores ORDER BY segmento");
    $segmentos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    $erro = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Associado - Sistema de Fornecedores</title>
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

        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filters h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .filter-group {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group select {
            padding: 10px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
        }

        .btn-filter {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-filter:hover {
            background: #5a6fd8;
        }

        .fornecedores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .fornecedor-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .fornecedor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .fornecedor-header {
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

        .fornecedor-segmento {
            background: #667eea;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
        }

        .fornecedor-info {
            margin-bottom: 15px;
        }

        .fornecedor-info p {
            margin: 8px 0;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .avaliacao {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }

        .estrelas {
            color: #ffc107;
            font-size: 1.2em;
        }

        .btn-contratar {
            width: 100%;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .btn-contratar:hover {
            transform: translateY(-2px);
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
                text-align: center;
                gap: 15px;
            }
            
            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }
            
            .fornecedores-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div>
                <h1>🏢 Área do Associado</h1>
                <p>Bem-vindo, <?php echo htmlspecialchars($associado_nome); ?>!</p>
                <p style="font-size: 0.9em; opacity: 0.8;">ID: <?php echo $associado_id; ?> | <?php echo htmlspecialchars($associado_unidade); ?></p>
            </div>
            <div class="nav-links">
                <a href="minhas_contratacoes.php">Minhas Contratações</a>
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

        <div class="filters">
            <h3>🔍 Filtrar Fornecedores</h3>
            <form method="GET" class="filter-group">
                <select name="segmento">
                    <option value="">Todos os segmentos</option>
                    <?php foreach ($segmentos as $segmento): ?>
                        <option value="<?php echo htmlspecialchars($segmento); ?>" 
                                <?php echo $segmento_filtro === $segmento ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($segmento); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-filter">Filtrar</button>
                <?php if ($segmento_filtro): ?>
                    <a href="area_associado.php" class="btn-filter" style="background: #6c757d; text-decoration: none;">Limpar</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (empty($fornecedores)): ?>
            <div class="no-results">
                <h3>😔 Nenhum fornecedor encontrado</h3>
                <p>Tente alterar os filtros ou aguarde novos cadastros.</p>
            </div>
        <?php else: ?>
            <div class="fornecedores-grid">
                <?php foreach ($fornecedores as $fornecedor): ?>
                    <div class="fornecedor-card">
                        <div class="fornecedor-header">
                            <div>
                                <div class="fornecedor-nome"><?php echo htmlspecialchars($fornecedor['nome_empreendimento']); ?></div>
                                <div class="avaliacao">
                                    <span class="estrelas">
                                        <?php
                                        $media = floatval($fornecedor['media_avaliacao']);
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $media ? '★' : '☆';
                                        }
                                        ?>
                                    </span>
                                    <span><?php echo number_format($media, 1); ?> (<?php echo $fornecedor['total_avaliacoes']; ?> avaliações)</span>
                                </div>
                            </div>
                            <div class="fornecedor-segmento"><?php echo htmlspecialchars($fornecedor['segmento']); ?></div>
                        </div>

                        <div class="fornecedor-info">
                            <p>📧 <?php echo htmlspecialchars($fornecedor['email']); ?></p>
                            <p>📱 <?php echo htmlspecialchars($fornecedor['telefone']); ?></p>
                            <p>📍 <?php echo htmlspecialchars($fornecedor['endereco']); ?></p>
                            <?php if ($fornecedor['site']): ?>
                                <p>🌐 <a href="<?php echo htmlspecialchars($fornecedor['site']); ?>" target="_blank">Site</a></p>
                            <?php endif; ?>
                            <?php if ($fornecedor['instagram']): ?>
                                <p>📷 <?php echo htmlspecialchars($fornecedor['instagram']); ?></p>
                            <?php endif; ?>
                        </div>

                        <button class="btn-contratar" onclick="contratar(<?php echo $fornecedor['id_fornecedor']; ?>)">
                            Contratar Serviço
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function contratar(idFornecedor) {
            if (confirm('Deseja solicitar contratação deste fornecedor?')) {
                fetch('contratar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id_fornecedor=' + idFornecedor
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Solicitação enviada com sucesso! O fornecedor será notificado.');
                        location.reload();
                    } else {
                        alert('Erro: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Erro ao enviar solicitação: ' + error.message);
                });
            }
        }
    </script>
</body>
</html>

