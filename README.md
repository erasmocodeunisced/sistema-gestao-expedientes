# Sistema de Gestão de Expedientes

## Descrição

O **Sistema de Gestão de Expedientes** é uma aplicação web desenvolvida no âmbito da disciplina de **Engenharia de Software**, da Licenciatura em **Engenharia Informática da Universidade Aberta ISCED (UnISCED)**.

O sistema tem como finalidade apoiar a **gestão, registo, tramitação, despacho e arquivo de expedientes**, permitindo organizar e acompanhar o ciclo de vida dos documentos dentro de uma instituição.

A aplicação contará com um mecanismo de **Controlo de Acesso Baseado em Papéis (RBAC – Role-Based Access Control)**, garantindo que cada utilizador tenha acesso apenas às funcionalidades e operações correspondentes ao seu papel.

O sistema também contemplará **autenticação de utilizadores, gestão de permissões, tramitação de expedientes, despacho, arquivo, auditoria e geração de relatórios**, contribuindo para maior organização, segurança, rastreabilidade e eficiência na gestão documental.

## Objetivo

Desenvolver um sistema informatizado capaz de gerir o ciclo de vida dos expedientes, desde a sua entrada e registo até à tramitação, despacho e arquivo, assegurando o controlo de acesso e a rastreabilidade das operações realizadas pelos utilizadores.

## Principais Funcionalidades

* Autenticação e gestão de utilizadores;
* Controlo de acesso baseado em papéis (RBAC);
* Registo e gestão de expedientes;
* Encaminhamento e tramitação de documentos;
* Gestão de despachos;
* Arquivo de expedientes;
* Auditoria das operações realizadas;
* Geração de relatórios;
* Consulta do histórico dos expedientes.

## Como executar o sistema

Estas instruções permitem executar o projeto localmente depois de o clonar ou descarregar do GitHub.

### Requisitos

* PHP 8.1 ou superior, com as extensões `pdo` e `pdo_mysql` ativadas;
* MariaDB 10.4 ou superior (ou MySQL compatível);
* Git, caso o projeto seja clonado por linha de comandos;
* Um navegador atualizado.

O XAMPP pode ser utilizado para instalar e iniciar facilmente o PHP e o MariaDB. Neste caso, abra o painel do XAMPP e inicie o módulo **MySQL**. Não é necessário iniciar o Apache quando for usado o servidor embutido do PHP.

### 1. Obter o projeto

Pelo GitHub Desktop, escolha **Clone a repository**, informe o endereço do repositório e selecione uma pasta local.

Ou, no PowerShell:

```powershell
git clone URL_DO_REPOSITORIO
cd sistema-gestao-expedientes
```

Substitua `URL_DO_REPOSITORIO` pelo endereço apresentado no botão **Code** do GitHub.

### 2. Criar a base de dados

O ficheiro `database/schema.sql` cria automaticamente a base `sistema_gestao_expedientes`, as tabelas, os papéis, as permissões e o utilizador administrativo inicial.

#### Opção A: phpMyAdmin

1. Abra `http://localhost/phpmyadmin`.
2. Selecione o separador **Importar**.
3. Escolha o ficheiro `database/schema.sql` deste projeto.
4. Clique em **Executar** e confirme que não foram apresentados erros.

#### Opção B: terminal do MariaDB/MySQL

Na raiz do projeto, execute:

```powershell
mysql -u root -p < database/schema.sql
```

Quando o utilizador `root` não tiver palavra-passe, pressione **Enter** quando ela for solicitada. Se o comando `mysql` não for reconhecido, use o executável `mysql.exe` existente na pasta `bin` do XAMPP ou faça a importação pelo phpMyAdmin.

### 3. Confirmar a configuração

Por padrão, a aplicação procura a base de dados com estes dados em `config/config.php`:

```text
Servidor: 127.0.0.1
Porta: 3306
Base de dados: sistema_gestao_expedientes
Utilizador: root
Palavra-passe: vazia
```

Se o MariaDB/MySQL usar outro utilizador, palavra-passe, porta ou servidor, altere esses valores antes de iniciar a aplicação. Não publique credenciais reais no GitHub.

### 4. Iniciar a aplicação

Na pasta raiz do projeto, execute:

```powershell
php -S localhost:8000 -t public
```

Mantenha esse terminal aberto e aceda no navegador a:

```text
http://localhost:8000/
```

Para parar o servidor, volte ao terminal e pressione `Ctrl+C`.

### 5. Aceder à demonstração

O utilizador administrativo criado pelo esquema é:

```text
Email: admin@sistema.local
Palavra-passe: Admin@2026!UnISCED
```

Depois do primeiro acesso, recomenda-se alterar a palavra-passe em **Utilizadores**. Estas credenciais são apenas para demonstração local e não devem ser usadas num ambiente real.

### 6. Roteiro rápido para apresentação

Depois de iniciar sessão, o docente pode demonstrar:

1. **Dashboard**: visão geral do sistema e dos indicadores.
2. **Expedientes > Novo expediente**: registo de um documento.
3. **Expedientes**: consulta, filtragem e abertura dos detalhes.
4. **Tramitações**: encaminhamento do expediente para outro destino/responsável.
5. **Despachos**: registo de uma decisão administrativa.
6. **Arquivo**: arquivo de um expediente concluído.
7. **Relatórios**: consulta dos dados consolidados.
8. **Auditoria**: verificação das operações realizadas.
9. **Utilizadores**: gestão de contas e demonstração do controlo de acesso por papéis.

Para testar as restrições de acesso, crie em **Utilizadores** uma conta com o papel `consulta`, termine a sessão e entre com essa conta. As opções disponíveis serão limitadas às permissões atribuídas ao papel.

### Resolução de problemas

* **Erro de ligação à base de dados**: confirme se o MariaDB/MySQL está iniciado, se a base foi importada e se os dados em `config/config.php` estão corretos.
* **`php` ou `mysql` não é reconhecido**: adicione as pastas de instalação do PHP e do MariaDB/MySQL ao `PATH`, ou utilize os executáveis diretamente a partir do XAMPP.
* **Página não abre**: confirme que o comando foi executado na raiz do projeto e que está a utilizar `http://localhost:8000/`.
* **Porta 8000 ocupada**: inicie com outra porta, por exemplo `php -S localhost:8080 -t public`, e aceda a `http://localhost:8080/`.

## Autores

* **Erasmo** — `erasmocodeunisced`
* **Sérgio** — `sergio-dev2026`

## Instituição

**Universidade Aberta ISCED (UnISCED)**
**Faculdade de Engenharia e Agricultura**
**Licenciatura em Engenharia Informática**
**Disciplina: Engenharia de Software**
