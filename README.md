# Sistema de Gerenciamento de Igrejas e Membros

Sistema completo para gerenciamento de igrejas, membros e transferências entre igrejas, desenvolvido com Symfony 7.

## Funcionalidades

### Igrejas
- Cadastro completo com validações de documento (CPF/CNPJ)
- Código interno único obrigatório
- Limite de membros configurável
- Listagem paginada com busca por nome
- CRUD completo (Criar, Visualizar, Atualizar, Deletar)

### Membros
- Cadastro com validações de documento (CPF/CNPJ)
- Email único por igreja
- Validação de data de nascimento
- Listagem com filtros por igreja e busca por nome
- CRUD completo
- Soft-delete com auditoria

### Transferências
- Transferência de membros entre igrejas
- Validação de email único na igreja destino
- Intervalo mínimo de 10 dias entre transferências
- Histórico completo de transferências por membro
- Listagem com filtros e busca por nome do membro

### Administração
- Sistema de autenticação JWT
- Usuários administradores
- Auditoria completa de ações
- Soft-delete com registro de usuário e data

## Instalação e Configuração

### Pré-requisitos
- PHP 8.2+
- Composer
- Docker
- PostgreSQL (via Docker ou local)
- Symfony CLI (opcional)

### 1. Clone o repositório
```bash
git clone <repository-url>
cd desafio-backend
```

### 2. Suba os containers e instale as dependências
**IMPORTANTE**: O arquivo `.env` está incluído no repositório para facilitar a configuração inicial, mas **você deve ajustar as credenciais do banco de dados** conforme seu ambiente.
```bash
docker compose up -d

composer install
```

### 3. Configure o projeto

**IMPORTANTE**: O arquivo `.env` está incluído no repositório para facilitar a configuração inicial, mas **você deve ajustar as credenciais do banco de dados** conforme seu ambiente.

**Opção 1 - Docker (Recomendado):**
Siga as instruções na seção "Banco de Dados com Docker" abaixo.

**Opção 2 - PostgreSQL Local:**
Edite o arquivo `.env` e configure:
```env
# Database
DATABASE_URL="postgresql://usuario:senha@127.0.0.1:5432/nome_do_banco?serverVersion=15&charset=utf8"
```

### 4. Execute as migrações
```bash
php bin/console doctrine:migrations:migrate
```

### 5. Inicie o servidor
```bash
symfony server:start
# ou
php -S localhost:8000 -t public
```

## Documentação da API

### Swagger UI
Acesse a documentação interativa da API em:
```
http://localhost:8000/swagger.html
```

### Autenticação
Todos os endpoints (exceto login, para facilitar testes) requerem autenticação JWT:

1. **Registrar admin**:
```bash
curl -X POST "http://localhost:8000/admin/register" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","email":"admin@igreja.com","password":"123456","fullName":"Administrador"}'
```

2. **Fazer login**:
```bash
curl -X POST "http://localhost:8000/admin/login" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "username=admin&password=123456"
```

3. **Usar o token**:
```bash
curl -H "Authorization: Bearer SEU_TOKEN" "http://localhost:8000/church/"
```
  Copiar Token: Copie o token JWT retornado na resposta;
  Autorizar: Clique no botão "Authorize" no topo da página;
  Colar Token: Cole o token no campo "Value" e clique em "Authorize";
  Testar: Agora você pode testar todos os endpoints protegidos.

  Importante: O token JWT expira em 1 hora. Se receber erro 401, faça login novamente.

### Estrutura de Resposta Padrão

Todas as listagens seguem o padrão:
```json
{
  "pagination": {
    "current_page": 1,
    "total_pages": 1,
    "total_items": 4,
    "items_per_page": 10,
    "has_next": false,
    "has_previous": false
  },
  "data": [...]
}
```

## Tecnologias Utilizadas

- **Framework**: Symfony 7
- **ORM**: Doctrine 3.5
- **Validação**: Symfony Validator + Validadores customizados
- **Documentação**: Swagger/OpenAPI (NelmioApiDocBundle)
- **Paginação**: KnpPaginatorBundle
- **Autenticação**: JWT (LexikJWTAuthenticationBundle)
- **Arquitetura**: DTOs + Services + Controllers

## Validações Implementadas

### Igrejas
- Nome obrigatório
- Documento válido (CPF/CNPJ)
- Código interno único
- Limite de membros positivo

### Membros
- Nome obrigatório
- Documento válido (CPF/CNPJ)
- Email único por igreja
- Data de nascimento válida
- Igreja existente

### Transferências
- Membro existente
- Igrejas diferentes
- Email único na igreja destino
- Intervalo mínimo de 10 dias
- Igrejas existentes

## Características Especiais

- **Autenticação JWT**: Sistema completo de autenticação
- **Auditoria**: Log completo de todas as ações
- **Soft-delete**: Exclusão lógica com registro de usuário e data
- **Formulários no Swagger**: Endpoints de criação usam formulários
- **Dupla opção de update**: Formulário e JSON para updates
- **Busca por nome**: Todos os endpoints de listagem suportam busca
- **Paginação consistente**: Estrutura padronizada em todas as listagens
- **DTOs**: Separação clara entre dados de transferência e entidades
- **Validação em camadas**: DTOs + Validadores de negócio
- **Ordenação**: Listagens ordenadas por ID crescente

---

**Desenvolvido com Symfony 7**