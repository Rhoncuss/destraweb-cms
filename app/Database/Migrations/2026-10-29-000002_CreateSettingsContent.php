<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSettingsContent extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'setting_group' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'general'],
            'setting_key' => ['type' => 'VARCHAR', 'constraint' => 190],
            'setting_value' => ['type' => 'LONGTEXT', 'null' => true],
            'setting_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'text'],
            'is_encrypted' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('setting_key');
        $this->forge->addKey('setting_group');
        $this->forge->createTable('settings', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 10],
            'name' => ['type' => 'VARCHAR', 'constraint' => 80],
            'native_name' => ['type' => 'VARCHAR', 'constraint' => 80],
            'direction' => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'ltr'],
            'flag' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'is_default' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('languages', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'language_code' => ['type' => 'VARCHAR', 'constraint' => 10],
            'group_name' => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'general'],
            'trans_key' => ['type' => 'VARCHAR', 'constraint' => 190],
            'trans_value' => ['type' => 'LONGTEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['language_code', 'group_name', 'trans_key']);
        $this->forge->createTable('translations', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'slug' => ['type' => 'VARCHAR', 'constraint' => 120],
            'icon' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'description' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'post'],
            'template' => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'default'],
            'visibility' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'public'],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'settings' => ['type' => 'LONGTEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('sections', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'section_id' => ['type' => 'INT', 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'field_key' => ['type' => 'VARCHAR', 'constraint' => 80],
            'type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'text'],
            'options' => ['type' => 'LONGTEXT', 'null' => true],
            'default_value' => ['type' => 'TEXT', 'null' => true],
            'is_required' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['section_id', 'field_key']);
        $this->forge->createTable('section_fields', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'parent_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'slug' => ['type' => 'VARCHAR', 'constraint' => 120],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('parent_id');
        $this->forge->createTable('media_folders', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'folder_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'filename' => ['type' => 'VARCHAR', 'constraint' => 190],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'mime_type' => ['type' => 'VARCHAR', 'constraint' => 120],
            'extension' => ['type' => 'VARCHAR', 'constraint' => 20],
            'size' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'width' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'height' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'alt' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'caption' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'hash' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'path' => ['type' => 'VARCHAR', 'constraint' => 255],
            'variants' => ['type' => 'LONGTEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('folder_id');
        $this->forge->addKey('mime_type');
        $this->forge->addKey('user_id');
        $this->forge->createTable('media', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'section_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'post'],
            'author_id' => ['type' => 'INT', 'unsigned' => true],
            'parent_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'template' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'format' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'standard'],
            'featured_image_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'icon' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'sticky' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'allow_comments' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'order_form' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'published_at' => ['type' => 'DATETIME', 'null' => true],
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
            'views' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['section_id', 'status']);
        $this->forge->addKey(['type', 'status']);
        $this->forge->addKey('author_id');
        $this->forge->addKey('published_at');
        $this->forge->addKey('deleted_at');
        $this->forge->createTable('contents', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'content_id' => ['type' => 'INT', 'unsigned' => true],
            'language_code' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'slug' => ['type' => 'VARCHAR', 'constraint' => 190],
            'excerpt' => ['type' => 'TEXT', 'null' => true],
            'body' => ['type' => 'LONGTEXT', 'null' => true],
            'seo_title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'seo_description' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'seo_keywords' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'canonical_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'og_image_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'custom_fields' => ['type' => 'LONGTEXT', 'null' => true],
            'reading_time' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['content_id', 'language_code']);
        $this->forge->addUniqueKey(['language_code', 'slug']);
        $this->forge->addKey('title');
        $this->forge->createTable('content_translations', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'section_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'parent_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'icon' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'color' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'image_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['section_id', 'parent_id']);
        $this->forge->createTable('categories', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'category_id' => ['type' => 'INT', 'unsigned' => true],
            'language_code' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'name' => ['type' => 'VARCHAR', 'constraint' => 190],
            'slug' => ['type' => 'VARCHAR', 'constraint' => 190],
            'description' => ['type' => 'TEXT', 'null' => true],
            'seo_title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'seo_description' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['category_id', 'language_code']);
        $this->forge->addUniqueKey(['language_code', 'slug']);
        $this->forge->createTable('category_translations', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'color' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tags', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tag_id' => ['type' => 'INT', 'unsigned' => true],
            'language_code' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'slug' => ['type' => 'VARCHAR', 'constraint' => 190],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tag_id', 'language_code']);
        $this->forge->addUniqueKey(['language_code', 'slug']);
        $this->forge->createTable('tag_translations', true);

        $this->forge->addField([
            'content_id' => ['type' => 'INT', 'unsigned' => true],
            'category_id' => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addUniqueKey(['content_id', 'category_id']);
        $this->forge->createTable('content_categories', true);

        $this->forge->addField([
            'content_id' => ['type' => 'INT', 'unsigned' => true],
            'tag_id' => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addUniqueKey(['content_id', 'tag_id']);
        $this->forge->createTable('content_tags', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'content_id' => ['type' => 'INT', 'unsigned' => true],
            'media_id' => ['type' => 'INT', 'unsigned' => true],
            'role' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'gallery'],
            'sort_order' => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['content_id', 'role']);
        $this->forge->createTable('content_media', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'content_id' => ['type' => 'INT', 'unsigned' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'address' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'lat' => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'lng' => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'zoom' => ['type' => 'INT', 'default' => 14],
            'sort_order' => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('content_id');
        $this->forge->createTable('content_maps', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'content_id' => ['type' => 'INT', 'unsigned' => true],
            'related_id' => ['type' => 'INT', 'unsigned' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['content_id', 'related_id']);
        $this->forge->createTable('content_related', true);
    }

    public function down(): void
    {
        foreach ([
            'content_related', 'content_maps', 'content_media', 'content_tags', 'content_categories',
            'tag_translations', 'tags', 'category_translations', 'categories', 'content_translations',
            'contents', 'media', 'media_folders', 'section_fields', 'sections', 'translations',
            'languages', 'settings',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
