<?php

function install_schema(PDO $db): void
{
    $mysql = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
    $id = $mysql ? 'INTEGER PRIMARY KEY AUTO_INCREMENT' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $tail = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
    $tables = [
      "d2_users" => "id $id,name VARCHAR(80) NOT NULL,email VARCHAR(190) NOT NULL UNIQUE,password VARCHAR(255) NOT NULL,role VARCHAR(16) NOT NULL,active INTEGER NOT NULL DEFAULT 1,auth_version INTEGER NOT NULL DEFAULT 1,created_at DATETIME NOT NULL",
      "d2_groups" => "id $id,name VARCHAR(100) NOT NULL,description TEXT NOT NULL,creator_id INTEGER NOT NULL,created_at DATETIME NOT NULL,FOREIGN KEY(creator_id) REFERENCES d2_users(id)",
      "d2_memberships" => "group_id INTEGER NOT NULL,user_id INTEGER NOT NULL,PRIMARY KEY(group_id,user_id),FOREIGN KEY(group_id) REFERENCES d2_groups(id),FOREIGN KEY(user_id) REFERENCES d2_users(id)",
      "d2_challenges" => "id $id,group_id INTEGER NOT NULL,title VARCHAR(140) NOT NULL,description TEXT NOT NULL,points INTEGER NOT NULL,due_date DATE NULL,archived INTEGER NOT NULL DEFAULT 0,created_at DATETIME NOT NULL,FOREIGN KEY(group_id) REFERENCES d2_groups(id)",
      "d2_submissions" => "id $id,challenge_id INTEGER NOT NULL,user_id INTEGER NOT NULL,note TEXT NOT NULL,proof VARCHAR(80) NOT NULL,status VARCHAR(16) NOT NULL DEFAULT 'pending',feedback TEXT NOT NULL,awarded_points INTEGER NOT NULL DEFAULT 0,reviewed_by INTEGER NULL,submitted_at DATETIME NOT NULL,reviewed_at DATETIME NULL,UNIQUE(challenge_id,user_id),FOREIGN KEY(challenge_id) REFERENCES d2_challenges(id),FOREIGN KEY(user_id) REFERENCES d2_users(id),FOREIGN KEY(reviewed_by) REFERENCES d2_users(id)",
      "d2_events" => "id $id,actor_id INTEGER NULL,event VARCHAR(80) NOT NULL,entity VARCHAR(190) NOT NULL,created_at DATETIME NOT NULL,FOREIGN KEY(actor_id) REFERENCES d2_users(id)",
      "d2_attempts" => "bucket VARCHAR(64) PRIMARY KEY,attempts INTEGER NOT NULL,expires_at INTEGER NOT NULL",
      "d2_settings" => "name VARCHAR(80) PRIMARY KEY,value TEXT NOT NULL"
    ];
    // Fresh installation only. Never overwrite or silently adopt preexisting tables.
    foreach ($tables as $name => $columns) {
        $db->exec("CREATE TABLE $name ($columns)$tail");
    }
    $db->exec('CREATE INDEX d2_submissions_status ON d2_submissions(status)');
    $db->exec('CREATE INDEX d2_challenges_group ON d2_challenges(group_id)');
    $stmt = $db->prepare('INSERT INTO d2_settings (name,value) VALUES (?,?)');
    $stmt->execute(['schema_version','1']);
}
