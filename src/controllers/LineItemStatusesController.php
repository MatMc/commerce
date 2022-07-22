<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\commerce\controllers;

use Craft;
use craft\commerce\helpers\DebugPanel;
use craft\commerce\models\LineItemStatus;
use craft\commerce\Plugin;
use craft\errors\MissingComponentException;
use craft\helpers\Json;
use Throwable;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * Class  Line Item Status Controller
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class LineItemStatusesController extends BaseAdminController
{
    public function actionIndex(): Response
    {
        $lineItemStatuses = Plugin::getInstance()->getLineItemStatuses()->getAllLineItemStatuses();

        return $this->renderTemplate('commerce/settings/lineitemstatuses/index', compact('lineItemStatuses'));
    }

    /**
     * @param int|null $id
     * @param LineItemStatus|null $lineItemStatus
     * @throws HttpException
     */
    public function actionEdit(int $id = null, LineItemStatus $lineItemStatus = null): Response
    {
        $variables = compact('id', 'lineItemStatus');

        if (!$variables['lineItemStatus']) {
            if ($variables['id']) {
                $variables['lineItemStatus'] = Plugin::getInstance()->getLineItemStatuses()->getLineItemStatusById($variables['id']);

                if (!$variables['lineItemStatus']) {
                    throw new HttpException(404);
                }
            } else {
                $variables['lineItemStatus'] = new LineItemStatus();
            }
        }

        DebugPanel::prependOrAppendModelTab(model: $variables['lineItemStatus'], prepend: true);

        if ($variables['lineItemStatus']->id) {
            $variables['title'] = $variables['lineItemStatus']->name;
        } else {
            $variables['title'] = Craft::t('commerce', 'Create a new line item status');
        }

        return $this->renderTemplate('commerce/settings/lineitemstatuses/_edit', $variables);
    }

    /**
     * @throws BadRequestHttpException
     * @throws ErrorException
     * @throws Exception
     * @throws MissingComponentException
     */
    public function actionSave(): void
    {
        $this->requirePostRequest();

        $id = $this->request->getBodyParam('id');
        $lineItemStatus = $id ? Plugin::getInstance()->getLineItemStatuses()->getLineItemStatusById($id) : false;

        if (!$lineItemStatus) {
            $lineItemStatus = new LineItemStatus();
        }

        $lineItemStatus->name = $this->request->getBodyParam('name');
        $lineItemStatus->handle = $this->request->getBodyParam('handle');
        $lineItemStatus->color = $this->request->getBodyParam('color');
        $lineItemStatus->default = (bool)$this->request->getBodyParam('default');

        // Save it
        if (Plugin::getInstance()->getLineItemStatuses()->saveLineItemStatus($lineItemStatus)) {
            $this->setSuccessFlash(Craft::t('commerce', 'Order status saved.'));
            $this->redirectToPostedUrl($lineItemStatus);
        } else {
            $this->setFailFlash(Craft::t('commerce', 'Couldn’t save line item status.'));
        }

        Craft::$app->getUrlManager()->setRouteParams(compact('lineItemStatus'));
    }

    /**
     * @throws BadRequestHttpException
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function actionReorder(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $ids = Json::decode($this->request->getRequiredBodyParam('ids'));
        if (!Plugin::getInstance()->getLineItemStatuses()->reorderLineItemStatuses($ids)) {
            return $this->asFailure(Craft::t('commerce', 'Couldn’t reorder  Line Item Statuses.'));
        }

        return $this->asSuccess();
    }

    /**
     * @throws BadRequestHttpException
     * @throws Throwable
     */
    public function actionArchive(): ?Response
    {
        $this->requireAcceptsJson();

        $lineItemStatusId = $this->request->getRequiredParam('id');

        if (!Plugin::getInstance()->getLineItemStatuses()->archiveLineItemStatusById((int)$lineItemStatusId)) {
            return $this->asFailure(Craft::t('commerce', 'Couldn’t archive Line Item Status.'));
        }

        return $this->asSuccess();
    }
}
