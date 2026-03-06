# 🎵 SISTEMA MELODIAS V2.0 - DOCUMENTAÇÃO COMPLETA

## 📋  SOBRE O SISTEMA

Sistema completo de gestão com três níveis de permissões:
- **Super Admin** 👑: Controle total (criar usuários, gerenciar permissões, tudo)
- **Admin** 🛡️: Gerenciar materiais e sugestões
- **Usuário** 👤: Acessar biblioteca e enviar sugestões

---

## 🚀 INSTALAÇÃO RÁPIDA

### Passo 1: Executar Setup do Banco de Dados
1. Abra no navegador: `http://localhost/karen_site/Site/melodias/setup_banco.php`
2. Aguarde a mensagem de sucesso
3. **IMPORTANTE**: Delete o arquivo `setup_banco.php` após a instalação!

### Passo 2: Fazer Login
1. Acesse: `http://localhost/karen_site/Site/melodias/login.php`
2. Use as credenciais do Super Admin:
   - **Email**: karen.l.s.gomes@gmail.com
   - **Senha**: Bibia.0110

---

## 👑 FUNCIONALIDADES DO SUPER ADMIN

### Gerenciar Usuários (Exclusivo)
- ✅ Criar novos usuários
- ✅ Editar informações de qualquer usuário
- ✅ Alterar permissões (user → admin → superadmin)
- ✅ Ativar/Desativar contas
- ✅ Excluir usuários do sistema
- ✅ Resetar senhas de outros usuários

**Como Criar Usuário:**
1. Dashboard → Menu lateral → "Usuários"
2. Botão "+ Novo Usuário"
3. Preencha os dados:
   - Nome completo
   - Email (único no sistema)
   - Senha (mínimo 6 caracteres)
   - Especialidade (opcional)
   - WhatsApp (opcional)
   - **Nível de Permissão**:
     - `Usuário Normal`: Acesso básico (biblioteca + sugestões)
     - `Administrador`: Gerencia materiais e sugestões
     - `Super Administrador`: Controle total

**Como Editar Usuário:**
- Na tabela de usuários, clique no botão ✏️ (editar)
- Modifique os campos necessários
- Deixe "Nova Senha" vazio para manter a senha atual
- Salve alterações

**Como Ativar/Desativar:**
- Clique no botão 🔒 (cadeado) para alternar status
- Usuários inativos não podem fazer login

**Como Excluir:**
- Clique no botão 🗑️ (lixeira)
- Confirme a exclusão
- ⚠️ **ATENÇÃO**: Esta ação é permanente e remove todas as sugestões do usuário!

---

## 🛡️ FUNCIONALIDADES DO ADMIN

### Gerenciar Materiais
- ✅ Adicionar novos arquivos PDF
- ✅ Editar informações de materiais
- ✅ Excluir materiais (remove arquivo físico também)
- ✅ Definir visibilidade:
  - **Público**: Todos os membros podem acessar
  - **Restrito**: Apenas administradores

**Como Adicionar Material:**
1. Dashboard → Menu lateral → "Materiais"
2. Botão "+ Novo Material"
3. Preencha:
   - Título do material
   - Categoria (ex: Psicologia Clínica)
   - Visibilidade
   - Upload do arquivo PDF
4. Salvar

### Gerenciar Sugestões
- ✅ Ver todas as sugestões enviadas
- ✅ Alterar status (Nova → Em Análise → Aprovada → Arquivada)
- ✅ Excluir sugestões
- ✅ Ver quem enviou cada sugestão

---

## 👤 FUNCIONALIDADES DO USUÁRIO

### Biblioteca Digital
- ✅ Ver todos os materiais públicos
- ✅ Buscar por título ou categoria
- ✅ Fazer download dos PDFs
- ✅ Admins veem também materiais restritos

### Caixa de Sugestões
- ✅ Enviar ideias para próximos encontros
- ✅ Sistema envia para análise dos administradores

### WhatsApp
- ✅ Acesso direto ao grupo oficial

---

## 🎨 RECURSOS DO SISTEMA

### Design Premium
- ✨ Interface moderna e responsiva
- 🌙 Modo escuro/claro (botão no topo)
- 📱 Funciona perfeitamente em celular
- 🎭 Animações suaves e profissionais

### Notificações (Toasts)
- ✅ Feedback visual para todas as ações
- 🟢 Verde: Sucesso
- 🔴 Vermelho: Erro/Exclusão
- 🟡 Amarelo: Aviso

### Modais Inteligentes
- 📝 Formulários em pop-ups elegantes
- ✖️ Fechar clicando fora ou ESC
- 🔄 Carregamento automático de dados

### Confirmações de Segurança
- ⚠️ Diálogo de confirmação antes de excluir
- 🛡️ Proteções para não deletar a própria conta
- 🔒 Validações de permissões em todas as ações

---

## 🔐 SISTEMA DE SEGURANÇA

### Proteções Implementadas
1. **Senhas criptografadas**: Hash bcrypt de alta segurança
2. **Proteção de sessão**: Verificação em todas as páginas
3. **Validação de permissões**: Hierarquia rigorosa (user < admin < superadmin)
4. **Sanitização de dados**: Proteção contra XSS e SQL Injection
5. **Proteções especiais**:
   - Super admin não pode se auto-deletar
   - Super admin não pode remover sua própria permissão
   - Contas inativas são bloqueadas automaticamente

