# Spec: Página de Perfil Completa com Tabs

**Data:** 2026-04-07  
**Status:** Aprovado

---

## Objetivo

Expandir a página `/perfil` de uma tela simples com um modal de edição limitado para uma página de conta completa, organizada em abas, cobrindo dados pessoais, endereços, segurança e pedidos.

---

## Layout Geral

A página mantém o **header do perfil atual** no topo (avatar, nome, e-mail, badges). Abaixo dele, um componente de abas substitui o grid de dois cards atual.

```
┌─────────────────────────────────────────────────┐
│  HEADER DO PERFIL (sem mudança)                  │
│  Avatar | Nome | Email | Membro desde            │
└─────────────────────────────────────────────────┘

[ Dados Pessoais ] [ Endereços ] [ Segurança ] [ Pedidos ]
─────────────────────────────────────────────────────────
  <conteúdo da aba ativa>
```

O botão "Editar Perfil" do header é removido — a edição acontece diretamente nas abas.

A aba ativa é mantida via hash da URL (`#dados`, `#enderecos`, `#seguranca`, `#pedidos`) para que o refresh preserve a seleção.

---

## Aba 1 — Dados Pessoais

**UI:** Formulário inline (sem modal) com campos:
- Nome completo (`name`) — texto
- E-mail (`email`) — texto, readonly (identificador de conta, não editável)
- Telefone (`phone`) — texto com máscara `(XX) XXXXX-XXXX`

**Comportamento:**
- Submit via AJAX (`PUT /perfil`)
- Ao salvar com sucesso: atualiza o nome no header do perfil em tempo real, exibe mensagem de sucesso inline
- Erros de validação exibidos inline abaixo de cada campo

**Backend:** `SiteController::perfil_update()` passa a aceitar e salvar o campo `name` (mínimo 2 chars, máximo 255). O e-mail **não** é editável — campo readonly no frontend, ignorado no backend.

---

## Aba 2 — Endereços

**UI:**
- Grid de cards, um por endereço cadastrado
- Cada card exibe: endereço completo, CEP formatado, cidade/estado
- Cada card tem botões **Editar** e **Excluir**
- Botão "Adicionar Endereço" no topo da seção, abre modal
- Estado vazio: mensagem "Nenhum endereço cadastrado" + botão para adicionar

**Modal de Adicionar/Editar Endereço:**
- Campos: CEP, Rua, Número, Complemento (opcional), Bairro, Cidade, Estado
- Campo CEP com lookup automático via ViaCEP ao perder foco (`GET https://viacep.com.br/ws/{cep}/json/`) — preenche Rua, Bairro, Cidade, Estado automaticamente
- Submit via AJAX (`POST /enderecos` para novo, `PUT /enderecos/{id}` para edição)
- Ao salvar: fecha modal, atualiza lista de cards sem reload

**Exclusão:**
- Confirmação inline no card (dois botões: Confirmar / Cancelar) antes de deletar
- `DELETE /enderecos/{id}` via AJAX
- Se endereço tem pedido vinculado: exibe mensagem de erro retornada pelo backend

**Backend:** Nenhuma mudança — `endereco_store`, `endereco_update`, `endereco_destroy` já implementados e funcionais.

---

## Aba 3 — Segurança

**UI:** Formulário inline (sem modal) com campos:
- Senha atual
- Nova senha
- Confirmar nova senha

**Comportamento:**
- Submit via AJAX (`PUT /perfil`)
- Campos de dados pessoais não são enviados nesta submissão (form separado)
- Ao salvar: exibe mensagem de sucesso, limpa os campos de senha
- Erros de validação inline

**Backend:** `perfil_update()` já suporta troca de senha. Nenhuma mudança necessária.

---

## Aba 4 — Pedidos

**UI:**
- Cards de estatísticas no topo: total de pedidos (excluindo `carrinho`), total de favoritos — valores reais do banco
- Tabela dos últimos 5 pedidos com colunas: Nº do pedido, Data, Valor total, Status (badge colorido), Link "Ver detalhes"
- Botão "Ver todos os pedidos" → `/meus-pedidos`
- Estado vazio: mensagem "Nenhum pedido realizado ainda" + botão para ir às compras

**Status badges** (mesma paleta da página de admin):
- `pendente` → borda cinza
- `processando` → borda preta
- `enviado` → borda preta, fundo preto, texto branco
- `entregue` → fundo preto, texto branco
- `cancelado` → borda vermelha

**Backend:** `SiteController::perfil()` passa a carregar:
- `$pedidosRecentes`: últimos 5 pedidos com `status != 'carrinho'`, eager load `itens`
- `$totalPedidos`: count de pedidos com `status != 'carrinho'`
- `$totalFavoritos`: count de favoritos do usuário

---

## Mudanças de Arquivos

| Arquivo | Mudança |
|---|---|
| `resources/views/site/perfil.blade.php` | Reescrita completa: tabs + 4 seções + modal de endereço |
| `app/Http/Controllers/SiteController.php` | `perfil()`: adicionar pedidos/stats; `perfil_update()`: aceitar `name` |
| `public/js/profile-edit.js` | Expandir: tabs, forms inline, CRUD de endereços, lookup CEP |

Nenhuma migration, rota nova ou model novo necessários.

---

## Estilo

Segue o design "Sober Tech" existente:
- Sem bordas arredondadas
- Tipografia monospace para labels e badges
- Paleta preto/branco/cinza
- Borders `border-[var(--color-lab-border)]`
- Tabs: linha inferior preta na aba ativa, cinza nas inativas

---

## Fora de Escopo

- Upload de foto de perfil
- Exclusão de conta
- Notificações / preferências de e-mail
- Avaliações de produtos (stat zerada removida ou mantida estática)
