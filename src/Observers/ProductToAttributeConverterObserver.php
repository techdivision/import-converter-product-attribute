<?php

/**
 * TechDivision\Import\Converter\Product\Attribute\Observers\ProductToAttributeConverterObserver
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

use TechDivision\Import\Converter\Observers\AbstractConverterObserver;

/**
 * Observer that extracts the attributes from a product CSV.
 *
 * @author    Tim Wagner <t.wagner@techdivision.com>
 * @copyright 2019 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/import-converter-product-attribute
 * @link      http://www.techdivision.com
 */
class ProductToAttributeConverterObserver extends AbstractConverterObserver
{

    /**
     * The artefact type.
     *
     * @var string
     */
    const ARTEFACT_TYPE = 'attribute-import';

    /**
     * Process the observer's business logic.
     *
     * @return void
     */
    protected function process()
    {
        // still to implement
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
        $this->getSubject()->addArtefacts(ProductToAttributeConverterObserver::ARTEFACT_TYPE, $artefacts);
    }
}
