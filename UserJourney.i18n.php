<?php
/**
 * This is a backwards-compatibility shim manually created by James Montalvo
 *
 * Beginning with MediaWiki 1.23, translation strings are stored in json files,
 * and the EXTENSION.i18n.php file only exists to provide compatibility with
 * older releases of MediaWiki. For more information about this migration, see:
 * https://www.mediawiki.org/wiki/Requests_for_comment/Localisation_format
 *
 * This shim maintains compatibility back to MediaWiki 1.17.
 */

$messages = array();
if ( !function_exists( 'wfJsonI18nShimf2b01d837887d3daWiretap' ) ) {
	function wfJsonI18nShimf2b01d837887d3daWiretap( $cache, $code, &$cachedData ) {
		$codeSequence = array_merge( array( $code ), $cachedData['fallbackSequence'] );
		foreach ( $codeSequence as $csCode ) {
			$fileName = dirname( __FILE__ ) . "/i18n/$csCode.json";
			if ( is_readable( $fileName ) ) {
				$data = FormatJson::decode( file_get_contents( $fileName ), true );
				foreach ( array_keys( $data ) as $key ) {
					if ( $key === '' || $key[0] === '@' ) {
						unset( $data[$key] );
					}
				}
				$cachedData['messages'] = array_merge( $data, $cachedData['messages'] );
			}

			$cachedData['deps'][] = new FileDependency( $fileName );
		}
		return true;
	}

	$GLOBALS['wgHooks']['LocalisationCacheRecache'][] = 'wfJsonI18nShimf2b01d837887d3daWiretap';
}