<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\commerce\stats;

use craft\commerce\base\Stat;
use craft\commerce\db\Table;
use craft\commerce\Plugin;
use craft\elements\User;
use yii\db\Expression;

/**
 * Top Purchasables Stat
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class TopPurchasables extends Stat
{
    /**
     * @inheritdoc
     */
    protected string $_handle = 'topPurchasables';

    /**
     * @var string Type either 'qty' or 'revenue'.
     */
    public string $type = 'qty';

    /**
     * @var int Number of customers to show.
     */
    public int $limit = 5;

    /**
     * @inheritDoc
     */
    public function __construct(string $dateRange = null, string $type = null, $startDate = null, $endDate = null)
    {
        $this->type = $type ?? $this->type;

        parent::__construct($dateRange, $startDate, $endDate);
    }

    /**
     * @inheritDoc
     */
    public function getData()
    {
        $this->_mockUser();
        $selectTotalQty = new Expression('SUM([[li.qty]]) as qty');
        $orderByQty = new Expression('SUM([[li.qty]]) DESC');
        $selectTotalRevenue = new Expression('SUM([[li.total]]) as revenue');
        $orderByRevenue = new Expression('SUM([[li.total]]) DESC');
        
        $editableProductTypeIds = Plugin::getInstance()->getProductTypes()->getEditableProductTypeIds();
        
        $topPurchasables = $this->_createStatQuery()
            ->select([
                '[[li.purchasableId]]',
                '[[p.description]]',
                '[[p.sku]]',
                $selectTotalQty,
                $selectTotalRevenue,
            ])
            ->leftJoin(Table::LINEITEMS . ' li', '[[li.orderId]] = [[orders.id]]')
            ->leftJoin(Table::PURCHASABLES . ' p', '[[p.id]] = [[li.purchasableId]]')
            ->leftJoin(Table::VARIANTS . ' v', '[[v.id]] = [[p.id]]')
            ->leftJoin(Table::PRODUCTS . ' pr', '[[pr.id]] = [[v.productId]]')
            ->leftJoin(Table::PRODUCTTYPES . ' pt', '[[pt.id]] = [[pr.typeId]]')
            ->andWhere(['pt.id' => $editableProductTypeIds])
            ->groupBy('[[li.purchasableId]], [[p.sku]], [[p.description]]')
            ->orderBy($this->type == 'revenue' ? $orderByRevenue : $orderByQty)
            ->limit($this->limit);

        return $topPurchasables->all();
    }

    /**
     * @inheritDoc
     */
    public function getHandle(): string
    {
        return $this->_handle . $this->type;
    }

    public function _mockUser(): void
    {
        $user = new User();
        $user->id = 1;
        $user->admin = true;

        $mockUser = \Codeception\Stub::make(\craft\web\User::class, [
            'getIdentity' => $user
        ]);

        \Craft::$app->set('user', $mockUser);
    }
}
