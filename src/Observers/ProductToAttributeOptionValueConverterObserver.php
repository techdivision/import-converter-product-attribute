<?php

/**
 * TechDivision\Import\Converter\Product\Attribute\Observers\ProductToAttributeOptionValueConverterObserver
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Tim Wagner <t.wagner@techdivision.com>
 * @copyright 2019 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/import-converter-product-attribute
 * @link      http://www.techdivision.com
 */

namespace TechDivision\Import\Converter\Product\Attribute\Observers;

use TechDivision\Import\Utils\StoreViewCodes;
use TechDivision\Import\Utils\FrontendInputTypes;
use TechDivision\Import\Attribute\Utils\ColumnKeys;
use TechDivision\Import\Attribute\Utils\MemberNames;
use TechDivision\Import\Product\Utils\ConfigurationKeys;
use TechDivision\Import\Observers\StateDetectorInterface;
use TechDivision\Import\Services\ImportProcessorInterface;
use TechDivision\Import\Converter\Observers\AbstractConverterObserver;
use TechDivision\Import\Attribute\Callbacks\SwatchTypeLoaderInterface;
use TechDivision\Import\Attribute\Services\AttributeBunchProcessorInterface;

/**
 * Observer that extracts the missing attribute option values from a product CSV.
 *
 * @author    Tim Wagner <t.wagner@techdivision.com>
 * @copyright 2019 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/import-converter-product-attribute
 * @link      http://www.techdivision.com
 */
class ProductToAttributeOptionValueConverterObserver extends AbstractConverterObserver
{

    /**
     * The artefact type.
     *
     * @var string
     */
    const ARTEFACT_TYPE = 'option-import';

    /**
     * The import processor instance.
     *
     * @var \TechDivision\Import\Services\ImportProcessorInterface
     */
    protected $importProcessor;

    /**
     * The attribute bunch processor instance.
     *
     * @var \TechDivision\Import\Attribute\Services\AttributeBunchProcessorInterface
     */
    protected $attributeBunchProcessor;

    /**
     * The swatch type loader instance.
     *
     * @var \TechDivision\Import\Attribute\Callbacks\SwatchTypeLoaderInterface
     */
    protected $swatchTypeLoader;

    /**
     * The array with the column keys that has to be cleaned up when their values are empty.
     *
     * @var array
     */
    protected $cleanUpEmptyColumnKeys;

    /**
     * Initialize the observer with the passed product bunch processor instance.
     *
     * @param \TechDivision\Import\Services\ImportProcessorInterface                   $importProcessor         The product bunch processor instance
     * @param \TechDivision\Import\Attribute\Services\AttributeBunchProcessorInterface $attributeBunchProcessor The attribute bunch processor instance
     * @param \TechDivision\Import\Attribute\Callbacks\SwatchTypeLoaderInterface       $swatchTypeLoader        The swatch type loader instance
     * @param \TechDivision\Import\Observers\StateDetectorInterface|null               $stateDetector           The state detector instance to use
     */
    public function __construct(
        ImportProcessorInterface $importProcessor,
        AttributeBunchProcessorInterface $attributeBunchProcessor,
        SwatchTypeLoaderInterface $swatchTypeLoader,
        StateDetectorInterface $stateDetector = null
    ) {

        // initialize the swatch type loader and the processor instances
        $this->importProcessor = $importProcessor;
        $this->swatchTypeLoader = $swatchTypeLoader;
        $this->attributeBunchProcessor = $attributeBunchProcessor;

        // pass the state detector to the parent method
        parent::__construct($stateDetector);
    }

    /**
     * @return string
     */
    public function getEmptyAttributeValueConstant()
    {
        return $this->getSubject()->getConfiguration()->getConfiguration()->getEmptyAttributeValueConstant();
    }

