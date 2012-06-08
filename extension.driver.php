<?php

	class Extension_ExpressionField extends Extension {
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function uninstall() {
			return Symphony::Database()->query("DROP TABLE `tbl_fields_expression`");
		}

		public function install() {
			return Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_expression` (
					`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`field_id` INT(11) UNSIGNED NOT NULL,
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			");
		}

	/*-------------------------------------------------------------------------
		Utilites:
	-------------------------------------------------------------------------*/

		public static function appendAssets() {
			if(class_exists('Administration')
				&& Administration::instance() instanceof Administration
				&& Administration::instance()->Page instanceof HTMLPage
			) {
				Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/expressionfield/assets/expressionfield.publish.css', 'screen', 10000, false);
			}
		}

	}
