# 🏠 Portal do Morador - Funcionalidades Completas

## 📋 Visão Geral

O Portal do Morador foi completamente implementado com **3 abas principais**:

1. **Meu Perfil** - Visualização e edição de dados pessoais
2. **Visitantes** - Cadastro e gerenciamento de visitantes
3. **Hidrômetro** - Visualização de hidrômetro e histórico de leituras

---

## 🎯 Funcionalidades Implementadas

### **1. ABA: MEU PERFIL**

#### **1.1 Visualização de Dados**
- ✅ Nome completo
- ✅ CPF
- ✅ Unidade
- ✅ E-mail

#### **1.2 Atualização de Telefones**
- ✅ Telefone fixo (opcional)
- ✅ Celular (opcional)
- ✅ Salvamento independente da senha

#### **1.3 Alteração de Senha**
- ✅ Validação de senha atual
- ✅ Nova senha (mínimo 6 caracteres)
- ✅ Confirmação de senha
- ✅ Criptografia BCRYPT
- ✅ Suporte a migração de SHA1 para BCRYPT

---

### **2. ABA: VISITANTES**

#### **2.1 Cadastro de Visitantes**
- ✅ Nome completo (obrigatório)
- ✅ Tipo de documento (CPF ou RG)
- ✅ Número do documento (obrigatório)
- ✅ Telefone fixo (opcional)
- ✅ Celular (opcional)
- ✅ E-mail (opcional)
- ✅ Observação (opcional)

#### **2.2 Listagem de Visitantes**
- ✅ Tabela com todos os visitantes do morador
- ✅ Exibição de nome, documento, telefone e status
- ✅ Badge de status (Ativo/Inativo)

#### **2.3 Gerenciamento**
- ✅ Excluir visitante (com confirmação)
- ✅ Apenas visitantes do próprio morador são exibidos
- ✅ Segurança: não é possível excluir visitantes de outros moradores

---

### **3. ABA: HIDRÔMETRO**

#### **3.1 Dados do Hidrômetro**
- ✅ Número do hidrômetro
- ✅ Número do lacre
- ✅ Data de instalação
- ✅ Status (Ativo/Inativo)

#### **3.2 Histórico de Leituras**
- ✅ Data da leitura
- ✅ Leitura anterior (m³)
- ✅ Leitura atual (m³)
- ✅ Consumo (m³)
- ✅ Valor total (R$)
- ✅ Últimas 12 leituras
- ✅ Ordenação por data (mais recente primeiro)

---

## 🔌 APIs Criadas

### **API: api_portal_morador.php**

#### **Endpoints Implementados:**

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `?action=perfil` | GET | Obter dados do perfil do morador |
| `?action=perfil` | PUT | Atualizar telefone/celular ou senha |
| `?action=visitantes` | GET | Listar visitantes do morador |
| `?action=visitantes` | POST | Cadastrar novo visitante |
| `?action=visitantes&id={id}` | DELETE | Excluir visitante |
| `?action=hidrometro` | GET | Obter hidrômetro e histórico de leituras |

#### **Autenticação:**
- ✅ Token Bearer no header `Authorization`
- ✅ Validação de sessão via tabela `sessoes_portal`
- ✅ Verificação de expiração do token
- ✅ Retorno HTTP 401 para requisições não autorizadas

---

## 🗄️ Estrutura do Banco de Dados

### **Tabelas Utilizadas:**

#### **1. moradores**
```sql
- id (PK)
- nome
- cpf
- unidade
- email
- senha (BCRYPT)
- telefone
- celular
- ativo
- data_cadastro
- data_atualizacao
- ultimo_acesso
```

#### **2. visitantes**
```sql
- id (PK)
- morador_id (FK) ← NOVO CAMPO NECESSÁRIO
- nome_completo
- documento
- tipo_documento (CPF/RG)
- telefone
- celular
- email
- observacao
- ativo
- data_cadastro
- data_atualizacao
```

#### **3. hidrometros**
```sql
- id (PK)
- morador_id (FK)
- unidade
- numero_hidrometro
- numero_lacre
- ativo
- data_instalacao
- data_cadastro
- data_atualizacao
```

#### **4. leituras**
```sql
- id (PK)
- hidrometro_id (FK)
- morador_id (FK)
- unidade
- leitura_anterior
- leitura_atual
- consumo
- valor_metro_cubico
- valor_minimo
- valor_total
- data_leitura
- observacao
- data_cadastro
```

