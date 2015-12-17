<?php


class UserJourney {

	static $referers = null;





		// To get hooks on a page load:

		// global $hookLogFile;
		// if ( ! isset( $hookLogFile ) ) {
		// 	$hookLogFile = __DIR__ . "/../hooklog/" . date( "Ymd_His", time() ) . "_" . rand() . ".txt";
		// 	file_put_contents( $hookLogFile, $_SERVER["REQUEST_URI"] . "\n", FILE_APPEND );
		// }
		// file_put_contents( $hookLogFile, "$event\n", FILE_APPEND );







	// 1 of the earliest hooks in page load
	// public static function onArticlePageDataBefore( $article, $fields ){



	// 	return true;

	// }





	public static function onBeforeInitialize( &$title, &$article, &$output, &$user, $request, $mediaWiki ) {

		$output->enableClientCache( false );
		$output->addMeta( 'http:Pragma', 'no-cache' );

		global $wgRequestTime,
			$egCurrentHit, //data about this page load
			$egUserUnreviewedPages, //number of unreviewed pages upon begin page load
			$egUserid,
			$egPageSave,
			$egRecordedInDB;
			//consider comparing before-after table for edge case of new page added to wl in page load

		//maybe use this method to preclude 2x view logging
		//escape if this function is called following the PageContentSaveComplete hook
		// if ( $egPageSave == true ) {
		// 	return true;
		// }

		//Temp vars contained in $egCurrentHit upon completion of this function
		$UserPoints = 0; // init user points
		$UserActions = ""; // init blank
		$UserBadges = ""; // init blank

		//Logic to only reward 1st view of a given page per day
		$ts = date("Ymd000000", time() ); //rename to $dayStart
		$pid = $title->getArticleId();
		$username = $user->getName();
		// $articleNS = $article->showNamespaceHeader();
		$egUserid = $user->getId();

		$dbr = wfGetDB( DB_SLAVE );

		$egUserUnreviewedPages = $dbr->selectRow(
			array('wl' => 'watchlist'),
			array(
				"COUNT(*) AS number",
				// "wl.wl_user AS wl_user",
			),
			array(
				"wl.wl_user" => $egUserid,
				// "wl.wl_namespace" => NS_MAIN,//$articleNS,
				"wl.wl_notificationtimestamp IS NOT NULL",
			),
			__METHOD__,
			null,
			null
		);

		$userViewsOfThisPageToday = $dbr->selectRow(
			array('uj' => 'userjourney'),
			array(
				"COUNT(*) AS number",
			),
			array(
				"uj.hit_timestamp>$ts",
				"uj.user_name" => $username,
				"uj.page_id=$pid",
				"uj.user_actions" => "View",
				// "uj.user_actions LIKE %View%",
			),
			__METHOD__,
			array(
				"LIMIT" => "1",
			),
			null // join conditions
		);
		$numUserViewsOfThisPageToday = $userViewsOfThisPageToday->number;

		if ( $UserActions != "" ) {
			$UserActions = $UserActions . ", ";
		}
		$UserActions = $UserActions . "View";

		if( $numUserViewsOfThisPageToday > 0 ) {
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
			// file_put_contents("/var/www/html/MWHooks.txt", "egCurrentHit true\n", FILE_APPEND);
			//Need to handle if this event is repeated (like redirect after save)
		} else {
			// file_put_contents("/var/www/html/MWHooks.txt", "egCurrentHit false\n", FILE_APPEND);
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

		// echo "<script type='text/javascript'>alert('test')</script>";
		$egCurrentHit['user_badges'] = "Test";
		//Unsure why I need to call this here when it seems AfterFinalPageOutput is called after this hook
		self::recordInDatabase();

		return true;

	}

	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ){

		// file_put_contents("/var/www/html/MWHooks.txt", "onBeforePageDisplay\n", FILE_APPEND);

		global $egCurrentHit, $egUserUnreviewedPages, $egUserid;

		//Logic to only reward 1st save of a given page per day
		// $ts = date("Ymd000000", time() );
		// $ptitle = $article->getTitle();
		// $pid = $ptitle->getArticleId();
		// $username = $user->getName();

		$dbr = wfGetDB( DB_SLAVE );

		$deltaUserUnreviewedPages = $dbr->selectRow(
			array('wl' => 'watchlist'),
			array(
				"COUNT(*) AS number",
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

		if ( $deltaUserUnreviewedPages->number != $egUserUnreviewedPages->number ) {
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
		//Issue: Doesn't annunciate on page save earning points
		$alertPoints = $egCurrentHit['user_points'];
		$alertBadges = $egCurrentHit['user_badges'];
		$alertMessage = ""; //NULL;
		$alertAction = $egCurrentHit['page_action'];
		// echo "<script type='text/javascript'>alert('$alertPoints and $alertBadges')</script>";
		// echo "<script>console.log( 'Notifications: $alertPoints Points, $alertBadges Badges, and $alertMessage Message. Event: $alertAction' );</script>";

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
			// echo "<script type='text/javascript'>alert('$alertMessage')</script>";
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

	//
	//	See WebRequest::getPathInfo() for ideas/info
	//  Make better use of: $wgScript, $wgScriptPath, $wgArticlePath;
	//
	//  Other recommendations:
	//    wfSuppressWarnings();
	//    $a = parse_url( $url );
	//    wfRestoreWarnings();
	//
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


