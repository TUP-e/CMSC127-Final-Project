CREATE TABLE users (
    user_id         SERIAL PRIMARY KEY ,
    student_number  VARCHAR(20)    NOT NULL UNIQUE,
    name            VARCHAR(100)   NOT NULL,
    email           VARCHAR(150)   NOT NULL UNIQUE,
    password_hash   VARCHAR(255)   NOT NULL
    created_at     TIMESTAMP     NOT NULL DEFAULT NOW()
);

CREATE TABLE userRoles (
    user_id         INT            NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    org_id          INT            NOT NULL REFERENCES organizations(org_id) ON DELETE CASCADE,
    role            VARCHAR(20)    NOT NULL CHECK (role IN ('treasurer', 'officer', 'member','advisor')),
    PRIMARY KEY (user_id, org_id)
);

CREATE TABLE organizations (
    org_id          SERIAL PRIMARY KEY,
    name            VARCHAR(100)   NOT NULL UNIQUE,
    description     TEXT,
    starting_balance NUMERIC(12,2)  NOT NULL DEFAULT 0.00,
    current_balance  NUMERIC(12,2)  NOT NULL DEFAULT 0.00,
    created_at       TIMESTAMP      NOT NULL DEFAULT NOW()
);


CREATE TABLE transactions (
    transaction_id  SERIAL PRIMARY KEY,
    org_id          INT            NOT NULL REFERENCES organizations(org_id) ON DELETE CASCADE,
    entered_by       INT           NOT NULL REFERENCES users(user_id) ON DELETE RESTRICT,
    type            VARCHAR(10)    NOT NULL CHECK (type IN ('income', 'expense')),
    description     TEXT,
    amount            NUMERIC(12,2)   NOT NULL CHECK (amount > 0),
    transaction_date DATE          NOT NULL,
    created_at      TIMESTAMP      NOT NULL DEFAULT NOW(),
    is_locked       BOOLEAN        NOT NULL DEFAULT FALSE   -- set TRUE after 24 hrs
);

CREATE TABLE transactionAuditLog (
    log_id          SERIAL        PRIMARY KEY,
    transaction_id  INT           NOT NULL REFERENCES transactions(transaction_id) ON DELETE CASCADE,
    changed_by     INT         NOT NULL REFERENCES users(user_id) ON DELETE RESTRICT,
    action         VARCHAR(10) NOT NULL CHECK (action IN ('INSERT', 'UPDATE', 'DELETE')),
    changed_at      TIMESTAMP      NOT NULL DEFAULT NOW(),

);