#### **5. sessoes_portal**
```sql
- id (PK)
- morador_id (FK)
- token (UNIQUE)
- ip_address
- user_agent
- data_criacao
- data_expiracao
- ultimo_acesso
```

---

## ⚠️ Atualização Necessária no Banco de Dados

### **Adicionar campo `morador_id` na tabela `visitantes`**

Execute o script SQL fornecido:

```sql
-- Arquivo: update_visitantes_morador_id.sql

ALTER TABLE `visitantes` 
ADD COLUMN `morador_id` INT(11) NULL AFTER `id`,
ADD INDEX `idx_morador_id` (`morador_id`);

ALTER TABLE `visitantes`
ADD CONSTRAINT `fk_visitantes_morador`
FOREIGN KEY (`morador_id`) REFERENCES `moradores`(`id`)
ON DELETE CASCADE
ON UPDATE CASCADE;
```

**Por que é necessário?**
- Permite vincular cada visitante ao morador que o cadastrou
- Garante que cada morador veja apenas seus próprios visitantes
- Mantém integridade referencial no banco de dados

---

## 🔒 Segurança Implementada

### **Autenticação e Autorização**
- ✅ Token de 256 bits (64 caracteres hexadecimais)
- ✅ Validação de token em todas as requisições
- ✅ Verificação de expiração (7 dias)
- ✅ Registro de IP e User-Agent
- ✅ Apenas dados do próprio morador são acessíveis

### **Proteção de Dados**
- ✅ Prepared Statements (proteção contra SQL Injection)
- ✅ Validação de entrada de dados
- ✅ Sanitização de saída
- ✅ HTTPS recomendado para produção

### **Senhas**
- ✅ BCRYPT (custo 10)
- ✅ Migração automática de SHA1 para BCRYPT
- ✅ Validação de senha atual antes de alterar
- ✅ Mínimo de 6 caracteres para nova senha

---

## 📱 Interface do Usuário

### **Design Responsivo**
- ✅ Desktop (layout completo)
- ✅ Tablet (adaptado)
- ✅ Mobile (otimizado)

### **Componentes**
- ✅ Header com nome do usuário e botão de logout
- ✅ Tabs para navegação entre seções
- ✅ Cards para organização de conteúdo
- ✅ Formulários com validação
- ✅ Tabelas responsivas
- ✅ Alertas de sucesso/erro
- ✅ Loading screen durante verificação de sessão
- ✅ Empty states para listas vazias

### **Experiência do Usuário**
- ✅ Feedback visual em todas as ações
- ✅ Confirmação antes de excluir
- ✅ Mensagens de erro claras
- ✅ Scroll automático para alertas
- ✅ Formulários resetam após sucesso

---

## 🧪 Como Testar

### **1. Teste de Login e Acesso**
1. Acesse: `login_morador.html`
2. Digite CPF e senha
3. Clique em "Entrar"
4. ✅ Deve redirecionar para `portal.html`
5. ✅ Nome do morador deve aparecer no header

### **2. Teste da Aba "Meu Perfil"**

#### **Visualização de Dados**
1. Acesse a aba "Meu Perfil"
2. ✅ Dados do morador devem aparecer (nome, CPF, unidade, e-mail)

#### **Atualizar Telefones**
1. Digite um telefone e celular
2. Clique em "Salvar Telefones"
3. ✅ Deve exibir mensagem de sucesso
4. ✅ Recarregue a página e verifique se os dados foram salvos

#### **Alterar Senha**
1. Digite a senha atual
2. Digite a nova senha (mínimo 6 caracteres)
3. Confirme a nova senha
4. Clique em "Alterar Senha"
5. ✅ Deve exibir mensagem de sucesso
6. ✅ Faça logout e login com a nova senha

### **3. Teste da Aba "Visitantes"**

#### **Cadastrar Visitante**
1. Acesse a aba "Visitantes"
2. Preencha o formulário:
   - Nome: João da Silva
   - Tipo: CPF
   - Documento: 123.456.789-00
   - Celular: (31) 99999-9999
3. Clique em "Cadastrar Visitante"
4. ✅ Deve exibir mensagem de sucesso
5. ✅ Visitante deve aparecer na lista abaixo

