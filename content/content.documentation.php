<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	class contentExtensionExpressionFieldDocumentation extends AdministrationPage {
		public function __viewIndex() {
			$this->setTitle("Symphony &ndash; " . __("Expression Field Documentation"));
			$this->appendSubheading(__("Documentation"));

		// Documentation ------------------------------------------------------

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Perl Compatible Regular Expressions')));

			$fieldset->appendChild(new XMLElement('p', __('
				Perl Compatible Regular Expressions, commonly known as PCRE,
				are a means for identifying strings of text of interest.
			')));

			$fieldset->appendChild(new XMLElement('h3', __('Resources')));

			$list = new XMLElement('ul');

			$list->appendChild(new XMLElement('li', '
				<a href="http://www.regular-expressions.info/tutorial.html">' . __('Learn How to Use Regular Expressions') . '</a>
			'));
			$list->appendChild(new XMLElement('li', '
				<a href="http://en.wikipedia.org/wiki/Pcre">' . __('PCRE on Wikipedia') . '</a>
			'));
			$list->appendChild(new XMLElement('li', '
				<a href="http://au.php.net/manual/en/reference.pcre.pattern.syntax.php">' . __('A technical description of PCRE') . '</a>
			'));

			$fieldset->appendChild($list);

			$this->Form->appendChild($fieldset);
		}
	}
