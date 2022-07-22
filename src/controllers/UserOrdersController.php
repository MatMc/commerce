<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\commerce\controllers;

use Craft;
use craft\commerce\behaviors\CustomerBehavior;
use craft\elements\User;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Class User Orders Controller
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0
 */
class UserOrdersController extends BaseFrontEndController
{
    /**
     * Get customer's orders
     *
     * @throws BadRequestHttpException
     */
    public function actionGetOrders(): Response
    {
        $this->requireAcceptsJson();

        /** @var User|CustomerBehavior|null $user */
        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            return $this->asFailure(Craft::t('commerce', 'No user authenticated.'));
        }

        $orders = $user->getOrders();

        return $this->asSuccess(data: [
            'orders' => $orders,
        ]);
    }
}
