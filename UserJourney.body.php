<?php

class UserJourney {

	static $referers = null;
	
	/*
	* Short issues
	* 
	* Need to address multiple hits displayed on page view
	* 
	* 
	 * Future Hooks to implement
		
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
	// public static function onArticlePageDataBefore( $article, $fields ){



	// 	return true;

	// }


	public static function onBeforeInitialize( &$title, &$article, &$output, &$user, $request, $mediaWiki ) {
// file_put_contents("/var/www/html/MWHooks.txt", "onBeforeInitializeBegin\n", FILE_APPEND);

		$output->enableClientCache( false );
		$output->addMeta( 'http:Pragma', 'no-cache' );

		global $wgRequestTime, 
			$egCurrentHit, //data about this page load
			$egNumUserUnreviewedPages, //number of unreviewed pages upon begin page load
			$egUserid,
			$egPageSave,
			$egRecordedInDB;
			//consider comparing before-after table for edge case of new page added to wl in page load
			
		//escape if this function is called following the PageContentSaveComplete hook
		// if ( $egPageSave == true ) {
		// 	return true;
		// }

		//Temp vars contained in $egCurrentHit upon completion of this function
		$UserPoints = 0; // init user points
		$UserActions = ""; // init blank
		$UserBadges = ""; // init blank

		//Logic to only reward 1st view of a given page per day
		$ts = date("Ymd000000", time() );
		$pid = $title->getArticleId();
		$username = $user->getName();
		// $articleNS = $article->showNamespaceHeader();
		$egUserid = $user->getId();

		$dbr = wfGetDB( DB_SLAVE );

		$egNumUserUnreviewedPages = $dbr->selectRow(
		// $userUnreviewedPages = $dbr->select(
			array('wl' => 'watchlist'),
			array(
				"COUNT(*) AS num_unreviewed_pages",
				// "wl.wl_user AS wl_user",
				// "wl.wl_namespace AS wl_namespace",
				// "wl.wl_title AS wl_title",
				// "wl.wl_notificationtimestamp AS wl_notificationTS",
			),
			array(
				"wl.wl_user" => $egUserid,
				// "wl.wl_namespace" => NS_MAIN,//$articleNS,
				//"wl.wl_title" => "Page_2",//$title,
				"wl.wl_notificationtimestamp IS NOT NULL",
			),
			__METHOD__,
			null,//OPTIONS
			// array(
			// 	// "LIMIT" => "1",
			// ),
			null // join conditions
		);
		// $egNumUserUnreviewedPages = $dbr->selectRow( $userUnreviewedPages );

		// $numUserUnreviewedPages = $dbr->numRows( $egUserUnreviewedPages );
		// if ( $numUserUnreviewedPages > 0 ) {
		// 	$UserPoints += 5; //indicate user has un-reviewed page, move reward to later hook
		// }

		$res = $dbr->select(
			array('uj' => 'userjourney'),
			array(
				"uj.page_id AS page_id",
				"uj.hit_timestamp AS hit_timestamp",
				"uj.user_name AS user_name",
				"uj.user_actions AS user_actions",
			),
			array(
				"uj.hit_timestamp>$ts",
				"uj.user_name" => $username,
				"uj.page_id=$pid",
				"uj.user_actions" => "View",
			),
			__METHOD__,
			array(
				"LIMIT" => "1",
			),
			null // join conditions
		);

		if ( $UserActions != "" ) {
			$UserActions = $UserActions . ", ";
		}
		$UserActions = $UserActions . "View";

		if( $dbr->fetchRow( $res ) ) {
			//Leave $egUserPoints at current value
		} else {
			$UserPoints += 1;
		}

		$now = time();
		$hit = array(
			'user_points' => $UserPoints, //Eventually this will be a variable based on action specifics
			'user_badges' => $UserBadges,
			'user_actions' => $UserActions,
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

		if ( $egCurrentHit ) {
			file_put_contents("/var/www/html/MWHooks.txt", "egCurrentHit true\n", FILE_APPEND);
			//Need to handle if this event is repeated (like redirect after save)
		} else {
			file_put_contents("/var/www/html/MWHooks.txt", "egCurrentHit false\n", FILE_APPEND);
			$egCurrentHit = $hit;
		}


		// self::recordInDatabase(); //remove this after linking flow of hooks

// file_put_contents("/var/www/html/MWHooks.txt", "onBeforeInitializeEnd\n", FILE_APPEND);
		return true;

	}

// public static function onArticleUpdateBeforeRedirect( $article, &$sectionanchor, &$extraq ) {
// 	global $egCurrentHit;

// 	$egCurrentHit['user_actions'] = $egCurrentHit['user_actions'] . "Test";

// 	return true;
// }


	// After save page request has been completed
	public static function onPageContentSaveComplete( $article, $user, $content, $summary, 
		$isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId ) {
// file_put_contents("/var/www/html/MWHooks.txt", "onPageContentSaveComplete\n", FILE_APPEND);

		global $wgRequestTime, $egCurrentHit, $egPageSave;

		$egPageSave = true;

		//Logic to only reward 1st save of a given page per day
		$ts = date("Ymd000000", time() );
		$ptitle = $article->getTitle();
		$pid = $ptitle->getArticleId();
		$username = $user->getName();

		if ( $egCurrentHit['user_actions'] != "" ) {
			$egCurrentHit['user_actions'] = $egCurrentHit['user_actions'] . ", ";
		}
		$egCurrentHit['user_actions'] =  "SaveEdit";

		$dbr = wfGetDB( DB_SLAVE );

		$userHasSavedThisPageToday = $dbr->select(
			array('uj' => 'userjourney'),
			array(
				"uj.page_id AS page_id",
				"uj.hit_timestamp AS hit_timestamp",
				"uj.user_name AS user_name",
				"uj.user_actions AS user_actions",
			),
			array(
				"uj.hit_timestamp>$ts",
				"uj.user_name" => $username,
				"uj.page_id=$pid",
				"uj.user_actions" => "SaveEdit",
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
				"uj.user_actions AS user_actions",
			),
			array(
				//"uj.hit_timestamp>$ts",
				"uj.user_name" => $username,
				//"uj.page_id=$pid",//need to calc unique page saves per day?
				"uj.user_actions" => "SaveEdit",
			),
			__METHOD__,
			array(
				//"LIMIT" => "1",
			),
			null // join conditions
		);
		$numberOfUserRevisions = $dbr->numRows( $listOfUserRevisions );

		if( $dbr->numRows( $userHasSavedThisPageToday ) == 0 ) {
			$egCurrentHit['user_points'] += 3; 
		} else if ( $numberOfUserRevisions == 10 ) {
			$egCurrentHit['user_points'] += 10;
			$user_badge = $user_badge . "10th Edit";
			$egCurrentHit['user_badges'] = $egCurrentHit['user_badges'] . "10th Edit";
		}

		//Unsure why I need to call this here when it seems AfterFinalPageOutput is called after this hook
		self::recordInDatabase();

		return true;

	}

	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ){

// file_put_contents("/var/www/html/MWHooks.txt", "onBeforePageDisplay\n", FILE_APPEND);

		global $egCurrentHit, $egNumUserUnreviewedPages, $egUserid;

		//Logic to only reward 1st save of a given page per day
		// $ts = date("Ymd000000", time() );
		// $ptitle = $article->getTitle();
		// $pid = $ptitle->getArticleId();
		// $username = $user->getName();

		$dbr = wfGetDB( DB_SLAVE );

		$deltaNumUserUnreviewedPages = $dbr->selectRow(
			array('wl' => 'watchlist'),
			array( 
				"COUNT(*) AS num_unreviewed_pages",
			),
			array(
				"wl.wl_user" => $egUserid,
				"wl.wl_notificationtimestamp IS NOT NULL",
			),
			__METHOD__,
			//OPTIONS
			null,
			null // join conditions
		);

		if ( $deltaNumUserUnreviewedPages != $egNumUserUnreviewedPages ) {
			$egCurrentHit['user_points'] += 15; //indicate user has un-reviewed page, move reward to later hook
			if ( $egCurrentHit['user_actions'] != "" ) {
				$egCurrentHit['user_actions'] = $egCurrentHit['user_actions'] . ", ";
			}
			$egCurrentHit['user_actions'] = $egCurrentHit['user_actions'] . "Review";

		}

		// self::recordInDatabase();

		return true;

	}
		
	public static function recordInDatabase (  ) { // could have param &$output
		global $wgRequestTime, $egCurrentHit, $egRecordedInDB;

// file_put_contents("/var/www/html/MWHooks.txt", "recordInDatabase\n", FILE_APPEND);

		// calculate response time now, in the last hook (that I know of).
		$egCurrentHit['response_time'] = round( ( microtime( true ) - $wgRequestTime ) * 1000 );
		
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'userjourney',
			$egCurrentHit,
			__METHOD__
		);

		//Annunciate to user if they earned points or badge
		$alertPoints = $egCurrentHit['user_points'];
		$alertBadges = $egCurrentHit['user_badges'];
		if ( $alertPoints > 0 || $alertBadges != "" ){
			$alertMessage = $alertMessage . "Awesome!";
		}
		if ( $alertPoints > 0 ){
			$alertMessage = $alertMessage . "\\nYou got $alertPoints points!";
		}
		if ( $alertBadges != "" ){
			$alertMessage = $alertMessage . "\\nYou got the $alertBadges badge!";
		}
		if ( $alertMessage != "" ){
			echo "<script type='text/javascript'>alert('$alertMessage')</script>";
		}
		// echo "<script type='text/javascript'>alert('Test $alertMessage')</script>";
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
