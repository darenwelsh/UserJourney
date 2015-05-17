<?php

class UserJourney {

	static $referers = null;
	
	/**
	 *
	 *
	 *
	 **/


	// After save page request has been completed
	public static function saveComplete( $article, $user, $content, $summary, $isMinor, $isWatch, 
		$section, $flags, $revision, $status, $baseRevId ) {
		
		// $output->enableClientCache( false );
		// $output->addMeta( 'http:Pragma', 'no-cache' );

		global $wgRequestTime, $egUJCurrentHit;

		$now = time();
		$hit = array(
			'user_points' => 3, //Eventually this will be a variable based on action specifics
			'page_id' => "1",//$title->getArticleId(),
			'page_name' => "test name", //$title->getFullText(),
			'user_name' => $user->getName(),
			'hit_timestamp' => wfTimestampNow(),
			
			'hit_year' => date('Y',$now),
			'hit_month' => date('m',$now),
			'hit_day' => date('d',$now),
			'hit_hour' => date('H',$now),
			'hit_weekday' => date('w',$now), // 0' => sunday, 1=monday, ... , 6=saturday

			// 'page_action' => NULL //$request->getVal( 'action' ),
			// 'oldid' => NULL //$request->getVal( 'oldid' ),
			// 'diff' => NULL //$request->getVal( 'diff' ),

		);

		// $hit['referer_url'] = NULL //isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null;
		// $hit['referer_title'] = NULL //self::getRefererTitleText( $request->getVal('refererpage') );

		// @TODO: this is by no means the ideal way to do this...but it'll do for now...
		$egUJCurrentHit = $hit;

		self::recordInDatabase;
		self::updateDatabase;

		// return true;

	}



	public static function updateTable( &$title, &$article, &$output, &$user, $request, $mediaWiki ) {
		
		$output->enableClientCache( false );
		$output->addMeta( 'http:Pragma', 'no-cache' );

		global $wgRequestTime, $egUJCurrentHit;

		$now = time();
		$hit = array(
			'user_points' => 1, //Eventually this will be a variable based on action specifics
			'page_id' => $title->getArticleId(),
			'page_name' => $title->getFullText(),
			'user_name' => $user->getName(),
			'hit_timestamp' => wfTimestampNow(),
			
			'hit_year' => date('Y',$now),
			'hit_month' => date('m',$now),
			'hit_day' => date('d',$now),
			'hit_hour' => date('H',$now),
			'hit_weekday' => date('w',$now), // 0' => sunday, 1=monday, ... , 6=saturday

			'page_action' => $request->getVal( 'action' ),
			'oldid' => $request->getVal( 'oldid' ),
			'diff' => $request->getVal( 'diff' ),

		);

		$hit['referer_url'] = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null;
		$hit['referer_title'] = self::getRefererTitleText( $request->getVal('refererpage') );

		// @TODO: this is by no means the ideal way to do this...but it'll do for now...
		$egUJCurrentHit = $hit;

		return true;

	}
		
	public static function recordInDatabase (  ) { // could have param &$output
		global $wgRequestTime, $egUJCurrentHit;

		// calculate response time now, in the last hook (that I know of).
		$egUJCurrentHit['response_time'] = round( ( microtime( true ) - $wgRequestTime ) * 1000 );
		
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'userjourney',
			$egUJCurrentHit,
			__METHOD__
		);
		return true;
	}

	public static function updateDatabase( DatabaseUpdater $updater ) {
		global $wgDBprefix;

		$userJourneyTable = $wgDBprefix . 'userjourney';
		$schemaDir = __DIR__ . '/schema';
		
		$updater->addExtensionTable(
			$userJourneyTable,
			"$schemaDir/UserJourney.sql"
		);
		$updater->addExtensionField(
			$userjourneyTable
			// 'response_time',
			// "$schemaDir/patch-1-response-time.sql"
		);

		return true;
	}
	
	/**
	 *	See WebRequest::getPathInfo() for ideas/info
	 *  Make better use of: $wgScript, $wgScriptPath, $wgArticlePath;
	 *
	 *  Other recommendations:
	 *    wfSuppressWarnings();
	 *    $a = parse_url( $url );
	 *    wfRestoreWarnings();
	 **/
	public static function getRefererTitleText ( $refererpage=null ) {
		
		global $wgScriptPath;
	
		if ( $refererpage )
			return $refererpage;
		else if ( ! isset($_SERVER["HTTP_REFERER"]) )
			return null;
	
		$wikiBaseUrl = WebRequest::detectProtocol() . '://' . $_SERVER['HTTP_HOST'] . $wgScriptPath;
		
		// if referer URL starts 
		if ( strpos($_SERVER["HTTP_REFERER"], $wikiBaseUrl) === 0 ) {
			
			$questPos = strpos( $_SERVER['HTTP_REFERER'], '?' );
			$hashPos = strpos( $_SERVER['HTTP_REFERER'], '#' );
			
			if ($hashPos !== false) {
				$queryStringLength = $hashPos - $questPos;
				$queryString = substr($_SERVER['HTTP_REFERER'], $questPos+1, $queryStringLength);
			} else {
				$queryString = substr($_SERVER['HTTP_REFERER'], $questPos+1);
			}
						
			$query = array();
			parse_str( $queryString, $query );

			return isset($query['title']) ? $query['title'] : false;
		
		}
		else
			return false;
		
	}
	



}
