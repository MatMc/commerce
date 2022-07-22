<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\commerce\gql\types;

use Craft;
use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\DateTime;
use GraphQL\Type\Definition\Type;

/**
 * Class SaleType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.10
 */
class SaleType extends ObjectType
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'Sale';
    }

    public static function getType(): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        return GqlEntityRegistry::createEntity(self::getName(), new self([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => '',
        ]));
    }

    public static function getFieldDefinitions(): array
    {
        return Craft::$app->getGql()->prepareFieldDefinitions([
            'name' => [
                'name' => 'name',
                'type' => Type::string(),
                'description' => 'The name of the sale as described in the control panel.',
            ],
            'description' => [
                'name' => 'description',
                'type' => Type::string(),
                'description' => 'Description of the sale.',
            ],
            'apply' => [
                'name' => 'apply',
                'type' => Type::string(),
                'description' => 'How the sale should be applied.',
            ],
            'applyAmount' => [
                'name' => 'applyAmount',
                'type' => Type::float(),
                'description' => 'The amount applied used by the apply option.',
            ],
            'applyAmountAsPercent' => [
                'name' => 'applyAmountAsPercent',
                'type' => Type::string(),
                'description' => 'The amount applied used by the apply option.',
            ],
            'applyAmountAsFlat' => [
                'name' => 'applyAmountAsFlat',
                'type' => Type::float(),
                'description' => 'The amount applied used by the apply option.',
            ],
            'dateFrom' => [
                'name' => 'dateFrom',
                'type' => DateTime::getType(),
                'description' => 'Start date of the sale.',
            ],
            'dateTo' => [
                'name' => 'dateTo',
                'type' => DateTime::getType(),
                'description' => 'Start date of the sale.',
            ],
        ], self::getName());
    }
}
