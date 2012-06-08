<?php

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	class FieldExpression extends Field {

	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function __construct() {
			parent::__construct();

			$this->_name = 'Expression';
			$this->_required = true;
		}

		public function createTable() {
			$field_id = $this->get('id');

			return Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_entries_data_{$field_id}` (
					`id` INT(11) NOT NULL auto_increment,
					`entry_id` INT(11) UNSIGNED NOT NULL,
					`value` VARCHAR(255) DEFAULT NULL,
					`compiled` VARCHAR(255) DEFAULT NULL,
					`is_regexp` ENUM('yes', 'no') DEFAULT 'no',
					`is_cased` ENUM('yes', 'no') DEFAULT 'no',
					PRIMARY KEY  (`id`),
					KEY `entry_id` (`entry_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			");
		}

		public function canFilter() {
			return true;
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/

		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);
			$this->appendShowColumnCheckbox($wrapper);
		}

		public function commit() {
			$this->set('required', 'yes');
			return parent::commit();
		}

	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/
		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null){
			Extension_ExpressionField::appendAssets();
			$handle = $this->get('element_name');

		// Defaults -----------------------------------------------------------

			$wrapper->appendChild(Widget::Input(
				"fields{$fieldnamePrefix}[{$handle}][is_cased]{$fieldnamePostfix}", 'no', 'hidden'
			));
			$wrapper->appendChild(Widget::Input(
				"fields{$fieldnamePrefix}[{$handle}][is_regexp]{$fieldnamePostfix}", 'no', 'hidden'
			));

		// Expression ---------------------------------------------------------

			$label = Widget::Label(__('Expression'));
			$label->appendChild(Widget::Input(
				"fields{$fieldnamePrefix}[{$handle}][value]{$fieldnamePostfix}",
				General::sanitize($data['value'])
			));

			if (isset($error)) {
				$label = Widget::Error($label, $flagWithError);
			}

			$wrapper->appendChild($label);

			$help = new XMLElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue(__('Use %s as a wild-card unless regular expressions are enabled.', array('<code>*</code>')));

			$wrapper->appendChild($help);

		// Cased? -------------------------------------------------------------

			$settings = new XMLElement('div', null, array('class' => 'frame'));

			$input = Widget::Input(
				"fields{$fieldnamePrefix}[{$handle}][is_cased]{$fieldnamePostfix}", 'yes', 'checkbox',
				($data['is_cased'] == 'yes' ? array('checked' => 'checked') : null)
			);

			$label = Widget::Label(__(
				'%s Case sensitive?', array(
				$input->generate()
			)));

			$settings->appendChild($label);

			$help = new XMLElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue(__('Treat upper case and lower case letters differently.'));

			$settings->appendChild($help);

		// Regexp? ------------------------------------------------------------

			$input = Widget::Input(
				"fields{$fieldnamePrefix}[{$handle}][is_regexp]{$fieldnamePostfix}", 'yes', 'checkbox',
				($data['is_regexp'] == 'yes' ? array('checked' => 'checked') : null)
			);

			$label = Widget::Label(__(
				'%s Regular expressions?', array(
				$input->generate()
			)));

			$settings->appendChild($label);

			$help = new XMLElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue(__(
				'Advanced matching with <a href="%s">Perl compatible regular expressions</a>.', array(
				SYMPHONY_URL . '/extension/expressionfield/documentation/'
			)));

			$settings->appendChild($help);
			$wrapper->appendChild($settings);
		}

	/*-------------------------------------------------------------------------
		Input:
	-------------------------------------------------------------------------*/

		public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null) {
			$field_id = $this->get('id');
			$status = self::__OK__;

			if (!is_array($data)) $data = array($data);

			if (empty($data)) return null;

			$result = array();

			$result = array(
				'value'		=> @$data['value'],
				'compiled'	=> null,
				'is_cased'	=> @$data['is_cased'],
				'is_regexp'	=> @$data['is_regexp']
			);

			if ($result['is_regexp'] == 'no') {
				$choices = preg_split('/\s*,\s*/', $result['value'], -1, PREG_SPLIT_NO_EMPTY);
				$expression = '';

				foreach ($choices as $index => $choice) {
					if ($index) $expression .= '|';

					$expression .= str_replace(
						'\\*', '.*?',
						preg_quote($choice, '/')
					);
				}

				$expression = "^{$expression}$";
			}

			else {
				$expression = str_replace('/', '\\/', $result['value']);
			}

			if ($result['is_cased'] == 'no') {
				$result['compiled'] = "/{$expression}/i";
			}

			else {
				$result['compiled'] = "/{$expression}/";
			}

			return $result;
		}

	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/

		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null){
			$element = new XMLElement($this->get('element_name'));
			$element->setAttribute('is-cased', $data['is_cased']);
			$element->setAttribute('is-regexp', $data['is_regexp']);
			$element->setAttribute('expression', General::sanitize($data['compiled']));

			$wrapper->appendChild($element);
		}

		public function prepareTableValue($data, XMLElement $link=NULL, $entry_id = null) {
			if (empty($data) or strlen(trim($data['value'])) == 0) return;

			return parent::prepareTableValue(
				array(
					'value' => General::sanitize($data['value'])
				), $link
			);
		}

	/*-------------------------------------------------------------------------
		Filtering:
	-------------------------------------------------------------------------*/

		public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false) {
			if (is_array($data)) {
				if ($andOperation) $data = implode('+', $data);
				else $data = implode(',', $data);
			}

			$field_id = $this->get('id');

			// Find matching entries:
			$entries = array();
			$rows = Symphony::Database()->fetch("
				SELECT
					f.entry_id,
					f.compiled
				FROM
					`tbl_entries_data_{$field_id}` AS f
			");

			foreach ($rows as $row) if (preg_match($row['compiled'], $data)) {
				$entries[] = $row['entry_id'];
			}

			if (empty($entries)) return false;

			$this->_key++;
			$data = implode(", ", $entries);
			$joins .= "
				LEFT JOIN
					`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
					ON (e.id = t{$field_id}_{$this->_key}.entry_id)
			";
			$where .= "
				AND t{$field_id}_{$this->_key}.entry_id IN ({$data})
			";

			return true;
		}
	}
