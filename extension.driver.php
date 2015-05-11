<?php
	/*
	Copyight: Deux Huit Huit 2015
	LICENCE: MIT http://deuxhuithuit.mit-license.org;
	*/
	
	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");
	
	/**
	 *
	 * @author Deux Huit Huit
	 * https://deuxhuithuit.com/
	 *
	 */
	class extension_multilingual_selectbox_link_field extends extension_selectbox_link_field {

		/**
		 * Name of the extension
		 * @var string
		 */
		const EXT_NAME = 'Field: Multilingual Selectbox Link';
		
		/* ********* INSTALL/UPDATE/UNINSTALL ******* */

		/**
		 * Creates the table needed for the settings of the field
		 */
		public function install() {
			$__select_box_link_loaded = false;
			try {
				require_once(EXTENSIONS . '/selectbox_link_field/extension.driver.php');
				require_once(EXTENSIONS . '/selectbox_link_field/fields/field.selectbox_link.php');
				$__select_box_link_loaded = true;
			} catch (Exception $ex) {
				$__select_box_link_loaded = false;
			}
			if ($__select_box_link_loaded != true) {
				Administration::instance()->Page->pageAlert('Could not load selectbox_link_field extension.', Alert::ERROR);
				return false;
			}
			// depends on "Languages"
			$languages_status = ExtensionManager::fetchStatus(array('handle' => 'languages'));
			$languages_status = current($languages_status);
			if ($languages_status != EXTENSION_ENABLED) {
				Administration::instance()->Page->pageAlert('Could not load languages extension.', Alert::ERROR);
				return false;
			}
			
			// create table "alias"
			Symphony::instance()->Database()->query("CREATE VIEW `tbl_fields_multilingual_selectbox_link` AS
				SELECT * FROM `tbl_fields_selectbox_link`;");
			
			return true;
		}

		/**
		 * This method will update the extension according to the
		 * previous and current version parameters.
		 * @param string $previousVersion
		 */
		public function update($previousVersion = false) {
			$ret = true;
			return $ret;
		}

		public function uninstall() {
			Symphony::instance()->Database()->query("DROP VIEW `tbl_fields_multilingual_selectbox_link`");
			return true;
		}

	}