#### **Excluir Visitante**
1. Na lista de visitantes, clique no botão de excluir (🗑️)
2. Confirme a exclusão
3. ✅ Deve exibir mensagem de sucesso
4. ✅ Visitante deve sumir da lista

### **4. Teste da Aba "Hidrômetro"**

#### **Visualizar Dados do Hidrômetro**
1. Acesse a aba "Hidrômetro"
2. ✅ Deve exibir:
   - Número do hidrômetro
   - Número do lacre
   - Data de instalação
   - Status (Ativo)

#### **Visualizar Histórico de Leituras**
1. Na mesma aba, role para baixo
2. ✅ Deve exibir tabela com:
   - Data da leitura
   - Leitura anterior
   - Leitura atual
   - Consumo
   - Valor total
3. ✅ Leituras devem estar ordenadas por data (mais recente primeiro)

### **5. Teste de Segurança**

#### **Token Expirado**
1. Abra o console do navegador (F12)
2. Execute: `localStorage.setItem('portal_token', 'token_invalido')`
3. Recarregue a página
4. ✅ Deve redirecionar para login com mensagem de erro

#### **Sem Token**
1. Execute: `localStorage.clear()`
2. Acesse `portal.html` diretamente
3. ✅ Deve redirecionar para login

---

## 📊 Logs de Auditoria

### **Eventos Registrados:**

| Tipo | Descrição |
|------|-----------|
| `PERFIL_ATUALIZADO` | Morador atualizou telefone/celular |
| `SENHA_ALTERADA` | Morador alterou a senha |
| `VISITANTE_CADASTRADO` | Morador cadastrou visitante |
| `VISITANTE_EXCLUIDO` | Morador excluiu visitante |

### **Consultar Logs:**
```sql
SELECT * FROM logs_sistema 
WHERE tipo IN ('PERFIL_ATUALIZADO', 'SENHA_ALTERADA', 'VISITANTE_CADASTRADO', 'VISITANTE_EXCLUIDO')
ORDER BY data_hora DESC
LIMIT 50;
```

---

## 🚀 Próximos Passos Recomendados

### **Funcionalidades Futuras:**
- [ ] Edição de visitantes (atualmente só cadastro e exclusão)
- [ ] Upload de foto do visitante
- [ ] Agendamento de visitas
- [ ] Notificações de leituras de hidrômetro
- [ ] Gráficos de consumo de água
- [ ] Download de boletos
- [ ] Histórico de pagamentos
- [ ] Cadastro de veículos
- [ ] Reserva de áreas comuns

### **Melhorias Técnicas:**
- [ ] Paginação na lista de visitantes
- [ ] Busca/filtro de visitantes
- [ ] Exportação de histórico de leituras (PDF/Excel)
- [ ] PWA (Progressive Web App)
- [ ] Notificações push
- [ ] Dark mode

---

## 📝 Arquivos Criados/Modificados

### **Novos Arquivos:**
1. **api_portal_morador.php** - API completa do portal
2. **update_visitantes_morador_id.sql** - Script de atualização do banco
3. **PORTAL_MORADOR_FUNCIONALIDADES.md** - Esta documentação

### **Arquivos Modificados:**
1. **portal.html** - Interface completa com 3 abas
2. **portal_old_backup.html** - Backup do portal anterior

---

## ✅ Checklist de Implementação

- [x] API de perfil (GET/PUT)
- [x] API de visitantes (GET/POST/DELETE)
- [x] API de hidrômetro (GET)
- [x] Interface com tabs
- [x] Aba "Meu Perfil" completa
- [x] Aba "Visitantes" completa
- [x] Aba "Hidrômetro" completa
- [x] Autenticação por token
- [x] Validação de sessão
- [x] Logs de auditoria
- [x] Design responsivo
- [x] Feedback visual (alertas)
- [x] Documentação completa
- [ ] Script SQL executado no banco (PENDENTE)
- [ ] Testes em produção (PENDENTE)

---

## 🎉 Status Final

**Status:** ✅ Implementação Completa  
**Data:** 18 de Dezembro de 2024  
**Versão:** 1.0  
**Repositório:** https://github.com/andreprogramadorbh-ai/erpserra

---

## 📞 Suporte

Para dúvidas ou problemas:
1. Verifique os logs do sistema
2. Consulte esta documentação
3. Verifique o console do navegador (F12)
4. Entre em contato com o administrador do sistema

---

**Desenvolvido com ❤️ para o Condomínio Serra da Liberdade**
