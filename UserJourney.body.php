<?php

class UserJourney {

	static $referers = null;
	
	/*
	 * Future Hooks to implement

		 for completing a review
		 Since there is no hook or event related to reviewing a watched page revision,
		 I will use two hooks to determine this:  One early in page load; one late.
		 public static function onArticlePageDataBefore( $article, $fields){}
		 $wgHooks['ArticlePageDataBefore'][] = 'MyExtensionHooks::onArticlePageDataBefore';

		 onAfterFinalPgeOutput( $output)
		 AfterFinalPageOutput
		
		for badge Necromancer
		public static function onArticleUndelete( Title $title, $create, $comment, $oldPageID){}
		$wgHooks['Article'Undelete'][] = 'MyExtensionHooks::onArticleUndelete';

		for watching an article (need to prevent watch/unwatch endless cycle for points)
		public static function onWatchArticleComplete( $user, $article){}
		$wgHooks['WatchArticleComplete'][] = 'MyExtensionHook::onWatchArticleComplete';

		for completing file upload
		public static function onUploadComplete ( &$image){}
		$wgHooks['UploadComplete'][] = 'MyExtensionHooks::onUploadComplete';

		Add starter badge system (small values to test at first):
		* New Editor for first edit
		* Editor for 10 edits
		* Contributor for 20 edits

	 *
	 *
	 */


	// 1 of the earliest hooks in page load
	public static function onArticlePageDataBefore( $article, $fields ){



		return true;

	}


	public static function onBeforeInitialize( &$title, &$article, &$output, &$user, $request, $mediaWiki ) {
		
		$output->enableClientCache( false );
		$output->addMeta( 'http:Pragma', 'no-cache' );

		global $wgRequestTime, $egUJCurrentHit;

		//Logic to only reward 1st view of a given page per day
		$ts = date("Ymd000000", time() );
		$pid = $title->getArticleId();
		$usr = $user->getName();
		// $articleNS = $article->showNamespaceHeader();
		$usr = $user->getName();

		$dbr = wfGetDB( DB_SLAVE );

		$userUnreviewedPages = $dbr->select(
			array('wl' => 'watchlist'),
			array(
				"wl.wl_user AS wl_user",
				"wl.wl_namespace AS wl_namespace",
				"wl.wl_title AS wl_title",
				"wl.wl_notificationtimestamp AS wl_notificationTS",
			),
			array(
				"wl.wl_user" => $usr,
				"wl.wl_namespace" => $articleNS,
				"wl.wl_title" => $title,
				"wl.wl_notificationtimestamp" => !NULL,
			),
			__METHOD__,
			array(
				// "LIMIT" => "1",
			),
			null // join conditions
		);

		$numUserUnreviewedPages = $dbr->numRows( $userUnreviewedPages );
		if ($numUserUnreviewedPages > 0 ) {
			print_r("At least 1");
		}

		$res = $dbr->select(
			array('uj' => 'userjourney'),
			array(
				"uj.page_id AS page_id",
				"uj.hit_timestamp AS hit_timestamp",
				"uj.user_name AS user_name",
				"uj.page_action AS page_action",
			),
			array(
				"uj.hit_timestamp>$ts",
				"uj.user_name" => $usr,
				"uj.page_id=$pid",
				"uj.page_action" => "View page",
			),
			__METHOD__,
			array(
				"LIMIT" => "1",
			),
			null // join conditions
		);

		if( $dbr->fetchRow( $res ) ) {
			$user_points = 0;
		} else $user_points = 1;

		$now = time();
		$hit = array(
			'user_points' => $user_points, //Eventually this will be a variable based on action specifics
			'page_id' => $title->getArticleId(),
			'page_name' => $title->getFullText(),
			'user_name' => $user->getName(),
			'hit_timestamp' => wfTimestampNow(),
			
			'hit_year' => date('Y',$now),
			'hit_month' => date('m',$now),
			'hit_day' => date('d',$now),
			'hit_hour' => date('H',$now),
			'hit_weekday' => date('w',$now), // 0' => sunday, 1=monday, ... , 6=saturday

			'page_action' => "View page", //$request->getVal( 'action' ),
			'oldid' => $request->getVal( 'oldid' ),
			'diff' => $request->getVal( 'diff' ),

		);

		$hit['referer_url'] = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null;
		$hit['referer_title'] = self::getRefererTitleText( $request->getVal('refererpage') );

		$egUJCurrentHit = $hit;

		return true;

	}


	// After save page request has been completed
	public static function onPageContentSaveComplete( $article, $user, $content, $summary, $isMinor, $isWatch, 
		$section, $flags, $revision, $status, $baseRevId ) {

		global $wgRequestTime, $egUJCurrentHit;

		//Logic to only reward 1st save of a given page per day
		$ts = date("Ymd000000", time() );
		$ptitle = $article->getTitle();
		$pid = $ptitle->getArticleId();
		$usr = $user->getName();

		$dbr = wfGetDB( DB_SLAVE );

		$userHasSavedThisPageToday = $dbr->select(
			array('uj' => 'userjourney'),
			array(
				"uj.page_id AS page_id",
				"uj.hit_timestamp AS hit_timestamp",
				"uj.user_name AS user_name",
				"uj.page_action AS page_action",
			),
			array(
				"uj.hit_timestamp>$ts",
				"uj.user_name" => $usr,
				"uj.page_id=$pid",
				"uj.page_action" => "Edit page",
			),
			__METHOD__,
			array(
				"LIMIT" => "1",
			),
			null // join conditions
		);

		//add new $dbr->select query for first edit and award 10 bonus points, then do one for 10 edits, etc
		$listOfUserRevisions = $dbr->select(
			array('uj' => 'userjourney'),
			array(
				"uj.page_id AS page_id",
				"uj.hit_timestamp AS hit_timestamp",
				"uj.user_name AS user_name",
				"uj.page_action AS page_action",
			),
			array(
				//"uj.hit_timestamp>$ts",
				"uj.user_name" => $usr,
				//"uj.page_id=$pid",//need to calc unique page saves per day?
				"uj.page_action" => "Edit page",
			),
			__METHOD__,
			array(
				//"LIMIT" => "1",
			),
			null // join conditions
		);
		$numberOfUserRevisions = $dbr->numRows( $listOfUserRevisions );

		if( $dbr->numRows( $userHasSavedThisPageToday ) == 0 ) {
			$user_points = 3; 
		} else if ( $numberOfUserRevisions == 10 ) {
			$user_points = 10;
			$user_badge = "10th Edit";
		} else $user_points = 0;

		$now = time();
		$hit = array(
			'user_points' => $user_points, //Eventually this will be a variable based on action specifics
			'user_badge' => $user_badge,
			'page_id' => $pid,
			'page_name' => $article->getTitle(),
			'user_name' => $user->getName(),
			'hit_timestamp' => wfTimestampNow(),
			
			'hit_year' => date('Y',$now),
			'hit_month' => date('m',$now),
			'hit_day' => date('d',$now),
			'hit_hour' => date('H',$now),
			'hit_weekday' => date('w',$now), // 0' => sunday, 1=monday, ... , 6=saturday

			'page_action' => "Edit page", 

		);

		$egUJCurrentHit = $hit;

		self::recordInDatabase();

		return true;

	}

	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ){

		

		return false;

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
		// $updater->addExtensionField(
			// $userjourneyTable
			// 'response_time',
			// "$schemaDir/patch-1-response-time.sql"
		// );

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
