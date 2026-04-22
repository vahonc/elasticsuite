<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Index\Indices;

use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCore\Index\Mapping\Field;

/**
 * Test case for field configuration merging and type persistence.
 *
 * Regression test for ensuring that XML-provided "type" precedence is respected
 * when merging static field configuration with dynamically generated fields.
 *
 * This test ensures that:
 * - The fix for PHP operator precedence properly prepares the config array.
 * - The Field object (which is immutable) correctly reflects the overridden type after merge.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 */
class ConfigFieldTypeMergeTest extends TestCase
{
    /**
     * Ensures that XML-provided type is included in merged config
     * and correctly overrides the dynamically assigned field type.
     */
    public function testXmlTypeIsPreservedDuringFieldConfigMerge(): void
    {
        // Simulate a dynamically generated field (e.g., url_key as text).
        $fieldName = 'url_key';
        $field = new Field($fieldName, 'text');

        // Simulate XML field configuration with both fieldConfig and type override.
        $fieldConfigFromXml = [
            'fieldConfig' => ['is_filterable' => '1'],
            'type'        => 'keyword',
        ];

        /**
         * Execute the production logic.
         * We use parentheses to ensure correct operator precedence: (?? []) + (...).
         */
        // phpcs:ignore Generic.Files.LineLength
        $config = ($fieldConfigFromXml['fieldConfig'] ?? []) + (isset($fieldConfigFromXml['type']) ? ['type' => $fieldConfigFromXml['type']] : []);

        // Assertions for the config array itself.
        $this->assertArrayHasKey('type', $config, 'Config array must contain the "type" key.');
        $this->assertSame('keyword', $config['type'], 'Config array must have the XML-provided type.');

        /**
         * Apply config to field.
         * IMPORTANT: Field::mergeConfig returns a NEW object (immutable pattern).
         */
        $field = $field->mergeConfig($config);

        // Final state assertions.
        $this->assertSame(
            'keyword',
            $field->getType(),
            'The resulting Field object must use the XML-provided type override.'
        );

        $this->assertSame(
            '1',
            $field->getConfig()['is_filterable'] ?? null,
            'The additional fieldConfig values must be preserved.'
        );
    }
}
