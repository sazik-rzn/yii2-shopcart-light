<?php

use yii\db\Migration;

class m170418_095321_light extends Migration
{
    public function up()
    {
        $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

        $baseMap = [
            'ShopCartPosition'=>[
                'id' => $this->primaryKey()->comment('ID of this position'),
                'model_id' => $this->integer()->notNull()->comment('model ID of this position'),
                'count' => $this->integer()->notNull()->comment('Count'),
                'price_per_piece'=>  $this->integer()->notNull()->comment('Price per piece'),
                'user_id'=>  $this->integer()->comment('User ID'),
                'cookie_key'=>  $this->string()->comment('Cookie key for this user'),
                'created_at' => $this->integer()->notNull()->comment('Created'),
                'updated_at' => $this->integer()->notNull()->comment('Updated')
            ],            
        ];

        foreach ($baseMap as $name => $table) {
            $this->createTable($name, $table, $tableOptions);
        }
    }

    public function down()
    {
        echo "m170418_095321_light cannot be reverted.\n";

        return false;
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
