<?php

/*
 * Contao Open Source CMS
 *
 */

namespace Sioweb;
use Contao;
use Contao\System;

/**
* @file DownloadFolder.php
* @class DownloadFolder.php
* @author Sascha Weidner
* @version 4.0.0
* @package sioweb.contao.extensions.backend
* @copyright Sioweb - Sascha Weidner
*/

class DownloadFolder extends \ContentElement {

	/**
	 * Files object
	 * @var \FilesModel
	 */
	protected $objFiles;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_downloadfolder';


	/**
	 * Return if there are no files
	 * @return string
	 */
	public function generate() {
		// Use the home directory of the current user as file source
		if($this->useHomeDir && FE_USER_LOGGED_IN) {
			$this->import('FrontendUser', 'User');

			if($this->User->assignDir && $this->User->homeDir) {
				$this->orderSRC = array($this->User->homeDir);
			}
		} else {
			$this->orderSRC = deserialize($this->orderSRC);
		}

		// Return if there are no files
		if(!is_array($this->orderSRC) || empty($this->orderSRC)) {
			return '';
		}

		// Get the file entries from the database
		$this->objFiles = \FilesModel::findMultipleByUuids($this->orderSRC);

		if($this->objFiles === null) {
			if(!\Validator::isUuid($this->orderSRC[0])) {
				return '<p class="error">'.$GLOBALS['TL_LANG']['ERR']['version2format'].'</p>';
			}
			return '';
		}

		$file = \Input::get('file', true);

		// Send the file to the browser and do not send a 404 header (see #4632)
		if($file != '' && !preg_match('/^meta(_[a-z]{2})?\.txt$/', basename($file))) {
			while($this->objFiles->next()) {
				if($file == $this->objFiles->path || dirname($file) == $this->objFiles->path) {
					\Controller::sendFileToBrowser($file);
				}
			}

			$this->objFiles->reset();
		}

		return parent::generate();
	}


	/**
	 * Generate the content element
	 */
	protected function compile() {
		global $objPage;

		$downloadPath = TL_ROOT.'/assets/downloads/';
		$downloadFile = 'assets/downloads/'.date('Y-m-d')."_".$this->downloadFileTitle.".zip";

		if(is_file($downloadFile)) {
			unlink($downloadFile);
		}
		
		$files = array();
		$auxDate = array();

		$objFiles = $this->objFiles;
		$allowedDownload = trimsplit(',', strtolower($GLOBALS['TL_CONFIG']['allowedDownload']));

		if(!is_dir($downloadPath)) {
			System::log('Try to Zip files, can\'t find folder \'dowloads\' in '.TL_ROOT.'/assets/downloads/','compile ZIP-Download',false);
			return false;
		}
		if(!$this->downloadFileTitle) {
			System::log('No downloadfile-title found, please add a filename.','compile ZIP-Download',false);
			return false;
		}
		echo rand(1,10000);
		die();


		// Get all files
		while($objFiles->next()) {
			// Continue if the files has been processed or does not exist
			if(isset($files[$objFiles->path]) || !file_exists(TL_ROOT . '/' . $objFiles->path))
				continue;

			// Single files
			if($objFiles->type == 'file') {
				$objFile = new \File($objFiles->path, true);

				if(!in_array($objFile->extension, $allowedDownload) || preg_match('/^meta(_[a-z]{2})?\.txt$/', $objFile->basename))
					continue;
					
				$Pathinfo = pathinfo($objFiles->path);
					/* The Zip-Path D: */
				exec("cd '".TL_ROOT."/".$Pathinfo['dirname']."' && zip ".TL_ROOT.'/'.$downloadFile." ".$Pathinfo['filename'].".".$Pathinfo['extension'],$var);
				#echo 'Dirname: '.$Pathinfo['dirname'].'<br>';
				#echo '<pre>'.print_r($var,1).'</pre>';
			} else {
				$objSubfiles = \FilesModel::findByPid($objFiles->uuid);
				
				if($objSubfiles === null) {
					continue;
				}

				while($objSubfiles->next()) {
					if(isset($files[$objFiles->path]) || !file_exists(TL_ROOT . '/' . $objFiles->path)) {
						continue;
					}

					$Pathinfo = pathinfo($objSubfiles->path);
					/* The Zip-Path D: */
					#echo 'Dirname: '.$Pathinfo['dirname'].'<br>';	
					exec("cd ".TL_ROOT."/".$Pathinfo['dirname']." && zip ".TL_ROOT.'/'.$downloadFile." ".$Pathinfo['filename'].".".$Pathinfo['extension'],$var);
					#echo '<pre>'.print_r($var,1).'</pre>';
				}
			}
		}

		$this->Template->title = $this->Template->link = $this->Template->name = $this->downloadFileTitle;
		$this->Template->href = $downloadFile;
		$this->Template->icon = TL_ASSETS_URL . 'assets/contao/images/iconRAR.gif';
		$this->Template->extension = 'zip';
		$this->Template->mime = 'application/zip';
		$this->Template->path = $newDownloadPath;
	}
}
