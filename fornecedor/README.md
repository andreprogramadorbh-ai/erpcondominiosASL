# Sistema de Fornecedores - Associação Serra da Liberdade

## 📋 Descrição

Sistema completo para gerenciamento de fornecedores e associados da Associação Serra da Liberdade, permitindo contratações com desconto, avaliações e acompanhamento de serviços.

## 🚀 Instalação

### Pré-requisitos
- Servidor web com PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Extensões PHP: PDO, PDO_MySQL

### Passos para Instalação

1. **Upload dos arquivos**
   - Faça upload de todos os arquivos para o diretório do seu site no Hostgator
   - Certifique-se de que todos os arquivos estão na pasta raiz ou em uma subpasta

2. **Configuração do banco de dados**
   - Acesse `install.html` no seu navegador
   - Preencha os dados de conexão do banco MySQL
   - Clique em "Instalar Sistema"
   - O sistema criará automaticamente o banco e as tabelas

3. **Verificação**
   - Após a instalação, acesse `index.php`
   - O sistema estará pronto para uso

## 👥 Como Usar - Associados

### Cadastro
1. Acesse a página inicial
2. Clique em "Cadastrar-se" na área do associado
3. Preencha todos os dados obrigatórios
4. Anote seu ID de associado (será exibido após o cadastro)

### Login e Navegação
1. Use seu e-mail para fazer login
2. Na área do associado você pode:
   - Ver todos os fornecedores por segmento
   - Contratar serviços
   - Acompanhar suas contratações
   - Avaliar fornecedores após finalização

### Contratação de Serviços
1. Navegue pelos fornecedores
2. Use o filtro por segmento se necessário
3. Clique em "Contratar Serviço"
4. Aguarde a aprovação do fornecedor
5. Acompanhe o status em "Minhas Contratações"

### Avaliações
1. Após finalizar um serviço, você pode avaliar
2. Dê uma nota de 1 a 5 estrelas
3. Deixe um comentário (opcional)
4. Sua avaliação ajuda outros associados

## 🏪 Como Usar - Fornecedores

### Cadastro
1. Acesse a página inicial
2. Clique em "Cadastrar-se" na área do fornecedor
3. Preencha todos os dados obrigatórios
4. Use a busca por CEP para facilitar o endereço
5. Anote seu ID de fornecedor

### Login e Navegação
1. Use seu ID de fornecedor para fazer login
2. Na área do fornecedor você pode:
   - Ver solicitações pendentes
   - Aceitar/recusar contratações
   - Gerenciar serviços em andamento
   - Aplicar descontos para associados
   - Ver suas avaliações

### Gerenciamento de Contratações
1. **Solicitações Pendentes**: Aceite ou recuse
2. **Serviços Aceitos**: Inicie a execução
3. **Em Execução**: Aguarde finalização pelo associado

### Sistema de Desconto
1. Digite o ID do associado
2. Clique em "Aplicar Desconto"
3. O sistema confirmará os dados do associado
4. Aplique o desconto conforme acordado

## 🔧 Funcionalidades Principais

### Para Associados
- ✅ Cadastro completo com validação
- ✅ Login por e-mail
- ✅ Busca de fornecedores por segmento
- ✅ Contratação de serviços
- ✅ Acompanhamento de status
- ✅ Sistema de avaliações
- ✅ Finalização de serviços

### Para Fornecedores
- ✅ Cadastro com validação de CPF/CNPJ
- ✅ Login por ID
- ✅ Busca automática de endereço por CEP
- ✅ Gerenciamento de contratações
- ✅ Sistema de aplicação de desconto
- ✅ Visualização de avaliações
- ✅ Dashboard com estatísticas

### Sistema Geral
- ✅ Banco de dados MySQL estruturado
- ✅ Interface responsiva (mobile-friendly)
- ✅ Validações de segurança
- ✅ Máscaras para CPF/CNPJ/telefone
- ✅ Sistema de sessões
- ✅ Integração com API de CEP

## 📊 Estrutura do Banco de Dados

### Tabelas Principais
- **fornecedores**: Dados dos fornecedores
- **associados**: Dados dos associados
- **contratacoes**: Registro de contratações
- **avaliacoes**: Avaliações dos serviços

### Status de Contratação
- `pendente`: Aguardando resposta do fornecedor
- `aceita`: Aceita pelo fornecedor
- `executando`: Serviço em execução
- `finalizada`: Serviço finalizado
- `cancelada`: Cancelada por qualquer parte

## 🛠️ Arquivos Principais

### Páginas Principais
- `index.php`: Página inicial
- `install.html`: Instalação do sistema
- `config.php`: Configurações do banco

### Área do Associado
- `login_associado.html`: Login
- `cadastro_associado.html`: Cadastro
- `area_associado.php`: Dashboard
- `minhas_contratacoes.php`: Acompanhamento
- `avaliar.php`: Sistema de avaliações

### Área do Fornecedor
- `login_fornecedor.html`: Login
- `cadastro_fornecedor.html`: Cadastro
- `area_fornecedor.php`: Dashboard

### Scripts Backend
- `contratar.php`: Processar contratações
- `aplicar_desconto.php`: Sistema de desconto
- `responder_contratacao.php`: Aceitar/recusar
- `finalizar_contratacao.php`: Finalizar serviços

## 🔒 Segurança

- Validação de dados no frontend e backend
- Proteção contra SQL Injection (PDO)
- Sessões seguras
- Validação de CPF/CNPJ
- Verificação de permissões

## 📱 Responsividade

O sistema é totalmente responsivo e funciona em:
- Computadores desktop
- Tablets
- Smartphones
- Diferentes navegadores

## 🆘 Suporte

Para dúvidas ou problemas:
1. Verifique se todos os arquivos foram enviados
2. Confirme as configurações do banco de dados
3. Verifique se o PHP e MySQL estão funcionando
4. Consulte os logs de erro do servidor

## 📝 Notas Importantes

- Mantenha backup regular do banco de dados
- O sistema usa sessões PHP para autenticação
- IDs são gerados automaticamente
- Avaliações afetam a média do fornecedor
- Sistema otimizado para Hostgator

---

**Desenvolvido para a Associação Serra da Liberdade**  
Sistema completo de gestão de fornecedores e associados.

