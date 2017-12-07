<?php

/*
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
 *
 */

/**
* @file tl_content.php
* @author Sascha Weidner
* @version 4.0.0
* @package sioweb.contao.extensions.backend
* @copyright Sioweb - Sascha Weidner
*/

/**
 * Table tl_downloadfolder
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['sw_downloadfolder'] = str_replace('multiSRC','folders,downloadFileTitle',$GLOBALS['TL_DCA']['tl_content']['palettes']['downloads']);

$GLOBALS['TL_DCA']['tl_content']['fields']['folders'] = $GLOBALS['TL_DCA']['tl_content']['fields']['multiSRC'];
unset($GLOBALS['TL_DCA']['tl_content']['fields']['folders']['load_callback']);
// $GLOBALS['TL_DCA']['tl_content']['fields']['folders']['eval']['isGallery'] = true;

$GLOBALS['TL_DCA']['tl_content']['fields']['downloadFileTitle'] = array(
	'label'                   => &$GLOBALS['TL_LANG']['tl_content']['downloadFileTitle'],
	'inputType'               => 'text',
	'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
	'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['folders']['load_callback'] = array(
	array('downloadFolderAsZip', 'setMultiSrcFlags')
);

class downloadFolderAsZip extends tl_content {

	public function setMultiSrcFlags($varValue, DataContainer $dc)
	{
		if($dc->activeRecord->type === 'sw_downloadfolder') {
			$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['isDownloads'] = true;
			$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['extensions'] = Config::get('allowedDownload');
			return $varValue;
		}
		return parent::setMultiSrcFlags($varValue,$dc);
	}
}