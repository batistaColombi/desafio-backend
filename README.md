# Desafio Backend - Symfony

Projeto backend desenvolvido em Symfony para gerenciar igrejas e membros, com suporte a transferências de membros e validações de regras de negócio.

## Tecnologias

- PHP 8+
- Symfony 6
- Doctrine ORM
- PostgreSQL
- Symfony UX Turbo
- Docker (para Mercure)

## Estrutura do Projeto

- `src/Entity` → Entidades do sistema (`Church`, `Member`, `MemberTransfer`)
- `src/Controller` → Controladores para cada recurso
- `src/Services/Validator` → Validações de regras de negócio
- `migrations` → Scripts de migração do banco de dados
- `templates` → Templates Twig para Turbo Streams
- `public` → Arquivos públicos (JS, CSS, index.php)

## Funcionalidades

- Cadastro, atualização e remoção de igrejas e membros
- Transferência de membros entre igrejas
- Validações:
  - Limite máximo de membros por igreja
  - Validação de CPF/CNPJ dos membros
- Atualização automática via Symfony UX Turbo (Mercure)

## Instalação

1. Clone o repositório: git clone https://github.com/batistaColombi/desafio-backend.git
cd desafio-backend

2. Instale as dependências: composer install

3. Configure o ambiente: DATABASE_URL="postgresql://usuario:senha@127.0.0.1:5432/nome_do_banco?serverVersion=15&charset=utf8"

4. Crie o banco de dados e execute as migrações: php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate

6. Suba o servidor local: symfony server:start

6. Testes: php bin/phpunit
