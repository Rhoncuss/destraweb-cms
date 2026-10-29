<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEngagement extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'slug' => ['type' => 'VARCHAR', 'constraint' => 120],
            'location' => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'primary'],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('menus', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'menu_id' => ['type' => 'INT', 'unsigned' => true],
            'parent_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'custom'],
            'target_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'icon' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'target' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => '_self'],
            'css_class' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['menu_id', 'parent_id', 'sort_order']);
        $this->forge->createTable('menu_items', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'item_id' => ['type' => 'INT', 'unsigned' => true],
            'language_code' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'label' => ['type' => 'VARCHAR', 'constraint' => 190],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['item_id', 'language_code']);
        $this->forge->createTable('menu_item_translations', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'placement' => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'home_slider'],
            'type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'image'],
            'image_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'html' => ['type' => 'LONGTEXT', 'null' => true],
            'link_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'link_target' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => '_self'],
            'starts_at' => ['type' => 'DATETIME', 'null' => true],
            'ends_at' => ['type' => 'DATETIME', 'null' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'impressions' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'clicks' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['placement', 'is_active']);
        $this->forge->createTable('banners', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'banner_id' => ['type' => 'INT', 'unsigned' => true],
            'language_code' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'subtitle' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'cta_label' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'alt' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['banner_id', 'language_code']);
        $this->forge->createTable('banner_translations', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'title' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'content' => ['type' => 'LONGTEXT', 'null' => true],
            'image_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'trigger_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'delay'],
            'trigger_value' => ['type' => 'INT', 'default' => 3],
            'pages' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'all'],
            'content_ids' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'frequency' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'session'],
            'starts_at' => ['type' => 'DATETIME', 'null' => true],
            'ends_at' => ['type' => 'DATETIME', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'views' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('is_active');
        $this->forge->createTable('popups', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'content_id' => ['type' => 'INT', 'unsigned' => true],
            'parent_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'author_name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'author_email' => ['type' => 'VARCHAR', 'constraint' => 190],
            'body' => ['type' => 'TEXT'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'ip_hash' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['content_id', 'status']);
        $this->forge->addKey('parent_id');
        $this->forge->createTable('comments', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'slug' => ['type' => 'VARCHAR', 'constraint' => 120],
            'description' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'fields' => ['type' => 'LONGTEXT', 'null' => true],
            'success_message' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'email_to' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'email_subject' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'captcha' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'submissions_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('forms', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'form_id' => ['type' => 'INT', 'unsigned' => true],
            'data' => ['type' => 'LONGTEXT', 'null' => true],
            'ip_hash' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'locale' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'is_read' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'new'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['form_id', 'is_read']);
        $this->forge->createTable('form_submissions', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 190],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'token_hash' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'locale' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'ip_hash' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'confirmed_at' => ['type' => 'DATETIME', 'null' => true],
            'unsubscribed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('newsletter_subscribers', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'content_id' => ['type' => 'INT', 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'email' => ['type' => 'VARCHAR', 'constraint' => 190],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'message' => ['type' => 'TEXT', 'null' => true],
            'data' => ['type' => 'LONGTEXT', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'new'],
            'ip_hash' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['content_id', 'status']);
        $this->forge->createTable('orders', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'from_path' => ['type' => 'VARCHAR', 'constraint' => 500],
            'to_path' => ['type' => 'VARCHAR', 'constraint' => 500],
            'status_code' => ['type' => 'INT', 'default' => 301],
            'hits' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('from_path');
        $this->forge->createTable('redirects', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'visit_date' => ['type' => 'DATE'],
            'path_hash' => ['type' => 'VARCHAR', 'constraint' => 64],
            'path' => ['type' => 'VARCHAR', 'constraint' => 500],
            'content_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'language_code' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'views' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'visitors' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['visit_date', 'path_hash']);
        $this->forge->addKey('content_id');
        $this->forge->createTable('visits', true);
    }

    public function down(): void
    {
        foreach ([
            'visits', 'redirects', 'orders', 'newsletter_subscribers', 'form_submissions', 'forms',
            'comments', 'popups', 'banner_translations', 'banners', 'menu_item_translations',
            'menu_items', 'menus',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
