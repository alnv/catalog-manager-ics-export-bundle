<?php

namespace Alnv\CatalogManagerIcsExportBundle\Modules;

use function Clue\StreamFilter\remove;

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

            header('Content-Type: text/calendar; charset=utf-8');
            header('Content-Disposition: attachment; filename="'.$strFilename.'.ics"');

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

            $strStart = $arrEntity[$this->catalogStartDateField] ? (new \Date($arrEntity[$this->catalogStartDateField]))->tstamp : 0;
            if (!$strStart) continue;

            $strEnd = $arrEntity[$this->catalogEndDateField] ? (new \Date($arrEntity[$this->catalogEndDateField]))->tstamp : (new \Date($strStart))->dayEnd;
            $strLocation = $this->getSimpleTokenValue('catalogLocationField', $arrEntity);
            $strSummary = $this->getSimpleTokenValue('catalogNameField', $arrEntity);
            $strDescription = $this->getSimpleTokenValue('catalogDescriptionField', $arrEntity);
            $strUrl = $this->getSimpleTokenValue('catalogUrlField', $arrEntity);

            $objEvent = new \Eluceo\iCal\Domain\Entity\Event();
            $objEvent->setSummary($strSummary ?: '');
            $objEvent->setLocation(new \Eluceo\iCal\Domain\ValueObject\Location(($strLocation?:''), $strSummary));
            $objEvent->setDescription($strDescription ?: '');

            if ($strUrl) {
                $objEvent->addAttachment(new \Eluceo\iCal\Domain\ValueObject\Attachment(new \Eluceo\iCal\Domain\ValueObject\Uri($strUrl)));
            }

            $objDateTimeZone = new \DateTimeZone('Europe/Berlin');
            $objOccurenceStart = new \Eluceo\iCal\Domain\ValueObject\DateTime(\DateTime::createFromFormat('d.m.Y - H:i:s', date('d.m.Y - H:i:s', $strStart), $objDateTimeZone), true);
            $objOccurenceEnd = new \Eluceo\iCal\Domain\ValueObject\DateTime(\DateTime::createFromFormat('d.m.Y - H:i:s', date('d.m.Y - H:i:s', $strEnd), $objDateTimeZone), true);
            $objOccurrence = new \Eluceo\iCal\Domain\ValueObject\TimeSpan($objOccurenceStart, $objOccurenceEnd);
            $objEvent->setOccurrence($objOccurrence);

            /*
            if (!$strEnd) {
                $objEvent->setOccurrence(new \Eluceo\iCal\Domain\ValueObject\SingleDay($this->getDate('catalogStartDateField', $arrEntity, true)));
            } else {
                $objEvent->setOccurrence(new \Eluceo\iCal\Domain\ValueObject\TimeSpan($this->getDate('catalogStartDateField', $arrEntity, false), $objEndDate = $this->getDate('catalogEndDateField', $arrEntity, false)));
            }
            */

            $arrIcsData[] = $objEvent;
        }

        $objCalendar = new \Eluceo\iCal\Domain\Entity\Calendar($arrIcsData);
        if ($strTimeZone = \Config::get('timeZone')) {
            $objCalendar->addTimeZone(\Eluceo\iCal\Domain\Entity\TimeZone::createFromPhpDateTimeZone(new \DateTimeZone($strTimeZone),\DateTimeImmutable::createFromFormat('Y', '1970'), \DateTimeImmutable::createFromFormat('Y', '1970')));
        }

        return (new \Eluceo\iCal\Presentation\Factory\CalendarFactory())->createCalendar($objCalendar);
    }

    /*
    private function getDate($strField, $arrEntity, $blnSingleDay=true) {

        $strDate = $arrEntity[$this->{$strField}] ?: '';

        if (!$strDate) {
            return null;
        }

        $blnDateTime = \Validator::isDatim($strDate);
        $strFormat = $blnDateTime ? \Config::get('datimFormat') : \Config::get('dateFormat');

        return $blnSingleDay ? new \Eluceo\iCal\Domain\ValueObject\Date(\DateTimeImmutable::createFromFormat($strFormat, $strDate)) : new \Eluceo\iCal\Domain\ValueObject\DateTime(\DateTimeImmutable::createFromFormat($strFormat, $strDate), true);
    }
    */

    private function getSimpleTokenValue($strField, $arrEntity) {

        $strToken = $this->{$strField} ?: '';

        if ( !$strToken ) return '';

        return \StringUtil::parseSimpleTokens($strToken, $arrEntity);
    }
}