---

## 🗂️ ESTRUTURA DE ARQUIVOS

```
melodias/
├── config.php          # Configurações centralizadas e funções
├── setup_banco.php     # Instalação do banco (DELETAR após usar!)
├── login.php           # Página de login
├── painel.php          # Sistema principal (completo)
├── banco_melodias.sqlite  # Banco de dados SQLite
└── uploads/            # Pasta com arquivos enviados
```

---

## 📊 ESTRUTURA DO BANCO DE DADOS

### Tabela: profissionais (Usuários)
- `id`: ID único
- `nome`: Nome completo
- `email`: Email (único)
- `senha`: Senha criptografada
- `especialidade`: Área de atuação
- `whatsapp`: Telefone
- `role`: Permissão (user/admin/superadmin)
- `status`: Estado da conta (ativo/inativo)
- `created_at`: Data de criação
- `updated_at`: Última atualização

### Tabela: materiais
- `id`: ID único
- `titulo`: Título do material
- `categoria`: Categoria
- `caminho`: Caminho do arquivo
- `visibilidade`: todos/admin
- `created_by`: Quem criou
- `created_at`: Data de criação

### Tabela: sugestoes
- `id`: ID único
- `user_id`: Quem enviou
- `texto`: Conteúdo da sugestão
- `status`: nova/Em Análise/Aprovada/Arquivada
- `created_at`: Data de envio

---

## ❓ PERGUNTAS FREQUENTES

### Como resetar a senha de um usuário?
**Resposta**: Super admin pode editar o usuário e preencher o campo "Nova Senha"

### Como promover um usuário a admin?
**Resposta**: Super admin → Usuários → Editar → Alterar "Nível de Permissão"

### O que acontece se eu deletar um usuário?
**Resposta**: Todas as sugestões dele são removidas, mas os materiais que ele criou permanecem

### Como restringir um material apenas para admins?
**Resposta**: Ao criar/editar, selecione "Restrito" em "Visibilidade"

### Posso ter mais de um super admin?
**Resposta**: Sim! O super admin pode promover outros usuários a super admin

### Como fazer backup do sistema?
**Resposta**: Copie o arquivo `banco_melodias.sqlite` e a pasta `uploads/`

---

## 🎯 FLUXO DE USO RECOMENDADO

### Para Super Admin:
1. Fazer login
2. Criar usuários iniciais
3. Definir quem será admin
4. Adicionar materiais iniciais
5. Monitorar sugestões

### Para Admin:
1. Fazer login
2. Gerenciar biblioteca (adicionar/editar/remover materiais)
3. Moderar sugestões dos membros

### Para Usuários:
1. Fazer login
2. Acessar biblioteca e baixar materiais
3. Enviar sugestões de temas

---

## 🛠️ CONFIGURAÇÕES AVANÇADAS

### Alterar credenciais do Super Admin
Edite `config.php`:
```php
define('SUPER_ADMIN_EMAIL', 'seuemail@exemplo.com');
define('SUPER_ADMIN_PASSWORD', 'SuaSenhaAqui');
```
Depois rode `setup_banco.php` novamente para aplicar.

### Alterar limite de arquivo
Edite `php.ini`:
```
upload_max_filesize = 20M
post_max_size = 20M
```

---

## 💡 DICAS PROFISSIONAIS

1. **Delete setup_banco.php** após instalação (segurança)
2. **Faça backups regulares** do arquivo SQLite
3. **Use senhas fortes** para todos os usuários
4. **Não compartilhe** credenciais de admin
5. **Revise sugestões regularmente** para engajar membros
6. **Organize materiais por categoria** consistente
7. **Desative contas** ao invés de deletar (mantém histórico)

---

## 📞 SUPORTE

Sistema desenvolvido com:
- PHP 7.4+
- SQLite3
- HTML5/CSS3
- JavaScript (Vanilla)
- Font Awesome 6.4
- Google Fonts (Inter)

**Desenvolvido por**: GitHub Copilot
**Versão**: 2.0
**Data**: 2026

---

## ✅ CHECKLIST PÓS-INSTALAÇÃO

- [ ] Executou setup_banco.php
- [ ] Fez login com super admin
- [ ] Deletou setup_banco.php
- [ ] Criou pelo menos 1 usuário teste
- [ ] Adicionou pelo menos 1 material
- [ ] Testou enviar sugestão
- [ ] Testou modo escuro
- [ ] Testou responsividade mobile
- [ ] Fez backup do banco de dados

---

## 🎊 RECURSOS COMPLETOS

✅ Sistema de permissões em 3 níveis
✅ CRUD completo de usuários (apenas superadmin)
✅ CRUD completo de materiais (admin+)
✅ Sistema de sugestões com status
✅ Upload de arquivos PDF
✅ Busca em tempo real
✅ Modo escuro/claro
✅ Toasts de notificação elegantes
✅ Modais reutilizáveis
✅ Confirmações de delete
✅ Design responsivo profissional
✅ Animações suaves
✅ Proteções de segurança avançadas
✅ Sistema de sessões seguro
✅ Senhas criptografadas com bcrypt
✅ Sanitização de inputs
✅ Validações de permissões
✅ Interface intuitiva e moderna

---

🚀 **SISTEMA PRONTO PARA PRODUÇÃO!**
#   m e l o d i a s - k a r e n - g o m e s  
 