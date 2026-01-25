#!/bin/bash
# =====================================================
# Script de Teste - Módulo de Dependentes
# =====================================================
# 
# Testa todas as funcionalidades do módulo de dependentes
# 
# @author Sistema ERP Serra da Liberdade
# @date 25/01/2026

echo "========================================"
echo "TESTE DO MÓDULO DE DEPENDENTES"
echo "========================================"
echo ""

BASE_URL="http://localhost/api/error"

echo "[1/6] Testando verificação de conexão..."
curl -s "${BASE_URL}/debug_dependentes.php?acao=verificar_conexao" | python3 -m json.tool
echo ""
echo "----------------------------------------"
echo ""

echo "[2/6] Testando verificação de tabela..."
curl -s "${BASE_URL}/debug_dependentes.php?acao=verificar_tabela" | python3 -m json.tool
echo ""
echo "----------------------------------------"
echo ""

echo "[3/6] Testando INSERT direto..."
curl -s "${BASE_URL}/debug_dependentes.php?acao=testar_insert_direto" | python3 -m json.tool
echo ""
echo "----------------------------------------"
echo ""

echo "[4/6] Testando criação via Model..."
curl -s "${BASE_URL}/debug_dependentes.php?acao=testar" | python3 -m json.tool
echo ""
echo "----------------------------------------"
echo ""

echo "[5/6] Listando todos os dependentes..."
curl -s "${BASE_URL}/debug_dependentes.php?acao=listar_todos" | python3 -m json.tool
echo ""
echo "----------------------------------------"
echo ""

echo "[6/6] Limpando registros de teste..."
curl -s "${BASE_URL}/debug_dependentes.php?acao=limpar_testes" | python3 -m json.tool
echo ""
echo "----------------------------------------"
echo ""

echo "========================================"
echo "TESTES CONCLUÍDOS"
echo "========================================"
echo ""
echo "Verifique o arquivo de log em:"
echo "api/error/debug_dependentes.log"
echo ""
