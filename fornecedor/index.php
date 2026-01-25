<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Fornecedores - Associação Serra da Liberdade</title>
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
        }

        .header {
            text-align: center;
            padding: 60px 20px;
            color: white;
        }

        .header h1 {
            font-size: 3.5em;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header p {
            font-size: 1.3em;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.15);
        }

        .card-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }

        .card h2 {
            color: #333;
            font-size: 1.8em;
            margin-bottom: 15px;
        }

        .card p {
            color: #666;
            font-size: 1.1em;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .card-actions {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .btn {
            padding: 15px 25px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: transform 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .features {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .features h2 {
            text-align: center;
            color: #333;
            font-size: 2.5em;
            margin-bottom: 30px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .feature-item {
            text-align: center;
            padding: 20px;
        }

        .feature-icon {
            font-size: 3em;
            margin-bottom: 15px;
            color: #667eea;
        }

        .feature-item h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.3em;
        }

        .feature-item p {
            color: #666;
            line-height: 1.5;
        }

        .footer {
            text-align: center;
            padding: 40px 20px;
            color: white;
            opacity: 0.8;
        }

        .admin-section {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 30px;
            margin-top: 40px;
            text-align: center;
        }

        .admin-section h3 {
            color: white;
            margin-bottom: 15px;
            font-size: 1.5em;
        }

        .admin-section p {
            color: white;
            opacity: 0.9;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2.5em;
            }
            
            .header p {
                font-size: 1.1em;
            }
            
            .cards-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .card {
                padding: 30px 20px;
            }
            
            .features {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏢 Sistema de Fornecedores</h1>
        <p>Conectando associados e fornecedores da Associação Serra da Liberdade com qualidade, confiança e descontos exclusivos.</p>
    </div>

    <div class="container">
        <div class="cards-grid">
            <!-- Card Associados -->
            <div class="card">
                <div class="card-icon">👥</div>
                <h2>Área do Associado</h2>
                <p>Encontre fornecedores qualificados, contrate serviços com desconto exclusivo e avalie sua experiência.</p>
                <div class="card-actions">
                    <a href="login_associado.html" class="btn btn-primary">Entrar</a>
                    <a href="cadastro_associado.html" class="btn btn-secondary">Cadastrar-se</a>
                </div>
            </div>

            <!-- Card Fornecedores -->
            <div class="card">
                <div class="card-icon">🏪</div>
                <h2>Área do Fornecedor</h2>
                <p>Gerencie suas contratações, aplique descontos para associados e acompanhe suas avaliações.</p>
                <div class="card-actions">
                    <a href="login_fornecedor.html" class="btn btn-success">Entrar</a>
                    <a href="cadastro_fornecedor.html" class="btn btn-secondary">Cadastrar-se</a>
                </div>
            </div>
        </div>

        <!-- Recursos do Sistema -->
        <div class="features">
            <h2>🌟 Recursos do Sistema</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">💰</div>
                    <h3>Descontos Exclusivos</h3>
                    <p>Associados recebem descontos especiais em todos os fornecedores parceiros</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">⭐</div>
                    <h3>Sistema de Avaliações</h3>
                    <p>Avalie e seja avaliado para garantir a qualidade dos serviços</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">📱</div>
                    <h3>Fácil Contratação</h3>
                    <p>Processo simples e rápido para contratar serviços</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">🔒</div>
                    <h3>Seguro e Confiável</h3>
                    <p>Todos os fornecedores são verificados e aprovados</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">📊</div>
                    <h3>Acompanhamento</h3>
                    <p>Monitore o status de suas contratações em tempo real</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">🎯</div>
                    <h3>Segmentação</h3>
                    <p>Encontre fornecedores por categoria de serviço</p>
                </div>
            </div>
        </div>

        <!-- Seção Administrativa -->
        <div class="admin-section">
            <h3>⚙️ Área Administrativa</h3>
            <p>Primeira vez usando o sistema? Configure o banco de dados.</p>
            <a href="install.html" class="btn btn-secondary">Instalar Sistema</a>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2024 Associação Serra da Liberdade - Sistema de Fornecedores</p>
        <p>Desenvolvido para conectar nossa comunidade</p>
    </div>
</body>
</html>

