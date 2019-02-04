<?php
	/*
	Copyright: Deux Huit Huit 2015
	LICENCE: MIT http://deuxhuithuit.mit-license.org;
	*/

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once(TOOLKIT . '/class.field.php');
	require_once(EXTENSIONS . '/selectbox_link_field/fields/field.selectbox_link.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.FLang.php');

	/**
	 *
	 * Field class that will represent relationships between entries
	 * @author Deux Huit Huit
	 *
	 */
	class FieldMultilingual_SelectBox_Link extends FieldSelectBox_Link
	{
		public function __construct(){
			parent::__construct();
			$this->_name = __('Multilingual Select Box Link');
		}

		protected function getFieldSchema($fieldId) {
			$lc = FLang::getLangCode();

			if (empty($lc)) {
				$lc = FLang::getMainLang();
			}

			try {
				return Symphony::Database()
					->showColumns()
					->from('tbl_entries_data_' . $fieldId)
					->where(['Field' => ['in' => ['value-' . $lc]]])
					->execute()
					->rows();
			}
			catch (Exception $ex) {
				// bail out
			}
			return parent::getFieldSchema($fieldId);
		}

		public function fetchIDfromValue($value) {
			$id = null;
			$related_field_ids = $this->get('related_field_id');

			$lc = FLang::getLangCode();

			if (empty($lc)) {
				$lc = FLang::getMainLang();
			}

			$value = Lang::createHandle($value);

			$try_parent = false;

			foreach($related_field_ids as $related_field_id) {
				try {
					$return = Symphony::Database()
						->select(['entry_id' => 'id'])
						->from('tbl_entries_data_' . $related_field_id)
						->where(['or' => [
							['handle' => $value],
							['handle-' . $lc => $value],
						]])
						->limit(1)
						->execute()
						->column('id');

					// Skipping returns wrong results when doing an
					// AND operation, return 0 instead.
					if(!empty($return)) {
						$id = $return[0];
						break;
					}
				} catch (Exception $ex) {
					// Try the parent since this would normally be the case when a handle
					// column doesn't exist!
					$try_parent = true;
				}
			}

			if ($try_parent) {
				return parent::fetchIDfromValue($value);
			}

			return (is_null($id)) ? 0 : (int)$id;
		}

		public function fetchAssociatedEntrySearchValue($data, $field_id = null, $parent_entry_id = null){
			// We dont care about $data, but instead $parent_entry_id
			if(!is_null($parent_entry_id)) return $parent_entry_id;

			if(!is_array($data)) return $data;

			$handle = $data['handle'];

			$try_parent = false;

			$searchvalue = null;

			try {
				$searchvalue = Symphony::Database()
					->select(['entry_id'])
					->from('tbl_entries_data_' . $field_id)
					->where(['or' => [
						['handle' => $handle],
						['handle-' . $lc => $handle],
					]])
					->limit(1)
					->execute()
					->variable('entry_id');
			} catch (Exception $ex) {
				// Try the parent since this would normally be the case when a handle
				// column doesn't exist!
				$try_parent = true;
			}

			if ($try_parent) {
				return parent::fetchAssociatedEntrySearchValue($data, $field_id, $parent_entry_id);
			}

			return $searchvalue;
		}

		private static function startsWith($haystack, $needle) {
			// search backwards starting from haystack length characters from the end
			return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
		}

		protected function findRelatedValues(array $relation_id = array()) {
			$relation_data = parent::findRelatedValues($relation_id);
			if (is_array($relation_data)) {
				foreach ($relation_data as $r => $relation) {
					$section_id = SectionManager::fetchIDFromHandle($relation['section_handle']);
					$e = (new EntryManager)
						->select()
						->entry($relation['id'])
						->section($section_id)
						->includeAllFields()
						->execute()
						->next();
					$ed = $e->getData();
					foreach ($this->get('related_field_id') as $fieldId) {
						if (is_array($ed[$fieldId])) {
							foreach ($ed[$fieldId] as $key => $value) {
								if (self::startsWith($key, 'value-')) {
									$relation_data[$r][$key] = $value;
								}
							}
						}
					}
				}
			}
			return $relation_data;
		}

		public function prepareTextValue($data, $entry_id = null) {
			if(!is_array($data) || (is_array($data) && !isset($data['relation_id']))) {
				return parent::prepareTextValue($data, $entry_id);
			}

			if(!is_array($data['relation_id'])){
				$data['relation_id'] = array($data['relation_id']);
			}

			$result = $this->findRelatedValues($data['relation_id']);
			$lc = FLang::getLangCode();

			if (empty($lc)) {
				$lc = FLang::getMainLang();
			}

			$label = '';
			foreach($result as $item){
				if (isset($item['value-' . $lc])) {
					$label .= $item['value-' . $lc];
				} else {
					$label .= $item['value'];
				}
				$label .= ', ';
			}

			return trim($label, ', ');
		}
	}
