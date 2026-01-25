# Changelog - ERP Serra da Liberdade

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.1] - 2025-12-17

### 🐛 Corrigido

- **Login de Moradores:** O sistema de login de moradores não funcionava devido a uma incompatibilidade de hash de senha (SHA1 vs BCRYPT) e a um problema na busca de CPF (com e sem formatação). O código foi ajustado para:
  - Suportar ambos os tipos de hash (SHA1 e BCRYPT).
  - Migrar automaticamente as senhas de SHA1 para BCRYPT no primeiro login bem-sucedido.
  - Corrigir a query SQL para buscar o CPF independentemente da formatação.

### Changed

- `validar_login_morador.php`: Lógica de autenticação completamente reescrita para corrigir os problemas de hash e busca de CPF.

### Added

- `teste_login_morador.php`: Novo script de teste para validar a funcionalidade do login de moradores, incluindo testes de conexão com o banco, estrutura da tabela, busca de CPF e verificação de senhas.
- `CORRECOES_LOGIN_MORADOR.md`: Documentação técnica detalhada sobre o problema e a solução aplicada.

---

## [1.0.0] - 2025-10-22

### 🎉 Lançamento Inicial

- Lançamento inicial do sistema de controle de acesso e gestão para o condomínio Serra da Liberdade.

### ✨ Funcionalidades

- Gestão de Moradores, Veículos e Visitantes.
- Controle de Acesso via RFID e manual.
- Módulos de Estoque, Hidrômetros, Protocolos, Checklist e Notificações.
- Portal do Morador e área administrativa.

### 🐛 Corrigido

- **Sistema de Notificações:** Corrigidos problemas com download de anexos, duplicidade de notificações e upload de arquivos na área administrativa.
