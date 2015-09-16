<?php

namespace tests\migrations;

use yii\db\Schema;
use yii\db\Migration;

class TestMigration extends Migration
{
    public function up()
    {
        ob_start();
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        // tree
        if ($this->db->getTableSchema('{{%tree}}', true) !== null) {
            $this->dropTable('{{%tree}}');
        }
        $this->createTable('{{%tree}}', [
            'id'        => Schema::TYPE_PK,
            'parent_id' => Schema::TYPE_INTEGER . ' NULL',
            'sort'      => Schema::TYPE_INTEGER . ' NOT NULL',
            'slug'      => Schema::TYPE_STRING . ' NOT NULL',
        ], $tableOptions);
        $this->createIndex('parent_sort', '{{%tree}}', ['parent_id', 'sort']);

        // update cache (sqlite bug)
        $this->db->getSchema()->getTableSchema('{{%tree}}', true);
        ob_end_clean();
    }
}
