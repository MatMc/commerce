<?php

namespace craft\commerce\migrations;

use craft\db\Migration;

/**
 * m210922_133729_add_discount_order_condition_builder migration.
 */
class m210922_133729_add_discount_order_condition_builder extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%commerce_discounts}}', 'orderCondition')) {
            $this->addColumn('{{%commerce_discounts}}', 'orderCondition', $this->text()->after('description'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m210922_133729_add_discount_order_condition_builder cannot be reverted.\n";
        return false;
    }
}