    /**
     * Remove all the empty values from the row and return the cleared row.
     *
     * @return array The cleared row
     */
    protected function clearRow()
    {

        // query whether or not the column keys has been initialized
        if ($this->cleanUpEmptyColumnKeys === null) {
            // initialize the array with the column keys that has to be cleaned-up
            $this->cleanUpEmptyColumnKeys = array();

            // query whether or not column names that has to be cleaned up have been configured
            if ($this->getSubject()->getConfiguration()->hasParam(ConfigurationKeys::CLEAN_UP_EMPTY_COLUMNS)) {
                // if yes, load the column names
                $cleanUpEmptyColumns = $this->getSubject()->getCleanUpColumns();

                // translate the column names into column keys
                foreach ($cleanUpEmptyColumns as $cleanUpEmptyColumn) {
                    if ($this->hasHeader($cleanUpEmptyColumn)) {
                        $this->cleanUpEmptyColumnKeys[] = $this->getHeader($cleanUpEmptyColumn);
                    }
                }
            }
        }

        $emptyValueDefinition = $this->getEmptyAttributeValueConstant();
        // load the header keys
        $headers = in_array($emptyValueDefinition, $this->row, true) ? array_flip($this->getHeaders()) : [];
        // remove all the empty values from the row, expected the columns has to be cleaned-up
        foreach ($this->row as $key => $value) {
            // query whether or not to cleanup complete attribute
            if ($value === $emptyValueDefinition) {
                $this->cleanUpEmptyColumnKeys[$headers[$key]] = $key;
                $this->row[$key] = '';
            }
            // query whether or not the value is empty AND the column has NOT to be cleaned-up
            if (($value === null || $value === '') && in_array($key, $this->cleanUpEmptyColumnKeys) === false) {
                unset($this->row[$key]);
            }
        }

        // finally return the clean row
        return $this->row;
    }

    /**
     * Process the observer's business logic.
     *
     * @return void
     */
    protected function process()
    {

        // initialize the store view code
        $this->prepareStoreViewCode();

        // load the store ID, use the admin store if NO store view code has been set
        $storeId = $this->getStoreId(StoreViewCodes::ADMIN);

        // load the user defined EAV attributes by the found attribute set and the backend types
        $attributes = $this->getEavUserDefinedAttributes();

        // load the header keys
        $headers = array_flip($this->getHeaders());

        // remove all the empty values from the row
        $row = $this->clearRow();

        // initialize the array for the artefacts
        $artefacts = array();

        // load the entity type ID
        $entityType = $this->loadEavEntityTypeByEntityTypeCode($this->getSubject()->getEntityTypeCode());
        $entityTypeId = $entityType[MemberNames::ENTITY_TYPE_ID];

        $emptyValueDefinition = $this->getEmptyAttributeValueConstant();

        // iterate over the attributes and append them to the row
        foreach ($row as $key => $attributeValue) {
            // query whether or not attribute with the found code exists
            if (!isset($attributes[$attributeCode = $headers[$key]])) {
                // log a message in debug mode
                if ($this->isDebugMode()) {
                    $this->getSystemLogger()->debug(
                        $this->appendExceptionSuffix(
                            sprintf(
                                'Can\'t find attribute with attribute code %s',
                                $attributeCode
                            )
                        )
                    );
                }

                // stop processing
                continue;
            } else {
                // log a message in debug mode
                if ($this->isDebugMode()) {
                    // log a message in debug mode
                    $this->getSystemLogger()->debug(
                        $this->appendExceptionSuffix(
                            sprintf(
                                'Found attribute with attribute code %s',
                                $attributeCode
                            )
                        )
                    );
                }
            }

            // if yes, load the attribute by its code
            $attribute = $attributes[$attributeCode];

            // we only support user defined EAV attributes of type select and multiselect
            if (in_array($attribute[MemberNames::FRONTEND_INPUT], array(FrontendInputTypes::SELECT, FrontendInputTypes::MULTISELECT))) {
                // explode the values if we've a multiselect
                $valuesExploded = $this->explode($attributeValue, $this->getMultipleValueDelimiter());
                // check if valueExploded an array to fix crash on next step "foreach"
                $values = is_array($valuesExploded) ? $valuesExploded : [];
                // iterate over the values
                foreach ($values as $value) {
                    // query whether the value corresponds to the Empty Value definition to skip
                    if ($value === $emptyValueDefinition) {
                        continue;
                    }
                    // query whether or not the attribute value already exists
                    if ($this->loadAttributeOptionValueByEntityTypeIdAndAttributeCodeAndStoreIdAndValue($entityTypeId, $attributeCode, $storeId, $value)) {
                        continue;
                    }

                    // try to load the swatch type, if available
                    $swatchType = $this->getSwatchTypeLoader()->loadSwatchType($entityTypeId, $attributeCode);

                    // add the artefact to the array
                    $artefacts[] = $this->newArtefact(
                        array(
                            ColumnKeys::DEFAULT_VALUE  => $attribute[MemberNames::DEFAULT_VALUE],
                            ColumnKeys::ATTRIBUTE_CODE => $attribute[MemberNames::ATTRIBUTE_CODE],
                            ColumnKeys::SORT_ORDER     => 0,
                            ColumnKeys::VALUE          => is_null($swatchType) ? $value : null,
                            ColumnKeys::SWATCH_TYPE    => $swatchType,
                            ColumnKeys::SWATCH_VALUE   => $swatchType ? $value : null
                        ),
                        array()
                    );
                }
            }
        }

        // export the array with artefacts
        $this->addArtefacts($artefacts);
    }

