#!/bin/bash

# Script para corrigir caminhos de API de 'api/' para '../api/' em todos os HTML do frontend

echo "🔧 Corrigindo caminhos de API em todos os arquivos HTML..."
echo ""

contador=0

for arquivo in frontend/*.html; do
    if [ -f "$arquivo" ]; then
        # Contar quantas ocorrências existem antes
        antes=$(grep -c "fetch('api/" "$arquivo" 2>/dev/null || echo 0)
        
        if [ "$antes" -gt 0 ]; then
            echo "📝 Corrigindo: $arquivo ($antes ocorrências)"
            
            # Fazer backup
            cp "$arquivo" "$arquivo.bak"
            
            # Substituir fetch('api/ por fetch('../api/
            sed -i "s|fetch('api/|fetch('../api/|g" "$arquivo"
            
            # Substituir fetch(\"api/ por fetch(\"../api/
            sed -i 's|fetch("api/|fetch("../api/|g' "$arquivo"
            
            # Contar quantas ocorrências existem depois
            depois=$(grep -c "fetch('../api/" "$arquivo" 2>/dev/null || echo 0)
            
            echo "   ✅ Corrigido: $antes → $depois"
            contador=$((contador + 1))
        fi
    fi
done

echo ""
echo "✅ Correção concluída!"
echo "📊 Total de arquivos corrigidos: $contador"
echo ""
echo "💾 Backups salvos com extensão .bak"
