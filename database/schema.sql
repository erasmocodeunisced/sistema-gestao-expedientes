CREATE DATABASE IF NOT EXISTS sistema_gestao_expedientes
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sistema_gestao_expedientes;

CREATE TABLE IF NOT EXISTS roles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_name (name),
    UNIQUE KEY uq_roles_slug (slug),
    KEY idx_roles_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_permissions_name (name),
    UNIQUE KEY uq_permissions_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_user_roles_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_role
        FOREIGN KEY (role_id) REFERENCES roles (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role
        FOREIGN KEY (role_id) REFERENCES roles (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission
        FOREIGN KEY (permission_id) REFERENCES permissions (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS expedientes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference_code VARCHAR(50) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NULL,
    document_type VARCHAR(100) NULL,
    origin VARCHAR(150) NULL,
    destination VARCHAR(150) NULL,
    priority VARCHAR(30) NOT NULL DEFAULT 'normal',
    status VARCHAR(30) NOT NULL DEFAULT 'received',
    received_at DATETIME NOT NULL,
    closed_at DATETIME NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_expedientes_reference_code (reference_code),
    KEY idx_expedientes_status (status),
    KEY idx_expedientes_priority (priority),
    KEY idx_expedientes_received_at (received_at),
    KEY idx_expedientes_created_by (created_by),
    KEY idx_expedientes_destination (destination),
    CONSTRAINT fk_expedientes_created_by
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_expedientes_updated_by
        FOREIGN KEY (updated_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tramitacoes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    expediente_id BIGINT UNSIGNED NOT NULL,
    sender_user_id BIGINT UNSIGNED NULL,
    recipient_user_id BIGINT UNSIGNED NULL,
    destination VARCHAR(150) NOT NULL,
    responsible_user_id BIGINT UNSIGNED NULL,
    instructions TEXT NULL,
    observation TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'sent',
    sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    received_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tramitacoes_expediente (expediente_id),
    KEY idx_tramitacoes_sender (sender_user_id),
    KEY idx_tramitacoes_recipient (recipient_user_id),
    KEY idx_tramitacoes_responsible (responsible_user_id),
    KEY idx_tramitacoes_destination (destination),
    KEY idx_tramitacoes_status (status),
    CONSTRAINT fk_tramitacoes_expediente
        FOREIGN KEY (expediente_id) REFERENCES expedientes (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_tramitacoes_sender
        FOREIGN KEY (sender_user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_tramitacoes_recipient
        FOREIGN KEY (recipient_user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_tramitacoes_responsible
        FOREIGN KEY (responsible_user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS despachos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    expediente_id BIGINT UNSIGNED NOT NULL,
    tramitacao_id BIGINT UNSIGNED NULL,
    author_user_id BIGINT UNSIGNED NULL,
    content TEXT NOT NULL,
    decision VARCHAR(50) NULL,
    observation TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'registered',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_despachos_expediente (expediente_id),
    KEY idx_despachos_tramitacao (tramitacao_id),
    KEY idx_despachos_author (author_user_id),
    KEY idx_despachos_status (status),
    CONSTRAINT fk_despachos_expediente
        FOREIGN KEY (expediente_id) REFERENCES expedientes (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_despachos_tramitacao
        FOREIGN KEY (tramitacao_id) REFERENCES tramitacoes (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_despachos_author
        FOREIGN KEY (author_user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS arquivos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    expediente_id BIGINT UNSIGNED NOT NULL,
    archived_by BIGINT UNSIGNED NULL,
    archive_reference VARCHAR(150) NULL,
    notes TEXT NULL,
    archived_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_arquivos_expediente (expediente_id),
    KEY idx_arquivos_archived_by (archived_by),
    KEY idx_arquivos_archived_at (archived_at),
    CONSTRAINT fk_arquivos_expediente
        FOREIGN KEY (expediente_id) REFERENCES expedientes (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_arquivos_archived_by
        FOREIGN KEY (archived_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS auditoria (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_auditoria_user (user_id),
    KEY idx_auditoria_entity (entity_type, entity_id),
    KEY idx_auditoria_action (action),
    KEY idx_auditoria_created_at (created_at),
    CONSTRAINT fk_auditoria_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO roles (name, slug, description)
VALUES
    ('Administrador', 'administrador', 'Acesso administrativo completo ao sistema.'),
    ('Técnico', 'tecnico', 'Registo e acompanhamento operacional de expedientes.'),
    ('Consulta', 'consulta', 'Acesso de consulta aos expedientes autorizados.')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO permissions (name, slug, description)
VALUES
    ('Consultar utilizadores', 'users.view', 'Consultar utilizadores.'),
    ('Gerir utilizadores', 'users.manage', 'Criar, atualizar e desativar utilizadores.'),
    ('Consultar papéis e permissões', 'rbac.view', 'Consultar a configuração de acesso.'),
    ('Gerir papéis e permissões', 'rbac.manage', 'Criar e configurar papéis e permissões.'),
    ('Consultar expedientes', 'expedientes.view', 'Consultar expedientes.'),
    ('Gerir expedientes', 'expedientes.manage', 'Registar e atualizar expedientes.'),
    ('Tramitar expedientes', 'tramitacoes.manage', 'Encaminhar e atualizar tramitações.'),
    ('Consultar tramitações', 'tramitacoes.view', 'Consultar o histórico de tramitações.'),
    ('Consultar despachos', 'despachos.view', 'Consultar despachos associados aos expedientes.'),
    ('Registar despachos', 'despachos.manage', 'Registar e atualizar despachos.'),
    ('Gerir arquivo', 'arquivos.manage', 'Arquivar e consultar expedientes arquivados.'),
    ('Consultar arquivo', 'arquivos.view', 'Consultar expedientes arquivados.'),
    ('Consultar auditoria', 'auditoria.view', 'Consultar o histórico de auditoria.'),
    ('Consultar relatórios', 'relatorios.view', 'Consultar relatórios do sistema.')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    updated_at = CURRENT_TIMESTAMP;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions
WHERE roles.slug = 'administrador';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions
    ON permissions.slug IN (
        'expedientes.view',
        'expedientes.manage',
        'tramitacoes.manage',
        'tramitacoes.view',
        'despachos.manage',
        'despachos.view',
        'arquivos.manage',
        'arquivos.view'
    )
WHERE roles.slug = 'tecnico';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions
    ON permissions.slug IN ('expedientes.view', 'tramitacoes.view', 'despachos.view', 'arquivos.view', 'relatorios.view')
WHERE roles.slug = 'consulta';

INSERT INTO users (full_name, email, password_hash)
VALUES (
    'Administrador do Sistema',
    'admin@sistema.local',
    '$2y$10$bGnAAVMZb19Jdf5W8UykLOIk/sb3PN4hXqCoNDPkrdltdG./8kBuC'
)
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    updated_at = CURRENT_TIMESTAMP;

SET @admin_user_id = (
    SELECT id FROM users WHERE email = 'admin@sistema.local' LIMIT 1
);

INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT @admin_user_id, id
FROM roles
WHERE slug = 'administrador';