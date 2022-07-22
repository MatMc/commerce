<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\commerce\web\twig;

use craft\commerce\helpers\Currency;
use craft\commerce\helpers\PaymentForm;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class CommerceTwigExtension
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class Extension extends AbstractExtension
{
    public function getName(): string
    {
        return 'Craft Commerce Twig Extension';
    }

    /**
     * @inheritdoc
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('commerceCurrency', [Currency::class, 'formatAsCurrency']),
            new TwigFilter('commercePaymentFormNamespace', [PaymentForm::class, 'getPaymentFormNamespace']),
        ];
    }
}
