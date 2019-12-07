<?php

/**
 * TechDivision\Import\Converter\Product\Attribute\Subjects\ProductCategoryConverterSubject
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

namespace TechDivision\Import\Converter\Product\Attribute\Subjects;

use TechDivision\Import\Utils\CacheKeys;
use TechDivision\Import\Product\Utils\ColumnKeys;

/**
 * The subject implementation that handles the business logic to persist products.
 *
 * @author    Tim Wagner <t.wagner@techdivision.com>
 * @copyright 2019 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/import-converter-product-attribute
 * @link      http://www.techdivision.com
 */
class ConverterSubject extends \TechDivision\Import\Converter\Subjects\ConverterSubject
{

    /**
     * Return's the artefacts for post-processing.
     *
     * @return array The artefacts
     */
    public function getArtefacts()
    {

        // initialize the array for the artefacts
        $artefacts = array();

        // query whether or not artefacts are available
        if (is_array($arts = $this->getRegistryProcessor()->getAttribute(CacheKeys::ARTEFACTS))) {
            $artefacts = $arts;
        }

        // return the artefacts
        return $artefacts;
    }

    /**
     * Add the passed product type artefacts to the product with the
     * last entity ID and overrides existing ones with the same key.
     *
     * @param string $type      The artefact type, e. g. configurable
     * @param array  $artefacts The product type artefacts
     *
     * @return void
     */
    protected function overrideArtefacts($type, array $artefacts)
    {

        // initialize the array for the artefacts that has to be merged
        $toBeMerged = array();

        // add the artefacts by using their array key to override them
        foreach ($artefacts as $key => $artefact) {
            $toBeMerged[$type][$this->getLastEntityId()][$key] = $artefact;
        }

        // replace the artefacts in the registry
        $this->getRegistryProcessor()->mergeAttributesRecursive(CacheKeys::ARTEFACTS, $toBeMerged);
    }

    /**
     * Append's the passed product type artefacts to the product with the
     * last entity ID.
     *
     * @param string $type      The artefact type, e. g. configurable
     * @param array  $artefacts The product type artefacts
     *
     * @return void
     */
    protected function appendArtefacts($type, array $artefacts)
    {

        // initialize the array for the artefacts that has to be merged
        $toBeMerged = array();

        // append the artefacts
        foreach ($artefacts as $artefact) {
            $toBeMerged[$type][$this->getLastEntityId()][] = $artefact;
        }

        // replace the artefacts in the registry
        $this->getRegistryProcessor()->mergeAttributesRecursive(CacheKeys::ARTEFACTS, $toBeMerged);
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
    public function getArtefactsByTypeAndEntityId($type, $entityId)
    {

        // load the available artefacts from the registry
        $arts = $this->getRegistryProcessor()->getAttribute(CacheKeys::ARTEFACTS);

        // query whether or not, artefacts for the passed params are available
        if (isset($arts[$type][$entityId])) {
            // load the artefacts
            $artefacts = $arts[$entityId];

            // unserialize the original data, if we're in debug mode
            $keys = array_keys($artefacts);
            foreach ($keys as $key) {
                if (isset($artefacts[$key][ColumnKeys::ORIGINAL_DATA])) {
                    $artefacts[$key][ColumnKeys::ORIGINAL_DATA] = $this->isDebugMode() ? unserialize($artefacts[$key][ColumnKeys::ORIGINAL_DATA]) : null;
                }
            }

            // return the artefacts
            return $artefacts;
        }

        // throw an exception if not
        throw new \Exception(
            sprintf(
                'Cant\'t load artefacts for type %s and entity ID %d',
                $type,
                $entityId
            )
        );
    }

    /**
     * Queries whether or not artefacts for the passed type and entity ID are available.
     *
     * @param string $type     The artefact type, e. g. configurable
     * @param string $entityId The entity ID to return the artefacts for
     *
     * @return boolean TRUE if artefacts are available, else FALSE
     */
    public function hasArtefactsByTypeAndEntityId($type, $entityId)
    {

        // load the available artefacts from the registry
        $arts = $this->getRegistryProcessor()->getAttribute(CacheKeys::ARTEFACTS);

        // query whether or not artefacts for the passed type and entity ID are available
        return isset($arts[$type][$entityId]);
    }

    /**
     * Export's the artefacts to CSV files and resets the array with the artefacts to free the memory.
     *
     * @param integer $timestamp The timestamp part of the original import file
     * @param string  $counter   The counter part of the origin import file
     *
     * @return void
     */
    public function export($timestamp, $counter)
    {
        // do nothing here, because we want to plug-in to export the categories
    }

    /**
     * Return's the ID of the product that has been created recently.
     *
     * @return string The entity Id
     */
    public function getLastEntityId()
    {
        return $this->getValue(ColumnKeys::SKU);
    }
}
