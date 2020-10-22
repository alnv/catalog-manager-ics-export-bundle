<?php

namespace Alnv\CatalogManagerIcsExportBundle\Modules;

class IcsExportModule extends \Module {

    protected $strToken = null;
    protected $arrEntities = [];
    protected $blnActiveExport = true;
    protected $strTemplate = 'mod_ics_export';

    public function generate() {

        if ( TL_MODE == 'BE' ) {

            $objTemplate = new \BackendTemplate('be_wildcard');
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->title = $this->headline;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;
            $objTemplate->wildcard = '### ICS EXPORT ###';

            return $objTemplate->parse();
        }

        if ( !$this->catalogTablename ) {

            return '';
        }

        global $objPage;

        $this->import( 'CatalogInput' );

        $this->strToken = md5( $this->id );
        $arrActiveParams = explode( ',', $this->catalogActiveParameters );

        if ( is_array( $arrActiveParams ) && !empty( $arrActiveParams ) ) {

            foreach ( $arrActiveParams as $strParameter ) {

                if ( \CatalogManager\Toolkit::isEmpty( $strParameter ) ) {

                    continue;
                }

                if ( \CatalogManager\Toolkit::isEmpty( $this->CatalogInput->getActiveValue( $strParameter ) ) ) {

                    $this->blnActiveExport = false;

                    break;
                }
            }
        }

        if ( \Input::post( 'ICS_DOWNLOAD' ) == $this->strToken && $this->blnActiveExport ) {

            $strFilename = $objPage->alias;

            if ( $this->catalogICalFileName ) {

                $strFilename = \Controller::replaceInsertTags( $this->catalogICalFileName );
                $strFilename = \StringUtil::standardize( $strFilename, false );
            }

            header('Content-type: text/calendar; charset=utf-8');
            header('Content-Disposition: attachment; filename='. $strFilename .'.ics');

            echo $this->createIcsFile();
            exit;
        }

        return parent::generate();
    }

    protected function compile() {

        $this->Template->button = $GLOBALS['TL_LANG']['MSC']['icsExportButton'];
        $this->Template->active = $this->blnActiveExport;
        $this->Template->token = $this->strToken;
    }

    protected function setEntities() {

        $this->arrData['catalogUseArray'] = '1';
        $this->arrData['catalogFastMode'] = '1';
        $this->arrData['catalogExcludeArrayOptions'] = serialize( \CatalogManager\Toolkit::$arrArrayOptions );

        $objView = new \CatalogManager\CatalogView();
        $objView->strMode = 'view';
        $objView->arrOptions = $this->arrData;
        $objView->objMainTemplate = $this->Template;
        $objView->initialize();

        $this->arrEntities = $objView->getCatalogView( [ 'where' => [], 'orderBy' => [] ] );
    }

    protected function createIcsFile() {

        $arrIcsData = [];
        $this->setEntities();

        foreach ($this->arrEntities as $arrEntity) {

            $intStartDate = $this->getDate('catalogStartDateField', $arrEntity, 'dayBegin');
            $intEndDate = $this->getDate('catalogEndDateField', $arrEntity, 'dayEnd');

            if ( !$intStartDate ) continue;

            $arrData = [
                'BEGIN:VEVENT' => '',
                'DTSTART:' => $intStartDate,
                'LOCATION:' => $this->getSimpleTokenValue('catalogLocationField', $arrEntity),
                'DTSTAMP:' => date('Ymd\THis', time()),
                'SUMMARY:' => $this->getSimpleTokenValue('catalogNameField', $arrEntity),
                'URL;VALUE=URI:' => $this->getSimpleTokenValue('catalogUrlField', $arrEntity),
                'DESCRIPTION:' => $this->getSimpleTokenValue('catalogDescriptionField', $arrEntity),
                'UID:' => md5($arrEntity['id']),
            ];
            if ($intEndDate) {
                $arrIcsData['DTEND:'] = $intEndDate;
            }
            $arrData['END:VEVENT'] = '';

            $arrIcsData[] = $arrData;
        }

        $strFile =
            "BEGIN:VCALENDAR" . "\r\n" .
            "VERSION:2.0" . "\r\n".
            "PRODID://catalog_manager//catalog_manager.org//DE" . "\r\n";

        foreach ($arrIcsData as $arrIcs) {
            foreach ($arrIcs as $strType => $strValue) {
                $strFile .= $strType . $strValue . "\r\n";
            }
        }

        $strFile .= 'END:VCALENDAR';

        return $strFile;
    }

    private function getDate($strField, $arrEntity, $strType) {

        $strDate = $arrEntity[$this->{$strField}] ?: '';
        if (!$strDate) {
            return '';
        }
        if (\Validator::isDate($strDate)) {
            $objDate = new \Date($strDate);
            return date('Ymd\THis', ($objDate->{$strType}+5400));
        }
        if (\Validator::isDatim($strDate)) {
            $objDate = new \Date($strDate);
            return date('Ymd\THis', $objDate->tstamp);
        }
        return '';
    }

    private function getSimpleTokenValue($strField, $arrEntity) {

        $strToken = $this->{$strField} ?: '';

        if ( !$strToken ) return '';

        return \StringUtil::parseSimpleTokens($strToken, $arrEntity);
    }
}