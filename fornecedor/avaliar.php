<?php
session_start();
require_once 'config.php';

// Verificar se está logado
if (!isset($_SESSION['associado_id'])) {
    header('Location: login_associado.html');
    exit;
}

$associado_id = $_SESSION['associado_id'];
$contratacao_id = intval($_GET['id'] ?? 0);

if (!$contratacao_id) {
    header('Location: minhas_contratacoes.php');
    exit;
}

try {
    $pdo = getConnection();
    
    // Buscar dados da contratação
    $stmt = $pdo->prepare("
        SELECT c.*, f.nome_empreendimento, f.segmento
        FROM contratacoes c
        JOIN fornecedores f ON c.id_fornecedor = f.id_fornecedor
        WHERE c.id_contratacao = ? AND c.id_associado = ? AND c.status = 'finalizada'
    ");
    $stmt->execute([$contratacao_id, $associado_id]);
    $contratacao = $stmt->fetch();
    
    if (!$contratacao) {
        header('Location: minhas_contratacoes.php');
        exit;
    }
    
    // Verificar se já foi avaliado
    $stmt = $pdo->prepare("SELECT id_avaliacao FROM avaliacoes WHERE id_contratacao = ?");
    $stmt->execute([$contratacao_id]);
    $avaliacao_existente = $stmt->fetch();
    
} catch (Exception $e) {
    $erro = $e->getMessage();
}

// Processar avaliação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$avaliacao_existente) {
    $pontuacao = intval($_POST['pontuacao'] ?? 0);
    $feedback = trim($_POST['feedback'] ?? '');
    
    if ($pontuacao >= 1 && $pontuacao <= 5) {
        try {
            $pdo->beginTransaction();
            
            // Inserir avaliação
            $stmt = $pdo->prepare("
                INSERT INTO avaliacoes (id_contratacao, id_associado, id_fornecedor, pontuacao, feedback, data_avaliacao) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$contratacao_id, $associado_id, $contratacao['id_fornecedor'], $pontuacao, $feedback]);
            
            // Atualizar média do fornecedor
            $stmt = $pdo->prepare("
                UPDATE fornecedores 
                SET media_avaliacao = (
                    SELECT AVG(pontuacao) FROM avaliacoes WHERE id_fornecedor = ?
                ),
                total_avaliacoes = (
                    SELECT COUNT(*) FROM avaliacoes WHERE id_fornecedor = ?
                )
                WHERE id_fornecedor = ?
            ");
            $stmt->execute([$contratacao['id_fornecedor'], $contratacao['id_fornecedor'], $contratacao['id_fornecedor']]);
            
            $pdo->commit();
            
            header('Location: minhas_contratacoes.php?msg=Avaliação enviada com sucesso!');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro_avaliacao = $e->getMessage();
        }
    } else {
        $erro_avaliacao = 'Pontuação deve ser entre 1 e 5';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliar Fornecedor - Sistema de Fornecedores</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .form-container {
            padding: 40px;
        }

        .fornecedor-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .fornecedor-nome {
            font-size: 1.3em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
            font-size: 1.1em;
        }

        .rating {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .star {
            font-size: 2.5em;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .star:hover,
        .star.active {
            color: #ffc107;
        }

        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
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

        .alert-info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }

        .navigation {
            padding: 20px 40px;
            background: #f8f9fa;
            border-top: 1px solid #e1e5e9;
        }

        .nav-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .nav-link:hover {
            text-decoration: underline;
        }

        .rating-description {
            margin-top: 10px;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⭐ Avaliar Fornecedor</h1>
            <p>Sua opinião é muito importante!</p>
        </div>

        <div class="form-container">
            <?php if ($avaliacao_existente): ?>
                <div class="alert alert-info">
                    <strong>✅ Avaliação já enviada!</strong><br>
                    Você já avaliou este fornecedor para esta contratação.
                </div>
            <?php else: ?>
                <div class="fornecedor-info">
                    <div class="fornecedor-nome"><?php echo htmlspecialchars($contratacao['nome_empreendimento']); ?></div>
                    <p style="color: #666;"><?php echo htmlspecialchars($contratacao['segmento']); ?></p>
                    <p style="color: #666; margin-top: 10px;">
                        <strong>Serviço finalizado em:</strong> 
                        <?php echo date('d/m/Y H:i', strtotime($contratacao['data_finalizacao'])); ?>
                    </p>
                </div>

                <?php if (isset($erro_avaliacao)): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($erro_avaliacao); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Avaliação Geral *</label>
                        <div class="rating" id="rating">
                            <span class="star" data-rating="1">★</span>
                            <span class="star" data-rating="2">★</span>
                            <span class="star" data-rating="3">★</span>
                            <span class="star" data-rating="4">★</span>
                            <span class="star" data-rating="5">★</span>
                        </div>
                        <div class="rating-description" id="ratingDescription">
                            Clique nas estrelas para avaliar
                        </div>
                        <input type="hidden" name="pontuacao" id="pontuacao" required>
                    </div>

                    <div class="form-group">
                        <label for="feedback">Comentário (opcional)</label>
                        <textarea name="feedback" id="feedback" placeholder="Conte como foi sua experiência com este fornecedor..."></textarea>
                    </div>

                    <button type="submit" class="btn-primary" id="submitBtn" disabled>
                        Enviar Avaliação
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="navigation">
            <a href="minhas_contratacoes.php" class="nav-link">← Voltar às Contratações</a>
        </div>
    </div>

    <script>
        const stars = document.querySelectorAll('.star');
        const pontuacaoInput = document.getElementById('pontuacao');
        const submitBtn = document.getElementById('submitBtn');
        const ratingDescription = document.getElementById('ratingDescription');
        
        const descriptions = {
            1: '⭐ Muito Ruim - Serviço muito abaixo do esperado',
            2: '⭐⭐ Ruim - Serviço abaixo do esperado',
            3: '⭐⭐⭐ Regular - Serviço dentro do esperado',
            4: '⭐⭐⭐⭐ Bom - Serviço acima do esperado',
            5: '⭐⭐⭐⭐⭐ Excelente - Serviço muito acima do esperado'
        };

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                pontuacaoInput.value = rating;
                
                // Atualizar visual das estrelas
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
                
                // Atualizar descrição
                ratingDescription.textContent = descriptions[rating];
                
                // Habilitar botão
                submitBtn.disabled = false;
            });
            
            star.addEventListener('mouseover', function() {
                const rating = parseInt(this.dataset.rating);
                
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.style.color = '#ffc107';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });
        
        document.getElementById('rating').addEventListener('mouseleave', function() {
            const currentRating = parseInt(pontuacaoInput.value) || 0;
            
            stars.forEach((s, index) => {
                if (index < currentRating) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
    </script>
</body>
</html>

