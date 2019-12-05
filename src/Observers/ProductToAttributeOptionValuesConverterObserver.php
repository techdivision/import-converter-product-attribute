<?php

/**
 * TechDivision\Import\Converter\Product\Attribute\Observers\ProductToAttributeOptionValuesConverterObserver
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
use TechDivision\Import\Attribute\Utils\ColumnKeys;
use TechDivision\Import\Attribute\Utils\MemberNames;
use TechDivision\Import\Observers\StateDetectorInterface;
use TechDivision\Import\Converter\Observers\AbstractConverterObserver;
use TechDivision\Import\Product\Services\ProductBunchProcessorInterface;

/**
 * Observer that extracts the attribute option values from a product CSV.
 *
 * @author    Tim Wagner <t.wagner@techdivision.com>
 * @copyright 2019 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/import-converter-product-attribute
 * @link      http://www.techdivision.com
 */
class ProductToAttributeOptionValuesConverterObserver extends AbstractConverterObserver
{

    /**
     * The artefact type.
     *
     * @var string
     */
    const ARTEFACT_TYPE = 'attribute-import';

    /**
     * The product bunch processor instance.
     *
     * @var \TechDivision\Import\Product\Services\ProductBunchProcessorInterface
     */
    protected $productBunchProcessor;

    /**
     * The entity's existing attribue option values.
     *
     * @var array
     */
    protected $attributeOptionValues = array();

    /**
     * Initialize the observer with the passed product bunch processor instance.
     *
     * @param \TechDivision\Import\Product\Services\ProductBunchProcessorInterface $productBunchProcessor The product bunch processor instance
     * @param \TechDivision\Import\Observers\StateDetectorInterface|null           $stateDetector         The state detector instance to use
     */
    public function __construct(
        ProductBunchProcessorInterface $productBunchProcessor,
        StateDetectorInterface $stateDetector = null
    ) {

        // initialize the bunch processor instance
        $this->productBunchProcessor = $productBunchProcessor;

        // pass the state detector to the parent method
        parent::__construct($stateDetector);
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
        $storeId = $this->getRowStoreId(StoreViewCodes::ADMIN);

        // load the entity's existing attributes
        $this->getAttributesByPrimaryKeyAndStoreId($this->getPrimaryKey(), $storeId);

        // load the store view - if no store view has been set, we assume the admin
        // store view, which will contain the default (fallback) attribute values
        $storeViewCode = $this->getSubject()->getStoreViewCode(StoreViewCodes::ADMIN);

        // query whether or not the row has already been processed
        if ($this->storeViewHasBeenProcessed($pk = $this->getPrimaryKeyValue(), $storeViewCode)) {
            // log a message
            $this->getSystemLogger()->warning(
                $this->appendExceptionSuffix(
                    sprintf(
                        'Attributes for %s "%s" + store view code "%s" has already been processed',
                        $this->getPrimaryKeyColumnName(),
                        $pk,
                        $storeViewCode
                    )
                )
            );

            // return immediately
            return;
        }

        // load the attributes by the found attribute set and the backend types
        $attributes = $this->getAttributes();

        // load the header keys
        $headers = array_flip($this->getHeaders());

        // remove all the empty values from the row
        $row = $this->clearRow();

        // initialize the array for the artefacts
        $artefacts = array();

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

            error_log(print_r($attribute, true));

            // query whether or not the
            /* if (isset($this->attributeOptionValues[(integer) $attribute[MemberNames::ATTRIBUTE_ID]])) {
                continue;
            } */

            // add the artefact to the array
            /* $artefacts[] = $this->newArtefact(
                array(
                    ColumnKeys::VALUE          => $attributeValue,
                    ColumnKeys::DEFAULT_VALUE  => $attribute[MemberNames::DEFAULT_VALUE],
                    ColumnKeys::ATTRIBUTE_CODE => $attribute[MemberNames::ATTRIBUTE_CODE],
                    ColumnKeys::SORT_ORDER     => 0,
                    ColumnKeys::SWATCH_TYPE    => null,
                    ColumnKeys::SWATCH_VALUE   => null
                ),
                array()
            ); */
        }

        // export the array with artefacts
        $this->addArtefacts($artefacts);
    }

    /**
     * Return's the attributes for the attribute set of the product that has to be created.
     *
     * @return array The attributes
     * @throws \Exception
     */
    protected function getAttributes()
    {
        return $this->getSubject()->getAttributes();
    }

    /**
     * Return's the attribute option values
     * @return array
     */
    protected function getAttributeOptionValues()
    {
        return $this->attributeOptionValues;
    }

    /**
     * Intializes the existing attributes for the entity with the passed primary key.
     *
     * @param string  $pk      The primary key of the entity to load the attributes for
     * @param integer $storeId The ID of the store view to load the attributes for
     *
     * @return array The entity attributes
     */
    protected function getAttributeValuesByPrimaryKeyAndStoreId($pk, $storeId)
    {
        $this->attributeOptionValues = $this->getProductBunchProcessor()->getProductAttributesByPrimaryKeyAndStoreId($pk, $storeId);
    }

    /**
     * Queries whether or not artefacts for the passed type and entity ID are available.
     *
     * @param string $type     The artefact type, e. g. configurable
     * @param string $entityId The entity ID to return the artefacts for
     *
     * @return boolean TRUE if artefacts are available, else FALSE
     */
    protected function hasArtefactsByTypeAndEntityId($type, $entityId)
    {
        return $this->getSubject()->hasArtefactsByTypeAndEntityId($type, $entityId);
    }

    /**
     * Return the artefacts for the passed type and entity ID.
     *
     * @param string $type     The artefact type, e. g. configurable
     * @param string $entityId The entity ID to return the artefacts for
     *
     * @return array The array with the artefacts
     * @throws \Exception Is thrown, if no artefacts are available
     */
    protected function getArtefactsByTypeAndEntityId($type, $entityId)
    {
        return $this->getSubject()->getArtefactsByTypeAndEntityId($type, $entityId);
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
        $this->getSubject()->addArtefacts(ProductToAttributeOptionValuesConverterObserver::ARTEFACT_TYPE, $artefacts);
    }
}
