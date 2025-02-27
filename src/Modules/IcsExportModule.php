<?php

namespace Alnv\CatalogManagerIcsExportBundle\Modules;

use Alnv\CatalogManagerBundle\CatalogView;
use Alnv\CatalogManagerBundle\Toolkit;
use Contao\BackendTemplate;
use Contao\Config;
use Contao\Date;
use Contao\Input;
use Contao\Module;
use Contao\StringUtil;
use Contao\System;
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\Entity\TimeZone;
use Eluceo\iCal\Domain\ValueObject\Attachment;
use Eluceo\iCal\Domain\ValueObject\DateTime;
use Eluceo\iCal\Domain\ValueObject\Location;
use Eluceo\iCal\Domain\ValueObject\SingleDay;
use Eluceo\iCal\Domain\ValueObject\TimeSpan;
use Eluceo\iCal\Domain\ValueObject\Uri;
use Eluceo\iCal\Domain\ValueObject\Date as EluceoDate;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use Symfony\Component\HttpFoundation\Request;

class IcsExportModule extends Module
{

    protected string $strToken = '';

    protected array $arrEntities = [];

    protected bool $blnActiveExport = true;

    protected $strTemplate = 'mod_ics_export';

    public function generate()
    {

        if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create(''))) {

            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->title = $this->headline;
            $objTemplate->href = 'contao?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;
            $objTemplate->wildcard = '### ICS EXPORT ###';

            return $objTemplate->parse();
        }

        if (!$this->catalogTablename) {
            return '';
        }

        global $objPage;

        $this->strToken = \md5($this->id);
        $arrActiveParams = \explode(',', $this->catalogActiveParameters);
        $objParser = System::getContainer()->get('contao.insert_tag.parser');

        if (\is_array($arrActiveParams) && !empty($arrActiveParams)) {
            foreach ($arrActiveParams as $strParameter) {
                if ($strParameter === '' || $strParameter === null) {
                    continue;
                }

                if (Input::get($strParameter) === '' || Input::get($strParameter) === null) {
                    $this->blnActiveExport = false;
                    break;
                }
            }
        }

        if (Input::post('ICS_DOWNLOAD') == $this->strToken && $this->blnActiveExport) {

            $strFilename = $objPage->alias;

            if ($this->catalogICalFileName) {
                $strFilename = $objParser->replaceInline((string)$this->catalogICalFileName);
                $strFilename = StringUtil::standardize($strFilename, false);
            }

            \header('Content-Type: text/calendar; charset=utf-8');
            \header('Content-Disposition: attachment; filename="' . $strFilename . '.ics"');

            \ob_end_clean();
            \ob_start();

            echo $this->createIcsFile();
            exit;
        }

        return parent::generate();
    }

    protected function compile()
    {
        $this->Template->button = $GLOBALS['TL_LANG']['MSC']['icsExportButton'] ?? '';
        $this->Template->active = $this->blnActiveExport;
        $this->Template->token = $this->strToken;
    }

    protected function setEntities()
    {

        $this->arrData['catalogUseArray'] = '1';
        $this->arrData['catalogFastMode'] = '1';
        $this->arrData['catalogExcludeArrayOptions'] = \serialize(Toolkit::$arrArrayOptions);

        $objView = new CatalogView();
        $objView->strMode = 'view';
        $objView->arrOptions = $this->arrData;
        $objView->objMainTemplate = $this->Template;
        $objView->initialize();

        $this->arrEntities = $objView->getCatalogView(['where' => [], 'orderBy' => []]);
    }

    protected function createIcsFile()
    {

        $arrIcsData = [];
        $this->setEntities();

        foreach ($this->arrEntities as $arrEntity) {

            $strStart = $arrEntity[$this->catalogStartDateField] ? (new Date($arrEntity[$this->catalogStartDateField]))->tstamp : 0;

            if (!$strStart) {
                continue;
            }

            $strEnd = $arrEntity[$this->catalogEndDateField] ? (new Date($arrEntity[$this->catalogEndDateField]))->tstamp : (new Date($strStart))->dayEnd;
            $strLocation = $this->getSimpleTokenValue('catalogLocationField', $arrEntity);
            $strSummary = $this->getSimpleTokenValue('catalogNameField', $arrEntity);
            $strDescription = $this->getSimpleTokenValue('catalogDescriptionField', $arrEntity);
            $strUrl = $this->getSimpleTokenValue('catalogUrlField', $arrEntity);

            $objEvent = new Event();
            $objEvent->setSummary($strSummary ?: '');
            $objEvent->setLocation(new Location(($strLocation ?: ''), $strSummary));
            $objEvent->setDescription($strDescription ?: '');

            if ($strUrl) {
                $objEvent->addAttachment(new Attachment(new Uri($strUrl)));
            }

            if ($strStart !== $strEnd) {
                $objDateTimeZone = new \DateTimeZone('Europe/Berlin');
                $objOccurenceStart = new DateTime(\DateTime::createFromFormat('Y-m-d - H:i:s', date('Y-m-d - H:i:s', $strStart), $objDateTimeZone), true);
                $objOccurenceEnd = new DateTime(\DateTime::createFromFormat('Y-m-d - H:i:s', date('Y-m-d - H:i:s', $strEnd), $objDateTimeZone), true);
                $objOccurrence = new TimeSpan($objOccurenceStart, $objOccurenceEnd);
                $objEvent->setOccurrence($objOccurrence);
            } else {
                $objEvent->setOccurrence(new SingleDay(new EluceoDate(\DateTimeImmutable::createFromFormat('Y-m-d', $strStart))));
            }

            $arrIcsData[] = $objEvent;
        }

        $objCalendar = new Calendar($arrIcsData);
        if ($strTimeZone = Config::get('timeZone')) {
            $objCalendar->addTimeZone(TimeZone::createFromPhpDateTimeZone(new \DateTimeZone($strTimeZone), \DateTimeImmutable::createFromFormat('Y', '1970'), \DateTimeImmutable::createFromFormat('Y', '1970')));
        }

        return (new CalendarFactory())->createCalendar($objCalendar);
    }

    private function getSimpleTokenValue($strField, $arrEntity)
    {

        $strToken = $this->{$strField} ?: '';

        if (!$strToken) return '';

        return System::getContainer()->get('contao.string.simple_token_parser')->parse($strToken, $arrEntity, true);
    }
}