<?php
/**
 * Special:DisambiguationPages: every page marked with __DISAMBIG__.
 * GamerWiki backport from the Disambiguator extension.
 *
 * @file
 * @ingroup SpecialPage
 */
class DisambiguationPagesPage extends PageQueryPage {

	function __construct( $name = 'DisambiguationPages' ) {
		parent::__construct( $name );
	}

	function isExpensive() {
		return true;
	}

	function isSyndicated() {
		return false;
	}

	function getPageHeader() {
		return $this->msg( 'disambiguationpages-text' )->parseAsBlock();
	}

	function getQueryInfo() {
		return array(
			'tables' => array( 'page', 'page_props' ),
			'fields' => array(
				'page_namespace AS namespace',
				'page_title AS title',
				'page_title AS value'
			),
			'conds' => array(
				'page_id = pp_page',
				'pp_propname' => Disambiguation::PROP
			)
		);
	}

	function getOrderFields() {
		return array( 'page_namespace', 'page_title' );
	}

	function sortDescending() {
		return false;
	}
}
