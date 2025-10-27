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
- MySQL/PostgreSQL
- Symfony CLI (opcional)

### 1. Clone o repositório
```bash
git clone <repository-url>
cd desafio-backend
```

### 2. Instale as dependências
```bash
composer install
```

### 3. Configure o banco de dados

**IMPORTANTE**: O arquivo `.env` está incluído no repositório para facilitar a configuração inicial, mas **você deve ajustar as credenciais do banco de dados** conforme seu ambiente.

Edite o arquivo `.env` e configure:
```env
# Database
DATABASE_URL="mysql://usuario:senha@127.0.0.1:3306/nome_do_banco?serverVersion=8.0.32&charset=utf8mb4"
```

### 4. Crie o banco de dados
```bash
php bin/console doctrine:database:create
```

### 5. Execute as migrações
```bash
php bin/console doctrine:migrations:migrate
```

### 6. Inicie o servidor
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
Todos os endpoints (exceto login) requerem autenticação JWT:

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

### Endpoints Principais

#### Administração
- `POST /admin/register` - Registrar administrador
- `POST /admin/login` - Fazer login
- `GET /admin/` - Listar administradores
- `GET /admin/audit-logs` - Logs de auditoria

#### Igrejas
- `POST /church/create` - Criar igreja (formulário)
- `GET /church/` - Listar igrejas (paginado + busca)
- `GET /church/{id}` - Visualizar igreja
- `PUT /church/{id}/update` - Atualizar igreja (formulário + JSON)
- `DELETE /church/{id}/delete` - Deletar igreja
- `GET /church/{id}/members` - Membros da igreja (paginado)

#### Membros
- `POST /member/create` - Criar membro (formulário)
- `GET /member/` - Listar membros (filtros + busca)
- `GET /member/{id}` - Visualizar membro
- `PUT /member/{id}` - Atualizar membro (formulário + JSON)
- `DELETE /member/{id}/delete` - Soft-delete membro
- `POST /member/{id}/restore` - Restaurar membro

#### Transferências
- `POST /member-transfer/create` - Criar transferência (formulário)
- `GET /member-transfer/` - Listar transferências (filtros + busca)
- `GET /member-transfer/{id}` - Visualizar transferência
- `PUT /member-transfer/{id}/update` - Atualizar transferência (formulário + JSON)
- `DELETE /member-transfer/{id}/delete` - Deletar transferência
- `GET /member-transfer/member/{id}/history` - Histórico do membro

## Testando a API

### Exemplos de Uso

#### 1. Criar uma igreja
```bash
curl -X POST "http://localhost:8000/church/create" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "name=Igreja Central&document_type=CNPJ&document_number=11222333000181&internal_code=IC001&phone=(11) 99999-1111&address_street=Rua das Flores&address_number=123&city=São Paulo&state=SP&cep=01234-567&website=https://igrejacentral.com&members_limit=100"
```

#### 2. Criar um membro
```bash
curl -X POST "http://localhost:8000/member/create" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "name=João Silva&document_type=CPF&document_number=11144477735&email=joao@email.com&phone=(11) 99999-3333&birth_date=1990-05-15&address_street=Rua das Palmeiras&address_number=456&city=São Paulo&state=SP&cep=01234-567&church_id=1"
```

#### 3. Listar igrejas com busca
```bash
curl -H "Authorization: Bearer SEU_TOKEN" "http://localhost:8000/church/?search=Central"
```

#### 4. Listar membros com filtro
```bash
curl -H "Authorization: Bearer SEU_TOKEN" "http://localhost:8000/member/?church_id=1&search=João"
```

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

## Estrutura do Projeto

```
src/
├── Controller/          # Controllers da API
│   ├── AdminController.php
│   ├── ChurchController.php
│   ├── MemberController.php
│   └── MemberTransferController.php
├── Entity/             # Entidades Doctrine
│   ├── Admin.php
│   ├── AuditLog.php
│   ├── Church.php
│   ├── Member.php
│   ├── MemberTransfer.php
│   └── Traits/
│       └── SoftDeleteableTrait.php
├── DTO/                # Data Transfer Objects
│   ├── ChurchDTO.php
│   ├── CreateChurchDTO.php
│   ├── UpdateChurchDTO.php
│   ├── ChurchListDTO.php
│   ├── MemberDTO.php
│   ├── CreateMemberDTO.php
│   ├── UpdateMemberDTO.php
│   ├── MemberListDTO.php
│   ├── MemberTransferDTO.php
│   ├── CreateMemberTransferDTO.php
│   ├── UpdateMemberTransferDTO.php
│   └── MemberTransferListDTO.php
├── Service/            # Serviços de negócio
│   ├── AuditService.php
│   ├── ChurchDTOService.php
│   ├── MemberDTOService.php
│   ├── MemberTransferDTOService.php
│   └── SoftDeleteService.php
├── Validator/          # Validadores customizados
│   ├── ChurchValidator.php
│   ├── MemberValidator.php
│   └── MemberTransferValidator.php
└── Repository/         # Repositórios Doctrine
    ├── AdminRepository.php
    ├── AuditLogRepository.php
    ├── ChurchRepository.php
    ├── MemberRepository.php
    └── MemberTransferRepository.php
```

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

## Troubleshooting

### Problemas Comuns

1. **Erro de conexão com banco**
   - Verifique as credenciais no `.env`
   - Confirme se o banco existe
   - Execute `php bin/console doctrine:database:create`

2. **Erro de autenticação**
   - Verifique se o token JWT é válido
   - Confirme se o usuário está ativo
   - Teste o login novamente

3. **Erro de validação**
   - Verifique se os documentos são válidos
   - Confirme se os IDs existem
   - Verifique se os emails são únicos por igreja

4. **Swagger não carrega**
   - Acesse `http://localhost:8000/swagger.html`
   - Verifique se o servidor está rodando

## Suporte

Para dúvidas ou problemas:
1. Verifique a documentação do Swagger
2. Consulte os logs em `var/log/`
3. Teste os endpoints com curl ou Postman
4. Verifique os logs de auditoria em `/admin/audit-logs`

---

**Desenvolvido com Symfony 7**