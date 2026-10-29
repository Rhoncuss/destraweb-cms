<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCoreAuth extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'username' => ['type' => 'VARCHAR', 'constraint' => 60],
            'email' => ['type' => 'VARCHAR', 'constraint' => 190],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'role_id' => ['type' => 'INT', 'unsigned' => true, 'default' => 4],
            'avatar' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'locale' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'timezone' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'bio' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
            'last_login_ip' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'remember_token' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'remember_expires_at' => ['type' => 'DATETIME', 'null' => true],
            'reset_token' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'reset_expires_at' => ['type' => 'DATETIME', 'null' => true],
            'email_verified_at' => ['type' => 'DATETIME', 'null' => true],
            'meta' => ['type' => 'LONGTEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->addUniqueKey('username');
        $this->forge->addKey('role_id');
        $this->forge->addKey('status');
        $this->forge->createTable('users', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'slug' => ['type' => 'VARCHAR', 'constraint' => 60],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_system' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('roles', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'slug' => ['type' => 'VARCHAR', 'constraint' => 80],
            'group_name' => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'general'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('permissions', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'role_id' => ['type' => 'INT', 'unsigned' => true],
            'permission_id' => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['role_id', 'permission_id']);
        $this->forge->createTable('role_permissions', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 190],
            'ip' => ['type' => 'VARCHAR', 'constraint' => 64],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'success' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['email', 'created_at']);
        $this->forge->addKey(['ip', 'created_at']);
        $this->forge->createTable('login_attempts', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'action' => ['type' => 'VARCHAR', 'constraint' => 80],
            'module' => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'general'],
            'description' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'subject_type' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'subject_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'ip' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'meta' => ['type' => 'LONGTEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'created_at']);
        $this->forge->addKey('module');
        $this->forge->createTable('audit_logs', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'token_hash' => ['type' => 'VARCHAR', 'constraint' => 128],
            'prefix' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'dst_'],
            'abilities' => ['type' => 'TEXT', 'null' => true],
            'last_used_at' => ['type' => 'DATETIME', 'null' => true],
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
            'revoked_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token_hash');
        $this->forge->addKey('user_id');
        $this->forge->createTable('api_tokens', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'provider' => ['type' => 'VARCHAR', 'constraint' => 30],
            'provider_user_id' => ['type' => 'VARCHAR', 'constraint' => 190],
            'email' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['provider', 'provider_user_id']);
        $this->forge->addUniqueKey(['user_id', 'provider']);
        $this->forge->createTable('social_logins', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'provider' => ['type' => 'VARCHAR', 'constraint' => 30],
            'client_id' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'client_secret' => ['type' => 'TEXT', 'null' => true],
            'redirect_uri' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'scopes' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('provider');
        $this->forge->createTable('oauth_providers', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'info'],
            'title' => ['type' => 'VARCHAR', 'constraint' => 190],
            'message' => ['type' => 'TEXT', 'null' => true],
            'url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'icon' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'is_read' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'is_read']);
        $this->forge->createTable('notifications', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'slug' => ['type' => 'VARCHAR', 'constraint' => 60],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'version' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => '1.0.0'],
            'description' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'author' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'settings' => ['type' => 'LONGTEXT', 'null' => true],
            'installed_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('plugins', true);
    }

    public function down(): void
    {
        foreach ([
            'plugins', 'notifications', 'oauth_providers', 'social_logins', 'api_tokens',
            'audit_logs', 'login_attempts', 'role_permissions', 'permissions', 'roles', 'users',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
