<?php

namespace Alnv\CatalogManagerIcsExportBundle\DataContainer;

use Contao\DataContainer;
use Contao\Database;
use Alnv\CatalogManagerBundle\Toolkit;
use Alnv\CatalogManagerBundle\CatalogFieldBuilder;

class Module
{

    public function customizeIcaPalettes(DataContainer $objDataContainer = null)
    {

        if (!$objDataContainer) {
            return null;
        }

        $objDatabase = Database::getInstance();
        $objActiveRecord = $objDatabase->prepare('SELECT * FROM tl_module WHERE id = ?')->execute($objDataContainer->id);

        if (!$objActiveRecord->numRows) {
            return null;
        }

        if ($objActiveRecord->type == 'icsExport') {
            $GLOBALS['TL_DCA']['tl_module']['subpalettes']['catalogUseMasterPage'] = 'catalogMasterPage';
            $GLOBALS['TL_DCA']['tl_module']['fields']['catalogJoinFields']['options_callback'] = ['ics.export.datacontainer', 'getJoinAbleFields'];
            $GLOBALS['TL_DCA']['tl_module']['fields']['catalogActiveParameters']['label'] = $GLOBALS['TL_LANG']['tl_module']['icsExportActiveParameters'];
        }
    }


    public function getJoinAbleFields(DataContainer $objDataContainer = null)
    {

        $arrReturn = [];

        if (!$objDataContainer) {
            return $arrReturn;
        }

        $objDatabase = Database::getInstance();

        if (!$objDataContainer->activeRecord->catalogTablename) {

            return $arrReturn;
        }

        if (!$objDatabase->tableExists($objDataContainer->activeRecord->catalogTablename)) {
            return $arrReturn;
        }

        $objFieldBuilder = new CatalogFieldBuilder();
        $objFieldBuilder->initialize($objDataContainer->activeRecord->catalogTablename);
        $arrFields = $objFieldBuilder->getCatalogFields(true, null);

        foreach ($arrFields as $strFieldname => $arrField) {

            if ($arrField['multiple']) continue;
            if (!in_array($arrField['type'], ['select', 'radio'])) continue;
            if (!$arrField['optionsType'] || $arrField['optionsType'] == 'useOptions') continue;

            $arrReturn[$strFieldname] = Toolkit::getLabelValue($arrField['_dcFormat']['label'], $strFieldname);
        }

        return $arrReturn;
    }


    public function getFields(DataContainer $objDataContainer = null)
    {

        $arrReturn = [];

        if (!$objDataContainer) {

            return $arrReturn;
        }

        $objDatabase = Database::getInstance();

        if (!$objDataContainer->activeRecord->catalogTablename) {

            return $arrReturn;
        }

        if (!$objDatabase->tableExists($objDataContainer->activeRecord->catalogTablename)) {

            return $arrReturn;
        }

        $objFieldBuilder = new CatalogFieldBuilder();
        $objFieldBuilder->initialize($objDataContainer->activeRecord->catalogTablename);
        $arrFields = $objFieldBuilder->getCatalogFields(true, null);

        foreach ($arrFields as $strFieldname => $arrField) {

            if (is_numeric($strFieldname)) continue;
            if (in_array($arrFields['type'], ['upload', 'dbColumn'])) continue;
            if (in_array($arrFields['type'], Toolkit::excludeFromDc())) continue;

            $arrReturn[$strFieldname] = Toolkit::getLabelValue($arrField['_dcFormat']['label'], $strFieldname);
        }

        return $arrReturn;
    }
}