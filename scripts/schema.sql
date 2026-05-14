CREATE DATABASE IF NOT EXISTS ml_branch_asset_depreciation;
USE ml_branch_asset_depreciation;

CREATE TABLE IF NOT EXISTS users (
    id                    INT           NOT NULL PRIMARY KEY,
    first_name            VARCHAR(100)  NOT NULL,
    middle_name           VARCHAR(100)  DEFAULT NULL,
    last_name             VARCHAR(100)  NOT NULL,
    username              VARCHAR(100)  NOT NULL UNIQUE,
    password_hash         VARCHAR(255)  NOT NULL,
    user_type             ENUM('ADMIN','USER') NOT NULL DEFAULT 'USER',
    status                ENUM('ACTIVE','RESTRICTED') NOT NULL DEFAULT 'ACTIVE',
    last_login            DATETIME      DEFAULT NULL,
    password_changed_at   DATETIME      DEFAULT NULL,
    created_at            DATETIME      DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (id, first_name, last_name, username, password_hash, user_type, status)
VALUES (
    1,
    'Admin',
    'Admin',
    'admin',
    '$argon2id$v=19$m=16,t=2,p=1$d3IyMzVyMjM0$UefQu8QDcpkppTCu8s50JQ',
    'ADMIN',
    'ACTIVE'
);

CREATE TABLE IF NOT EXISTS gl_codes (
    gl_code       VARCHAR(20)              NOT NULL PRIMARY KEY,
    description   VARCHAR(255)             NOT NULL,
    account_type  ENUM('DEBIT', 'CREDIT')  NOT NULL
);

CREATE TABLE IF NOT EXISTS expense_types (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    expense_name   VARCHAR(255) NOT NULL,
    category_type  ENUM('MAINTENANCE_REPAIR', 'INVENTORY_ITEM', 'JOB_ORDER') NOT NULL,
    policy_months  INT NOT NULL
);

CREATE TABLE IF NOT EXISTS asset_groups (
    id                INT AUTO_INCREMENT    NOT NULL PRIMARY KEY,
    group_name        VARCHAR(150)          NOT NULL,
    expense_type_id   INT                   NOT NULL,
    actual_months     INT                   NOT NULL,

    asset_gl_code     VARCHAR(20)           NOT NULL,
    asset_gl_type     ENUM('DEBIT', 'CREDIT') NOT NULL DEFAULT 'CREDIT',

    expense_gl_code   VARCHAR(20)           NOT NULL,
    expense_gl_type   ENUM('DEBIT', 'CREDIT') NOT NULL DEFAULT 'DEBIT',

    CONSTRAINT fk_ag_expense_type
        FOREIGN KEY (expense_type_id)
        REFERENCES expense_types (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS assets (
    id                    INT AUTO_INCREMENT    NOT NULL PRIMARY KEY,
    system_asset_code     VARCHAR(150)          UNIQUE,
    reference_no          VARCHAR(100)          NULL DEFAULT NULL,

    main_zone_code        VARCHAR(20),
    zone_code             VARCHAR(20),
    region_code           VARCHAR(20),
    cost_center_code      VARCHAR(20),
    branch_name           VARCHAR(150),

    bos_branch_code       VARCHAR(50)           NULL,
    kpx_branch_id         VARCHAR(50)           NULL,
    corporate_name        VARCHAR(150)          NULL,

    asset_name            VARCHAR(255)          NOT NULL,
    asset_group_id        INT                   NULL,
    months                INT                   NOT NULL,

    description           VARCHAR(255)          NOT NULL,
    serial_number         VARCHAR(150)          NULL,
    item_code             VARCHAR(100)          NULL DEFAULT NULL,
    quantity              INT                   NOT NULL DEFAULT 1,
    property_type         ENUM('PURCHASED','LEASE','LEASEHOLD','MAINTENANCE') NOT NULL DEFAULT 'PURCHASED',

    date_received           DATE,
    depreciation_start_date DATE,
    depreciation_end_date   DATE,
    retirement_date         DATE,
    acquisition_cost        DECIMAL(15,2)       NOT NULL DEFAULT 0.00,
    monthly_depreciation    DECIMAL(15,2)       NOT NULL DEFAULT 0.00,

    depreciation_on         ENUM('FIRST_DAY','LAST_DAY','SPECIFIC_DATE') NOT NULL DEFAULT 'LAST_DAY',
    depreciation_day        TINYINT             NULL DEFAULT 1,
    status                  ENUM('ACTIVE','SOLD','DISPOSED','INACTIVE') NOT NULL DEFAULT 'ACTIVE',

    source_channel          VARCHAR(50)         NULL DEFAULT NULL COMMENT 'e.g., ISSUANCE, MANUAL, MIGRATION',
    source_issuance_no      VARCHAR(50)         NULL DEFAULT NULL,
    uom                     VARCHAR(20)         NULL DEFAULT NULL,
    unit_cost               DECIMAL(15,2)       NULL DEFAULT NULL,
    source_product_category VARCHAR(150)        NULL DEFAULT NULL,
    remarks                 VARCHAR(500)        NULL DEFAULT NULL,
    is_depreciable          BOOLEAN             NOT NULL DEFAULT TRUE,

    created_by              INT                 NULL,
    created_at              DATETIME            DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME            ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_assets_group_id
        FOREIGN KEY (asset_group_id) REFERENCES asset_groups(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_assets_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    INDEX idx_location_status (
        main_zone_code, zone_code, region_code, cost_center_code,
        bos_branch_code, kpx_branch_id, status
    ),
    INDEX idx_depreciation_window (depreciation_start_date, depreciation_end_date),
    INDEX idx_asset_group_id (asset_group_id),
    INDEX idx_asset_created (created_at DESC),
    INDEX idx_asset_list_filters (branch_name, asset_group_id, status),
    INDEX idx_assets_source_issuance (source_issuance_no),
    INDEX idx_assets_source_channel (source_channel),
    INDEX idx_assets_is_depreciable (is_depreciable)
);

CREATE TABLE IF NOT EXISTS running_depreciation (
    id                       INT AUTO_INCREMENT    NOT NULL PRIMARY KEY,
    asset_id                 INT                   NOT NULL UNIQUE,
    periods_elapsed          SMALLINT              NOT NULL DEFAULT 0,
    periods_remaining        SMALLINT              NOT NULL DEFAULT 0,
    accumulated_depreciation DECIMAL(15,2)         NOT NULL DEFAULT 0.00,
    book_value               DECIMAL(15,2)         NOT NULL DEFAULT 0.00,
    last_depreciation_date   DATE                  NULL DEFAULT NULL,
    is_fully_depreciated     BOOLEAN               DEFAULT FALSE,
    fully_depreciated_at     DATE                  NULL DEFAULT NULL,

    created_at               DATETIME              DEFAULT CURRENT_TIMESTAMP,
    updated_at               DATETIME              ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_running_dep_asset_id
        FOREIGN KEY (asset_id) REFERENCES assets(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    INDEX idx_dep_status (is_fully_depreciated, last_depreciation_date)
);

CREATE TABLE IF NOT EXISTS depreciation_ledger (
    id                          INT AUTO_INCREMENT      NOT NULL PRIMARY KEY,

    asset_id                    INT                     NULL,
    system_asset_code           VARCHAR(150)            NOT NULL,

    main_zone_code              VARCHAR(20)             NULL,
    zone_code                   VARCHAR(20)             NULL,
    region_code                 VARCHAR(100)            NULL,
    cost_center_code            VARCHAR(20)             NULL,
    branch_name                 VARCHAR(150)            NULL,

    bos_branch_code             VARCHAR(50)             NULL,
    kpx_branch_id               VARCHAR(50)             NULL,
    corporate_name              VARCHAR(150)            NULL,

    asset_name                  VARCHAR(255)            NOT NULL,
    asset_group_id              INT                     NULL,
    group_name                  VARCHAR(150)            NULL,
    months                      INT                     NOT NULL,
    property_type               ENUM('PURCHASED','LEASE','LEASEHOLD','MAINTENANCE') NOT NULL,

    acquisition_cost            DECIMAL(15,2)           NOT NULL,
    monthly_depreciation        DECIMAL(15,2)           NOT NULL,

    period_date                 DATE                    NOT NULL,
    period_month                TINYINT                 NOT NULL,
    period_year                 SMALLINT                NOT NULL,

    periods_elapsed             SMALLINT                NOT NULL,
    periods_remaining           SMALLINT                NOT NULL,
    period_depreciation_expense DECIMAL(15,2)           NOT NULL,
    accumulated_depreciation    DECIMAL(15,2)           NOT NULL,
    book_value                  DECIMAL(15,2)           NOT NULL,

    gl_a_code                   VARCHAR(20)             NOT NULL,
    gl_a_type                   ENUM('DEBIT','CREDIT')  NOT NULL,
    gl_a_amount                 DECIMAL(15,2)           NOT NULL,

    gl_b_code                   VARCHAR(20)             NOT NULL,
    gl_b_type                   ENUM('DEBIT','CREDIT')  NOT NULL,
    gl_b_amount                 DECIMAL(15,2)           NOT NULL,

    CONSTRAINT fk_ledger_asset
        FOREIGN KEY (asset_id)
        REFERENCES assets (id)
        ON DELETE SET NULL,

    UNIQUE KEY uq_asset_period (system_asset_code, period_date),

    INDEX idx_ledger_asset_id    (asset_id),
    INDEX idx_ledger_period      (period_year, period_month),
    INDEX idx_ledger_period_date (period_date),
    INDEX idx_ledger_zone        (main_zone_code, zone_code, region_code)
);

CREATE TABLE IF NOT EXISTS issuance_staging (
    id                      INT AUTO_INCREMENT PRIMARY KEY,

    date_issued             DATETIME        NOT NULL,
    issuance_number         VARCHAR(50)     NOT NULL,
    item_code               VARCHAR(100)    NULL,
    item_description        VARCHAR(500)    NOT NULL,
    quantity                INT             NOT NULL DEFAULT 1,
    uom                     VARCHAR(20)     NULL,
    cost_center_raw         VARCHAR(150)    NOT NULL,
    unit_cost               DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    total_amount            DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    description_remarks     VARCHAR(500)    NULL,
    product_category        VARCHAR(150)    NOT NULL,
    zone                    VARCHAR(20)     NULL,
    region                  VARCHAR(100)    NULL,
    branch_name             VARCHAR(150)    NULL,
    source_status           VARCHAR(50)     NULL DEFAULT 'done',

    transfer_status         ENUM('PENDING','TRANSFERRED','REJECTED') NOT NULL DEFAULT 'PENDING',
    transferred_asset_id    INT             NULL,
    rejection_reason        VARCHAR(500)    NULL,
    transferred_at          DATETIME        NULL,

    created_at              DATETIME        DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_issuance_number (issuance_number),
    INDEX idx_transfer_status (transfer_status),
    INDEX idx_date_issued (date_issued),
    INDEX idx_product_category (product_category),
    INDEX idx_zone (zone)
);

ALTER TABLE issuance_staging DROP INDEX uq_issuance_number;