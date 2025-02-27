<?php

use Contao\ArrayUtil;
use Alnv\CatalogManagerIcsExportBundle\Modules\IcsExportModule;

ArrayUtil::arrayInsert($GLOBALS['FE_MOD'], 3, [
    'ics-export' => [
        'icsExport' => IcsExportModule::class
    ]
]);