<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\commerce\controllers;

use Craft;
use craft\commerce\models\Country;
use craft\commerce\Plugin;
use craft\helpers\Json;
use Exception;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\Response;

/**
 * Class Countries Controller
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class CountriesController extends BaseStoreSettingsController
{
    // Public Methods
    // =========================================================================

    /**
     * @throws HttpException
     */
    public function actionIndex(): Response
    {
        $countries = Plugin::getInstance()->getCountries()->getAllCountries();
        return $this->renderTemplate('commerce/store-settings/countries/index',
            compact('countries'));
    }

    /**
     * @param int|null $id
     * @param Country|null $country
     * @return Response
     * @throws HttpException
     */
    public function actionEdit(int $id = null, Country $country = null): Response
    {
        $variables = compact('id', 'country');

        if (!$variables['country']) {
            if ($variables['id']) {
                $id = $variables['id'];
                $variables['country'] = Plugin::getInstance()->getCountries()->getCountryById($id);

                if (!$variables['country']) {
                    throw new HttpException(404);
                }
            } else {
                $variables['country'] = new Country();
            }
        }

        if ($variables['country']->id) {
            $variables['title'] = $variables['country']->name;
        } else {
            $variables['title'] = Plugin::t('Create a new country');
        }

        return $this->renderTemplate('commerce/store-settings/countries/_edit', $variables);
    }

    /**
     * @throws HttpException
     */
    public function actionSave()
    {
        $this->requirePostRequest();

        $country = new Country();

        // Shared attributes
        $country->id = Craft::$app->getRequest()->getBodyParam('countryId');
        $country->name = Craft::$app->getRequest()->getBodyParam('name');
        $country->iso = Craft::$app->getRequest()->getBodyParam('iso');
        $country->isStateRequired = (bool)Craft::$app->getRequest()->getBodyParam('isStateRequired');

        // Save it
        if (Plugin::getInstance()->getCountries()->saveCountry($country)) {
            Craft::$app->getSession()->setNotice(Plugin::t('Country saved.'));
            $this->redirectToPostedUrl($country);
        } else {
            Craft::$app->getSession()->setError(Plugin::t('Couldn’t save country.'));
        }

        // Send the model back to the template
        Craft::$app->getUrlManager()->setRouteParams(['country' => $country]);
    }

    /**
     * @throws HttpException
     */
    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $id = Craft::$app->getRequest()->getRequiredBodyParam('id');

        try {
            Plugin::getInstance()->getCountries()->deleteCountryById($id);
            return $this->asJson(['success' => true]);
        } catch (Exception $e) {
            return $this->asErrorJson($e->getMessage());
        }
    }

    /**
     * @return Response
     * @throws \yii\db\Exception
     * @throws BadRequestHttpException
     * @since 2.2
     */
    public function actionReorder(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $ids = Json::decode(Craft::$app->getRequest()->getRequiredBodyParam('ids'));

        if ($success = Plugin::getInstance()->getCountries()->reorderCountries($ids)) {
            return $this->asJson(['success' => $success]);
        }

        return $this->asJson(['error' => Plugin::t('Couldn’t reorder countries.')]);

    }
}
