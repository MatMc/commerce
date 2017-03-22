<?php
namespace craft\commerce;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use craft\commerce\plugin\Routes;
use craft\commerce\plugin\Services as CommerceServices;
use craft\commerce\web\twig\Extension;
use craft\elements\User as UserElement;
use craft\enums\LicenseKeyStatus;
use craft\events\RegisterCpAlertsEvent;
use craft\events\RegisterRichTextLinkOptionsEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\fields\RichText;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use craft\services\Sites;
use craft\services\UserPermissions;
use yii\base\Event;
use yii\base\Exception;
use yii\web\User;

class Plugin extends \craft\base\Plugin
{
    // Public Properties
    // =========================================================================


    // Traits
    // =========================================================================

    use CommerceServices;
    use Routes;

    // Constants
    // =========================================================================

    /**
     * @event \yii\base\Event The event that is triggered after the plugin has been initialized
     */
    const EVENT_AFTER_INIT = 'afterInit';

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        $this->_init();
    }

    /**
     * Pre-install checks
     *
     * @return bool
     * @throws Exception
     */
    public function beforeInstall(): bool
    {
        if (version_compare(Craft::$app->getInfo()->version, '3.0', '<')) {
            throw new Exception('Craft Commerce 2 requires Craft CMS 3+ in order to run.');
        }

        if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 70000) {
            Craft::error('Craft Commerce requires PHP 7.0+ in order to run.');

            return false;
        }

        return true;
    }

    /**
     * Cleanup before the plugin uninstalls
     *
     * @return bool
     * @throws Exception
     */
    public function beforeUnInstall(): bool
    {
        $this->_dropAllTables($records);

        Craft::$app->getDb()->createCommand()
            ->delete(
                '{{%elementindexsettings}}',
                ['type' => Order::class])
            ->execute();

        Craft::$app->getDb()->createCommand()
            ->delete(
                '{{%elementindexsettings}}',
                ['type' => Product::class])
            ->execute();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem()
    {
        $iconPath = Plugin::getInstance()->getBasePath().DIRECTORY_SEPARATOR.'icon-mask.svg';

        if (is_file($iconPath)) {
            $iconSvg = file_get_contents($iconPath);
        } else {
            $iconSvg = false;
        }

        $navItems = [
            'label' => Plugin::getInstance()->name,
            'url' => Plugin::getInstance()->id,
            'iconSvg' => $iconSvg,
            'subnav' => []
        ];

        if (Craft::$app->getUser()->checkPermission('commerce-manageOrders')) {
            $navItem['subnav']['orders'] = ['label' => Craft::t('commerce', 'Orders'), 'url' => 'commerce/orders'];
        }

        if (Craft::$app->getUser()->checkPermission('commerce-manageProducts')) {
            $navItem['subnav']['products'] = ['label' => Craft::t('commerce', 'Products'), 'url' => 'commerce/products'];
        }

        if (Craft::$app->getUser()->checkPermission('commerce-managePromotions')) {
            $navItem['subnav']['promotions'] = ['label' => Craft::t('commerce', 'Promotions'), 'url' => 'commerce/promotions'];
        }

        if (Craft::$app->user->identity->admin) {
            $navItem['subnav']['settings'] = ['label' => Craft::t('commerce', 'Settings'), 'url' => 'commerce/settings'];
        }

        return $navItems;
    }

    // Private Methods
    // =========================================================================

    /**
     * Initialize the plugin.
     */
    private function _init()
    {
        $this->_setPluginComponents();
        $this->_registerCpRoutes();
        $this->_addTwigExtensions();
        $this->_prepCpTemplate();
        $this->_registerRichTextLinks();
        $this->_registerPermissions();
        $this->_registerSessionEventListeners();
        $this->_registerCpAlerts();

        // Fire an 'afterInit' event
        $this->trigger(Plugin::EVENT_AFTER_INIT);
    }

    /**
     * Add the twig extension
     */
    private function _addTwigExtensions()
    {
        Craft::$app->view->twig->addExtension(new Extension);
    }

    /**
     * Prepare the control panel templates with CSS, JS, and translations
     */
    private function _prepCpTemplate()
    {
        if (Craft::$app->getRequest()->isCpRequest) {

            $templatesService = Craft::$app->getView();
            $templatesService->registerCssFile('commerce/commerce.css');
            $templatesService->registerJsFile('commerce/js/Commerce.js');
            $templatesService->registerJsFile('commerce/js/CommerceProductIndex.js');
            $templatesService->registerTranslations('commerce',
                [
                    'New {productType} product',
                    'New product',
                    'Update Order Status',
                    'Message',
                    'Status change message',
                    'Update',
                    'Cancel',
                    'First Name',
                    'Last Name',
                    'Address Line 1',
                    'Address Line 2',
                    'City',
                    'Zip Code',
                    'Phone',
                    'Alternative Phone',
                    'Phone (Alt)',
                    'Business Name',
                    'Business Tax ID',
                    'Country',
                    'State',
                    'Update Address',
                    'New',
                    'Edit',
                    'Add Address',
                    'Add',
                    'Update',
                    'No Address'
                ]
            );

            Craft::$app->getView()->hook('commerce.prepCpTemplate', [$this, 'prepCpTemplate']);
        }
    }

    /**
     * Register links to product in the rich text field
     */
    private function _registerRichTextLinks()
    {
        Event::on(RichText::class, RichText::EVENT_REGISTER_LINK_OPTIONS, function(RegisterRichTextLinkOptionsEvent $event) {
            // Include a Product link option if there are any product types that have URLs
            $productSources = [];

            foreach (Plugin::getInstance()->getProductTypes()->getAllProductTypes() as $productType) {
                if ($productType->hasUrls) {
                    $productSources[] = 'productType:'.$productType->id;
                }
            }

            if ($productSources) {
                $event->linkOptions[] = [
                    'optionTitle' => Craft::t('commerce', 'Link to a product'),
                    'elementType' => Product::class,
                    'sources' => $productSources
                ];
            }
        });
    }

    /**
     * Register commerce permissions
     */
    private function _registerPermissions()
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {

            $productTypes = Plugin::getInstance()->getProductTypes()->getAllProductTypes('id');

            $productTypePermissions = [];
            foreach ($productTypes as $id => $productType) {
                $suffix = ':'.$id;
                $productTypePermissions["commerce-manageProductType".$suffix] = ['label' => Craft::t('commerce', 'Manage “{type}” products', ['type' => $productType->name])];
            }

            $event->permissions[] = [
                'commerce-manageProducts' => ['label' => Craft::t('commerce', 'Manage products'), 'nested' => $productTypePermissions],
                'commerce-manageOrders' => ['label' => Craft::t('commerce', 'Manage orders')],
                'commerce-managePromotions' => ['label' => Craft::t('commerce', 'Manage promotions')],
            ];
        });
    }

    /**
     *
     */
    private function _registerSessionEventListeners()
    {
        Event::on(Sites::class, Sites::EVENT_AFTER_SAVE_SITE, [$this->getProductTypes(), 'addLocaleHandler']);
        if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
            Event::on(UserElement::class, UserElement::EVENT_AFTER_SAVE, [$this->getCustomers(), 'saveUserHandler']);
            Event::on(User::class, User::EVENT_AFTER_LOGIN, [$this->getCustomers(), 'loginHandler']);
            Event::on(User::class, User::EVENT_AFTER_LOGOUT, [$this->getCustomers(), 'logoutHandler']);
        }
    }

    /**
     *
     */
    private function _registerCpAlerts()
    {
        Event::on(Cp::class, Cp::EVENT_REGISTER_ALERTS, function(RegisterCpAlertsEvent $event) {

            if (Craft::$app->getRequest()->getFullPath() != 'commerce/settings/registration') {
                $licenseKeyStatus = Craft::$app->getPlugins()->getPluginLicenseKeyStatus('Commerce');

                if ($licenseKeyStatus == LicenseKeyStatus::Unknown) {
                    if (!Craft::$app->canTestEditions) {
                        $message = Craft::t('commerce', 'You haven’t entered your Commerce license key yet.');
                    }
                } else if ($licenseKeyStatus == LicenseKeyStatus::Invalid) {
                    $message = Craft::t('commerce', 'Your Commerce license key is invalid.');
                } else if ($licenseKeyStatus == LicenseKeyStatus::Mismatched) {
                    $message = Craft::t('commerce', 'Your Commerce license key is being used on another Craft install.');
                }

                if (isset($message)) {
                    $message .= ' ';

                    if (Craft::$app->getUser()->isAdmin()) {
                        $message .= '<a class="go" href="'.UrlHelper::cpUrl('commerce/settings/registration').'">'.Craft::t('commerce', 'Resolve').'</a>';
                    } else {
                        $message .= Craft::t('commerce', 'Please notify one of your site’s admins.');
                    }

                    $event->alerts[] = $message;
                }
            }
        });
    }


    private function _dropAllTables()
    {
        // TODO: Drop all Commerce Tables

        // Drop all foreign keys first
//        foreach ($records as $record) {
//            $record->dropForeignKeys();
//        }

        // Then drop the tables
//        foreach ($records as $record) {
//            $record->dropTable();
//        }
    }
}