#!/bin/bash

# Script para adicionar botão de Sair em todas as páginas HTML que não têm

cd /home/ubuntu/serrafatorado/frontend

# Botão HTML a ser adicionado (antes do </ul>)
BOTAO_HTML='            <li class="nav-item" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">\n                <a href="#" class="nav-link" id="btn-logout" style="background: rgba(239, 68, 68, 0.1); color: #fca5a5;" onclick="fazerLogout(event)">\n                    <i class="fas fa-sign-out-alt"><\/i> Sair\n                <\/a>\n            <\/li>'

# Função JavaScript a ser adicionada (antes do </script> final)
FUNCAO_JS='        \/\/ ========== FUNÇÃO DE LOGOUT ==========\n        function fazerLogout(event) {\n            event.preventDefault();\n            \n            if (confirm('\''Deseja realmente sair do sistema?'\'')) {\n                fetch('\''..\/api\/logout.php'\'', {\n                    method: '\''POST'\'',\n                    credentials: '\''include'\''\n                })\n                .then(response => response.json())\n                .then(data => {\n                    if (data.sucesso) {\n                        \/\/ Limpar sessionStorage\n                        sessionStorage.clear();\n                        \n                        \/\/ Redirecionar para login\n                        window.location.href = '\''login.html'\'';\n                    } else {\n                        alert('\''Erro ao fazer logout: '\'' + data.mensagem);\n                    }\n                })\n                .catch(error => {\n                    console.error('\''Erro ao fazer logout:'\'', error);\n                    \/\/ Mesmo com erro, limpar e redirecionar\n                    sessionStorage.clear();\n                    window.location.href = '\''login.html'\'';\n                });\n            }\n        }'

contador=0

# Percorrer todos os arquivos HTML
for arquivo in *.html; do
    # Pular arquivos especiais
    if [ "$arquivo" = "login.html" ] || [ "$arquivo" = "portal.html" ] || [ "$arquivo" = "teste_login.html" ] || [ "$arquivo" = "teste_moradores.html" ] || [ "$arquivo" = "debug_erros.html" ]; then
        continue
    fi
    
    # Verificar se já tem o botão Sair
    if grep -q "Sair" "$arquivo"; then
        echo "✓ $arquivo - Já tem botão Sair"
        continue
    fi
    
    # Verificar se tem menu (</ul>)
    if ! grep -q "</ul>" "$arquivo"; then
        echo "⚠ $arquivo - Não tem menu </ul>"
        continue
    fi
    
    # Criar backup
    cp "$arquivo" "$arquivo.bak"
    
    # Adicionar botão antes do primeiro </ul>
    sed -i "0,/<\/ul>/s/<\/ul>/$BOTAO_HTML\n        <\/ul>/" "$arquivo"
    
    # Verificar se tem <script> para adicionar função
    if grep -q "<script>" "$arquivo"; then
        # Adicionar função antes do último </script>
        sed -i "/<\/script>/i\\$FUNCAO_JS" "$arquivo"
    fi
    
    echo "✅ $arquivo - Botão adicionado"
    ((contador++))
done

echo ""
echo "=========================================="
echo "Total de arquivos modificados: $contador"
echo "=========================================="
