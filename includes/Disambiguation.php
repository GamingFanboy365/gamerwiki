<?php
/**
 * Disambiguation pages, marked with the __DISAMBIG__ behavior switch.
 *
 * GamerWiki backport of the core of the Disambiguator extension
 * (MediaWiki 1.21+): a page whose wikitext, or any template it uses,
 * contains __DISAMBIG__ gets the 'disambiguation' page property.
 * That property drives Special:DisambiguationPages,
 * Special:Disambiguations, the exclusions in Special:LonelyPages and
 * Special:ShortPages, and the "mw-disambig" class on links.
 *
 * @file
 */
class Disambiguation {

	/** Page property set by __DISAMBIG__ */
	const PROP = 'disambiguation';

	/**
	 * Add a LEFT JOIN on page_props to a QueryPage query so that
	 * disambiguation pages are left out of the results.
	 *
	 * @param $query array QueryPage::getQueryInfo() style array, using
	 *   an unaliased 'page' table
	 * @return array
	 */
	public static function excludeFromQuery( array $query ) {
		$query['tables'][] = 'page_props';
		$query['conds'][] = 'pp_page IS NULL';
		$query['join_conds']['page_props'] = array( 'LEFT JOIN', array(
			'pp_page = page_id',
			'pp_propname' => self::PROP
		) );
		return $query;
	}

	/**
	 * Add the "mw-disambig" class to links pointing at disambiguation pages.
	 *
	 * @param $pageIds array page_id => prefixed DB key
	 * @param $colours array prefixed DB key => link class(es), modified
	 */
	public static function addLinkClasses( array $pageIds, array &$colours ) {
		if ( !$pageIds ) {
			return;
		}
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			'page_props',
			'pp_page',
			array( 'pp_page' => array_keys( $pageIds ), 'pp_propname' => self::PROP ),
			__METHOD__
		);
		foreach ( $res as $row ) {
			$pdbk = $pageIds[$row->pp_page];
			if ( isset( $colours[$pdbk] ) && strpos( $colours[$pdbk], 'mw-disambig' ) !== false ) {
				continue;
			}
			$colours[$pdbk] = trim( ( isset( $colours[$pdbk] ) ? $colours[$pdbk] : '' ) . ' mw-disambig' );
		}
	}
}
