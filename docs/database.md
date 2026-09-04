# Modelo de dados

O esquema principal está em `database/schema.sql` e foi desenhado para MariaDB 10.4 com tabelas InnoDB, `utf8mb4`, chaves estrangeiras e índices para as consultas principais.

## Entidades

- `users`: utilizadores, credenciais com hash e estado da conta.
- `roles`: papéis que podem ser adicionados ou desativados sem alterar o código.
- `permissions`: permissões atómicas do sistema.
- `user_roles`: relação entre utilizadores e papéis.
- `role_permissions`: relação entre papéis e permissões.
- `expedientes`: registo principal dos documentos recebidos.
- `tramitacoes`: encaminhamentos entre utilizadores de um expediente.
- `despachos`: decisões ou orientações associadas a um expediente, com estado, observação e responsável.
- `arquivos`: informação de arquivo de um expediente.
- `auditoria`: registo de ações, entidade afetada e valores JSON antes/depois.

## Relacionamentos principais

- Um utilizador pode possuir vários papéis; um papel pode pertencer a vários utilizadores.
- Um papel pode possuir várias permissões; uma permissão pode estar associada a vários papéis.
- Um expediente pode possuir várias tramitações e despachos.
- Um expediente pode possuir um registo de arquivo, garantido pela chave única em `arquivos.expediente_id`.
- Utilizadores podem ser autores, remetentes, destinatários ou responsáveis por operações; a remoção de um utilizador preserva os registos e torna essas referências `NULL` quando aplicável.
- A auditoria mantém referências polimórficas por `entity_type` e `entity_id`, permitindo auditar futuras entidades sem criar uma tabela de auditoria por módulo.

## RBAC

O acesso é representado pelos dados de `roles`, `permissions`, `user_roles` e `role_permissions`. O seed cria os papéis `administrador`, `tecnico` e `consulta`, além das permissões iniciais. Novos papéis e permissões podem ser acrescentados por dados, sem modificar a estrutura das tabelas ou o código principal.

O utilizador administrativo inicial é criado com um hash bcrypt gerado por `password_hash` do PHP. A palavra-passe de desenvolvimento é `Admin@2026!UnISCED`; deve ser alterada ou removida antes de qualquer ambiente real.