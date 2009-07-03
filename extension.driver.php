<?php
	
	class Extension_ExpressionField extends Extension {
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/
		
		public function about() {
			return array(
				'name'			=> 'Field: Expression',
				'version'		=> '1.0.1',
				'release-date'	=> '2009-07-03',
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://pixelcarnage.com/',
					'email'			=> 'rowan@pixelcarnage.com'
				),
				'description' => 'Define simple expressions and test values against them in a data source.'
			);
		}
		
		public function uninstall() {
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_expression`");
		}
		
		public function install() {
			$this->_Parent->Database->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_expression` (
					`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`field_id` INT(11) UNSIGNED NOT NULL,
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				)
			");
			
			return true;
		}
		
	/*-------------------------------------------------------------------------
		Utilites:
	-------------------------------------------------------------------------*/
		
		protected $addedHeaders = false;
		
		public function addHeaders($page) {
			if (!$this->addedHeaders) {
				$page->addStylesheetToHead(URL . '/extensions/expressionfield/assets/publish.css', 'screen', 10262810);
				
				$this->addedHeaders = true;
			}
		}
	}
	
?>