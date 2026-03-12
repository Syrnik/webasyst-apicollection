<?php

declare(strict_types=1);

$model = new waModel();

$model->exec("
    CREATE TABLE IF NOT EXISTS apicollection_environment (
        id              INT(11)      NOT NULL AUTO_INCREMENT,
        contact_id      INT(11)      NOT NULL,
        is_shared       TINYINT(1)   NOT NULL DEFAULT 0,
        name            VARCHAR(255) NOT NULL,
        base_url        VARCHAR(1000) NULL,
        auth_type       ENUM('none','bearer','basic','apikey') NOT NULL DEFAULT 'none',
        auth_data       TEXT         NULL,
        custom_headers  TEXT         NULL,
        sort            INT(11)      NOT NULL DEFAULT 0,
        created         DATETIME     NOT NULL,
        updated         DATETIME     NOT NULL,
        PRIMARY KEY (id),
        KEY contact_id (contact_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$model->exec("
    CREATE TABLE IF NOT EXISTS apicollection_environment_selected (
        contact_id      INT(11)  NOT NULL,
        collection_id   INT(11)  NOT NULL,
        environment_id  INT(11)  NULL,
        PRIMARY KEY (contact_id, collection_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
