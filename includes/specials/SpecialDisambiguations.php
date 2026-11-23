<?php
/**
 * Implements Special:Disambiguations
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup SpecialPage
 */

/**
 * A special page that lists pages containing links to disambiguations pages
 *
 * @ingroup SpecialPage
 */
class DisambiguationsPage extends PageQueryPage {

	function __construct( $name = 'Disambiguations' ) {
		parent::__construct( $name );
	}

	function isExpensive() { return true; }
	function isSyndicated() { return false; }

	function getPageHeader() {
		return $this->msg( 'disambiguations-text' )->parseAsBlock();
	}

	function getQueryInfo() {
		// p1 is the disambiguation page, p2 a content page linking to it
		return array (
			'tables' => array( 'p1' => 'page', 'page_props', 'pagelinks', 'p2' => 'page' ),
			'fields' => array( 'p1.page_namespace AS namespace',
					'p1.page_title AS title',
					'pl_from AS value' ),
			'conds' => array( 'pp_page = p1.page_id',
					'pp_propname' => Disambiguation::PROP,
					'pl_namespace = p1.page_namespace',
					'pl_title = p1.page_title',
					'p2.page_id = pl_from',
					'p2.page_namespace' => MWNamespace::getContentNamespaces() )
		);
	}

	function getOrderFields() {
		return array( 'p1.page_namespace', 'p1.page_title', 'pl_from' );
	}

	function sortDescending() {
		return false;
	}

	/**
	 * Fetch links and cache their existence
	 *
	 * @param $db DatabaseBase
	 * @param $res
	 */
	function preprocessResults( $db, $res ) {
		$batch = new LinkBatch;
		foreach ( $res as $row ) {
			$batch->add( $row->namespace, $row->title );
		}
		$batch->execute();

		// Back to start for display
		if ( $db->numRows( $res ) > 0 ) {
			// If there are no rows we get an error seeking.
			$db->dataSeek( $res, 0 );
		}
	}

	function formatResult( $skin, $result ) {
		$title = Title::newFromID( $result->value );
		$dp = Title::makeTitle( $result->namespace, $result->title );

		$from = Linker::link( $title );
		$edit = Linker::link( $title, $this->msg( 'parentheses', $this->msg( 'editlink' )->text() )->escaped(),
			array(), array( 'redirect' => 'no', 'action' => 'edit' ) );
		$arr  = $this->getLanguage()->getArrow();
		$to   = Linker::link( $dp );

		return "$from $edit $arr $to";
	}
}