    /**
     * Returns the value(s) of the primary key column(s). As the primary key column can
     * also consist of two columns, the return value can be an array also.
     *
     * @return mixed The primary key value(s)
     */
    protected function getPrimaryKeyValue()
    {
        return $this->getValue(\TechDivision\Import\Product\Utils\ColumnKeys::SKU);
    }

    /**
     * Return's the import processor instance.
     *
     * @return \TechDivision\Import\Services\ImportProcessorInterface The import processor instance
     */
    protected function getImportProcessor()
    {
        return $this->importProcessor;
    }

    /**
     * Return's the attribute bunch processor instance.
     *
     * @return \TechDivision\Import\Attribute\Services\AttributeBunchProcessorInterface The attribute bunch processor instance
     */
    protected function getAttributeBunchProcessor()
    {
        return $this->attributeBunchProcessor;
    }

    /**
     * Return's the swatch type loader instance.
     *
     * @return \TechDivision\Import\Attribute\Callbacks\SwatchTypeLoaderInterface The swatch type loader instance
     */
    protected function getSwatchTypeLoader()
    {
        return $this->swatchTypeLoader;
    }

    /**
     * Return's an array with the available user defined EAV attributes for the actual entity type.
     *
     * @return array The array with the user defined EAV attributes
     */
    protected function getEavUserDefinedAttributes()
    {
        return $this->getSubject()->getEavUserDefinedAttributes();
    }

    /**
     * Return's an EAV entity type with the passed entity type code.
     *
     * @param string $entityTypeCode The code of the entity type to return
     *
     * @return array The entity type with the passed entity type code
     */
    protected function loadEavEntityTypeByEntityTypeCode($entityTypeCode)
    {
        return $this->getImportProcessor()->getEavEntityTypeByEntityTypeCode($entityTypeCode);
    }

    /**
     * Load's and return's the EAV attribute option value with the passed entity type ID, code, store ID and value.
     *
     * @param string  $entityTypeId  The entity type ID of the EAV attribute to load the option value for
     * @param string  $attributeCode The code of the EAV attribute option to load
     * @param integer $storeId       The store ID of the attribute option to load
     * @param string  $value         The value of the attribute option to load
     *
     * @return array The EAV attribute option value
     */
    protected function loadAttributeOptionValueByEntityTypeIdAndAttributeCodeAndStoreIdAndValue($entityTypeId, $attributeCode, $storeId, $value)
    {
        return $this->getAttributeBunchProcessor()->loadAttributeOptionValueByEntityTypeIdAndAttributeCodeAndStoreIdAndValue($entityTypeId, $attributeCode, $storeId, $value);
    }

    /**
     * Create's and return's a new empty artefact entity.
     *
     * @param array $columns             The array with the column data
     * @param array $originalColumnNames The array with a mapping from the old to the new column names
     *
     * @return array The new artefact entity
     */
    protected function newArtefact(array $columns, array $originalColumnNames)
    {
        return $this->getSubject()->newArtefact($columns, $originalColumnNames);
    }

    /**
     * Add the passed product type artefacts to the product with the
     * last entity ID.
     *
     * @param array $artefacts The product type artefacts
     *
     * @return void
     * @uses \TechDivision\Import\Product\Media\Subjects\MediaSubject::getLastEntityId()
     */
    protected function addArtefacts(array $artefacts)
    {
        $this->getSubject()->addArtefacts(ProductToAttributeOptionValueConverterObserver::ARTEFACT_TYPE, $artefacts);
    }
}
