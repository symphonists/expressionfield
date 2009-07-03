<?php
	
	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	class FieldExpression extends Field {
		protected $_driver = null;
		public $_ignore = array();
		
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/
		
		public function __construct(&$parent) {
			parent::__construct($parent);
			
			$this->_name = 'Expression';
			$this->_required = true;
			$this->_driver = $this->_engine->ExtensionManager->create('expressionfield');
		}
		
		public function createTable() {
			$field_id = $this->get('id');
			
			return $this->_engine->Database->query("
				CREATE TABLE IF NOT EXISTS `tbl_entries_data_{$field_id}` (
					`id` INT(11) NOT NULL auto_increment,
					`entry_id` INT(11) UNSIGNED NOT NULL,
					`value` VARCHAR(255) DEFAULT NULL,
					`compiled` VARCHAR(255) DEFAULT NULL,
					`is_regexp` ENUM('yes', 'no') DEFAULT 'no',
					`is_cased` ENUM('yes', 'no') DEFAULT 'no',
					PRIMARY KEY  (`id`),
					KEY `entry_id` (`entry_id`)
				)
			");
		}
		
		public function canFilter() {
			return true;
		}
		
	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/
		
		public function displaySettingsPanel(&$wrapper, $errors = null) {
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
		
		public function displayPublishPanel(&$wrapper, $data = null, $error = null, $prefix = null, $postfix = null, $entry_id = null) {
			$this->_driver->addHeaders($this->_engine->Page);
			$handle = $this->get('element_name');
			$fieldname = "fields{$prefix}[{$handle}]{$postfix}";
			
		// Defaults -----------------------------------------------------------
			
			$wrapper->appendChild(Widget::Input(
				"fields{$prefix}[{$handle}][is_cased]{$postfix}", 'no', 'hidden'
			));
			$wrapper->appendChild(Widget::Input(
				"fields{$prefix}[{$handle}][is_regexp]{$postfix}", 'no', 'hidden'
			));
			
		// Expression ---------------------------------------------------------
			
			$label = Widget::Label(__('Expression'));
			$label->appendChild(Widget::Input(
				"fields{$prefix}[{$handle}][value]{$postfix}",
				General::sanitize($data['value'])
			));
			
			if (isset($error)) {
				$label = Widget::wrapFormElementWithError($label, $error);
			}
			
			$wrapper->appendChild($label);
			
			$help = new XMLElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue('Use <code>*</code> as a wild-card unless regular expressions are enabled.');
			
			$wrapper->appendChild($help);
			
		// Cased? -------------------------------------------------------------
			
			$settings = new XMLElement('div');
			
			$input = Widget::Input(
				"fields{$prefix}[{$handle}][is_cased]{$postfix}", 'yes', 'checkbox',
				($data['is_cased'] == 'yes' ? array('checked' => 'checked') : null)
			);
			
			$label = Widget::Label(__(
				'%s Case sensetive?', array(
				$input->generate()
			)));
			
			$settings->appendChild($label);
			
			$help = new XMLElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue(__('Treat upper case and lower case letters differently.'));
			
			$settings->appendChild($help);
			
		// Regexp? ------------------------------------------------------------
			
			$input = Widget::Input(
				"fields{$prefix}[{$handle}][is_regexp]{$postfix}", 'yes', 'checkbox',
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
				URL . '/symphony/extension/expressionfield/documentation/'
			)));
			
			$settings->appendChild($help);
			$wrapper->appendChild($settings);
		}
		
	/*-------------------------------------------------------------------------
		Input:
	-------------------------------------------------------------------------*/
		
		public function processRawFieldData($data, &$status, $simulate = false, $entry_id = null) {
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
				$expression = preg_quote($result['value'], '/');
				$expression = str_replace('\\*', '.*?', $expression);
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
		
		public function appendFormattedElement(&$wrapper, $data) {
			$element = new XMLElement($this->get('element_name'));
			$element->setAttribute('is-cased', $data['is_cased']);
			$element->setAttribute('is-regexp', $data['is_regexp']);
			$element->setAttribute('expression', General::sanitize($data['compiled']));
			
			$wrapper->appendChild($element);
		}
		
		public function prepareTableValue($data, XMLElement $link = null) {
			if (empty($data) or strlen(trim($data['value'])) == 0) return;
			
			return parent::prepareTableValue(
				array(
					'value'		=> General::sanitize($data['compiled'])
				), $link
			);
		}
		
	/*-------------------------------------------------------------------------
		Filtering:
	-------------------------------------------------------------------------*/
		
		public function buildDSRetrivalSQL($data, &$joins, &$where, $and = false) {
			if (is_array($data)) {
				if ($and) $data = implode('+', $data);
				else $data = implode(',', $data);
			}
			
			$database = Frontend::instance()->Database;
			$field_id = $this->get('id');
			
			// Find matching entries:
			$entries = array();
			$rows = $database->fetch("
				SELECT
					f.entry_id,
					f.compiled
				FROM
					`tbl_entries_data_{$field_id}` AS f
			");
			
			foreach ($rows as $row) if (preg_match($row['compiled'], $data)) {
				$entries[] = $row['entry_id'];
			}
			
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
	
?>
