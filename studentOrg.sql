CREATE TABLE users (
    user_id        INT AUTO_INCREMENT  PRIMARY KEY,
    student_number VARCHAR(20)         NOT NULL UNIQUE,
    name           VARCHAR(100)        NOT NULL,
    email          VARCHAR(150)        NOT NULL UNIQUE,
    password_hash  VARCHAR(255)        NOT NULL,
    created_at     TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE organizations (
    org_id           INT AUTO_INCREMENT  PRIMARY KEY,
    name             VARCHAR(100)        NOT NULL UNIQUE,
    description      TEXT,
    starting_balance DECIMAL(12,2)       NOT NULL DEFAULT 0.00,
    current_balance  DECIMAL(12,2)       NOT NULL DEFAULT 0.00,
    created_at       TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_roles (
    user_id  INT         NOT NULL,
    org_id   INT         NOT NULL,
    role     VARCHAR(20) NOT NULL CHECK (role IN ('treasurer', 'officer', 'member', 'adviser')),
    PRIMARY KEY (user_id, org_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (org_id)  REFERENCES organizations(org_id) ON DELETE CASCADE
);

CREATE TABLE transactions (
    transaction_id   INT AUTO_INCREMENT  PRIMARY KEY,
    org_id           INT                 NOT NULL,
    entered_by       INT                 NOT NULL,
    created_at       TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_locked        BOOLEAN             NOT NULL DEFAULT FALSE,
    FOREIGN KEY (org_id)     REFERENCES organizations(org_id) ON DELETE CASCADE,
    FOREIGN KEY (entered_by) REFERENCES users(user_id) ON DELETE RESTRICT
);

CREATE TABLE transaction_details (
    transaction_id   INT           PRIMARY KEY,
    type             VARCHAR(10)   NOT NULL CHECK (type IN ('income', 'expense')),
    amount           DECIMAL(12,2) NOT NULL CHECK (amount > 0),
    description      TEXT,
    transaction_date DATE          NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE CASCADE
);

CREATE TABLE transaction_audit_log (
    log_id         INT AUTO_INCREMENT  PRIMARY KEY,
    transaction_id INT                 NOT NULL,
    changed_by     INT                 NOT NULL,
    action         VARCHAR(10)         NOT NULL CHECK (action IN ('INSERT', 'UPDATE', 'DELETE')),
    changed_at     TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by)     REFERENCES users(user_id) ON DELETE RESTRICT
);

CREATE TABLE transaction_audit_old (
    log_id      INT           PRIMARY KEY,
    type        VARCHAR(10),
    amount      DECIMAL(12,2),
    description TEXT,
    tx_date     DATE,
    FOREIGN KEY (log_id) REFERENCES transaction_audit_log(log_id) ON DELETE CASCADE
);

CREATE TABLE transaction_audit_new (
    log_id      INT           PRIMARY KEY,
    type        VARCHAR(10),
    amount      DECIMAL(12,2),
    description TEXT,
    tx_date     DATE,
    FOREIGN KEY (log_id) REFERENCES transaction_audit_log(log_id) ON DELETE CASCADE
);


-- Indeces for Faster Lookups and Joins --

-- Speeds up finding roles of a specific user (authorization checks, user joins)
CREATE INDEX idx_user_roles_user ON user_roles(user_id);

-- Speeds up retrieving all users in an organization (membership queries, org joins)
CREATE INDEX idx_user_roles_org ON user_roles(org_id);


-- Speeds up fetching transactions of a specific organization
CREATE INDEX idx_transactions_org ON transactions(org_id);

-- Speeds up retrieving transactions entered by a specific user (audit/user activity)
CREATE INDEX idx_transactions_user ON transactions(entered_by);


-- Speeds up lookup of audit logs for a specific transaction (history tracking)
CREATE INDEX idx_audit_tx ON transaction_audit_log(transaction_id);

-- Speeds up retrieving audit logs by user (activity monitoring)
CREATE INDEX idx_audit_user ON transaction_audit_log(changed_by);


-- Sample Data Seeding --

-- USERS
INSERT INTO users (student_number, name, email, password_hash)
VALUES
('2023001', 'Jhon Chriztopher Nice', 'tops@up.edu.ph', 'hashed_pw_1'),
('2023002', 'Samantha Mok', 'sam@up.edu.ph', 'hashed_pw_2'),
('2023003', 'Aleighia Keith Reyes', 'keith@up.edu.ph', 'hashed_pw_3');

-- ORGANIZATIONS
INSERT INTO organizations (name, description, starting_balance, current_balance)
VALUES
('Komsai.Org', 'Computer Science Org', 1000.00, 1000.00),
('Pawradise', 'Animal Welfare Org', 500.00, 500.00);

-- ROLES
INSERT INTO user_roles (user_id, org_id, role)
VALUES
(1, 1, 'treasurer'),
(2, 1, 'member'),
(3, 2, 'treasurer');

-- TRANSACTIONS 
INSERT INTO transactions (org_id, entered_by, is_locked)
VALUES
(1, 1, FALSE),
(1, 1, FALSE),
(2, 3, FALSE);

-- DETAILS
INSERT INTO transaction_details (transaction_id, type, amount, description, transaction_date)
VALUES
(1, 'income', 500.00, 'Membership fees', '2026-01-01'),
(2, 'expense', 200.00, 'Event supplies', '2026-01-02'),
(3, 'income', 300.00, 'Donation', '2026-01-03');

-- AUDIT LOG
INSERT INTO transaction_audit_log (transaction_id, changed_by, action)
VALUES
(1, 1, 'INSERT'),
(2, 1, 'INSERT'),
(3, 3, 'INSERT');