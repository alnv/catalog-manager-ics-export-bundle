<?php

$GLOBALS['TL_DCA']['tl_module']['config']['onload_callback'][] = [ 'ics.export.datacontainer', 'customizeIcaPalettes' ];

$GLOBALS['TL_DCA']['tl_module']['palettes']['icsExport'] = '{title_legend},name,headline,type;{catalog_legend},catalogTablename;{catalog_ics_legend},catalogNameField,catalogStartDateField,catalogEndDateField,catalogLocationField,catalogDescriptionField,catalogUrlField,catalogICalFileName;{catalog_taxonomy_legend},catalogUseTaxonomies,catalogActiveParameters;{catalog_master_legend:hide},catalogUseMasterPage;{catalog_pagination_legend:hide},catalogPerPage,catalogOffset;{catalog_join_legend:hide},catalogJoinFields,catalogJoinParentTable;{catalog_radiusSearch_legend:hide},catalogUseRadiusSearch;{protected_legend:hide:hide},protected;{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['fields']['catalogStartDateField'] = [

    'label' => &$GLOBALS['TL_LANG']['tl_module']['catalogStartDateField'],
    'inputType' => 'select',
    'eval' => [
        'chosen' => true,
        'maxlength' => 128,
        'tl_class' => 'w50',
        'mandatory' => true,
        'includeBlankOption' => true
    ],
    'options_callback' => [ 'ics.export.datacontainer', 'getFields' ],
    'exclude' => true,
    'sql' => "varchar(128) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['catalogEndDateField'] = [

    'label' => &$GLOBALS['TL_LANG']['tl_module']['catalogEndDateField'],
    'inputType' => 'select',
    'eval' => [
        'chosen' => true,
        'maxlength' => 128,
        'tl_class' => 'w50',
        'includeBlankOption' => true
    ],
    'options_callback' => [ 'ics.export.datacontainer', 'getFields' ],
    'exclude' => true,
    'sql' => "varchar(128) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['catalogLocationField'] = [

    'label' => &$GLOBALS['TL_LANG']['tl_module']['catalogLocationField'],
    'inputType' => 'text',
    'eval' => [
        'chosen' => true,
        'maxlength' => 255,
        'tl_class' => 'w50',
        'decodeEntities' => true
    ],
    'exclude' => true,
    'sql' => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['catalogNameField'] = [

    'label' => &$GLOBALS['TL_LANG']['tl_module']['catalogNameField'],
    'inputType' => 'text',
    'eval' => [
        'chosen' => true,
        'maxlength' => 128,
        'mandatory' => true,
        'tl_class' => 'w50',
        'decodeEntities' => true
    ],
    'exclude' => true,
    'sql' => "varchar(128) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['catalogDescriptionField'] = [

    'label' => &$GLOBALS['TL_LANG']['tl_module']['catalogDescriptionField'],
    'inputType' => 'text',
    'eval' => [
        'chosen' => true,
        'maxlength' => 128,
        'tl_class' => 'w50',
        'decodeEntities' => true
    ],
    'exclude' => true,
    'sql' => "varchar(128) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['catalogUrlField'] = [

    'label' => &$GLOBALS['TL_LANG']['tl_module']['catalogUrlField'],
    'inputType' => 'text',
    'eval' => [
        'chosen' => true,
        'maxlength' => 128,
        'tl_class' => 'w50',
        'decodeEntities' => true
    ],
    'exclude' => true,
    'sql' => "varchar(128) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['catalogICalFileName'] = [

    'label' => &$GLOBALS['TL_LANG']['tl_module']['catalogICalFileName'],
    'inputType' => 'text',
    'eval' => [
        'chosen' => true,
        'maxlength' => 128,
        'tl_class' => 'w50',
        'decodeEntities' => true
    ],
    'exclude' => true,
    'sql' => "varchar(128) NOT NULL default ''"
];