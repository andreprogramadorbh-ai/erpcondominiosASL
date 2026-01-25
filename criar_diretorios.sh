#!/bin/bash
# Script para criar diretórios necessários para o sistema

echo "Criando diretórios de upload..."

# Criar diretório de notificações
mkdir -p uploads/notificacoes
chmod 755 uploads/notificacoes

echo "Diretórios criados com sucesso!"
echo ""
echo "Estrutura criada:"
echo "  uploads/"
echo "    └── notificacoes/ (755)"
echo ""
echo "IMPORTANTE: Certifique-se de que o usuário do servidor web (www-data, apache, nginx)"
echo "tem permissão de escrita nestes diretórios."
echo ""
echo "Se necessário, execute:"
echo "  sudo chown -R www-data:www-data uploads/"
echo "  ou"
echo "  sudo chown -R apache:apache uploads/"
