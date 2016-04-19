<?php

class SpecialUserJourney extends SpecialPage {

	public $mMode;

	public function __construct() {
		parent::__construct(
			"UserJourney", //
			"",  // rights required to view
			true // show in Special:SpecialPages
		);
	}

	function execute( $parser = null ) {
		global $wgRequest, $wgOut;

		list( $limit, $offset ) = wfCheckLimits();

		$this->mMode = $wgRequest->getVal( 'show' );

		$wgOut->addHTML( $this->getPageHeader() );

		//TO-DO change to switch?
		if ($this->mMode == 'user-history') {
			$this->userHistory();
		}
		else if ($this->mMode == 'user-badges') {
			$this->userBadges();
		}
		else if ($this->mMode == 'user-activity-data') {
			$this->myScoreData();
		}
		else if ( $this->mMode == 'user-activity-plot' ) {
			$this->myScorePlot();
		}

		if ($this->mMode == 'compare-activity-data') {
			$this->compareScoreData();
		}
		else if ( $this->mMode == 'compare-activity-by-similar-activity' ) {
			$this->compareActivityByPeers();
		}
		else if ( $this->mMode == 'compare-activity-by-user-group' ) {
			$this->compareScoreByUserGroup();
		}
		else if ( $this->mMode == 'compare-activity-between-groups' ){
			$this->compareScoreBetweenGroups();
		}
		else if ( $this->mMode == 'compare-views-between-groups' ){
			$this->compareViewsBetweenGroups();
		}
		else {
			$this->overview();
		}
	}

	public function getPageHeader() {
		global $wgRequest;

		if( $this->getUser()->getID() ){ // Only do stuff if user has an ID

			// show the names of the different views
			$navLine = '<strong>' . wfMsg( 'userjourney-viewmode' ) . ':</strong> ';

			$filterUser = $wgRequest->getVal( 'filterUser' );
			$filterPage = $wgRequest->getVal( 'filterPage' );

			if ( $filterUser || $filterPage ) {

				$UserJourneyTitle = SpecialPage::getTitleFor( 'UserJourney' );
				$unfilterLink = ': (' . Xml::element( 'a',
					array( 'href' => $UserJourneyTitle->getLocalURL() ),
					wfMsg( 'userjourney-unfilter' )
				) . ')';

			}
			else {
				$unfilterLink = '';
			}

			$navLine .= "<ul>";

			$navLine .= "<li>" . $this->createHeaderLink( 'userjourney-overview' ) . $unfilterLink . "</li>";

			//TO-DO clarify naming between modes and messages and functions
			//TO-DO add if statement to show extra data plots if logged-in user is in groups sysop or Manager
			//TO-DO add pull-down menus so these views can show any user's data for sysop or Manager

			$navLine .= "<li>" . $this->createHeaderLink( 'userjourney-history', 'user-history' ) . $unfilterLink . "</li>";

			$navLine .= "<li>" . $this->createHeaderLink( 'userjourney-badges', 'user-badges' ) . $unfilterLink . "</li>";

			$navLine .= "<li>" . wfMessage( 'userjourney-my-activity' )->text()
				. ": (" . $this->createHeaderLink( 'userjourney-rawdata', 'user-activity-data' )
				. ") (" . $this->createHeaderLink( 'userjourney-plot', 'user-activity-plot' )
				. ")</li>";

			$navLine .= "<li>" . wfMessage( 'userjourney-compare-activity' )->text()
				// . ": (" . $this->createHeaderLink( 'userjourney-rawdata', 'compare-activity-data' ) // not currently displayed, maybe later for admins/Managers
				. ": (" . $this->createHeaderLink( 'userjourney-plot-by-peers', 'compare-activity-by-similar-activity' )
				. ") (" . $this->createHeaderLink( 'userjourney-plot-users-within-group', 'compare-activity-by-user-group' )
				. ") (" . $this->createHeaderLink( 'userjourney-plot-by-group', 'compare-activity-between-groups' )
				. ") (" . $this->createHeaderLink( 'userjourney-plot-views-between-groups', 'compare-views-between-groups' )
				. ")</li>";

			$navLine .= "</ul>";

		} else {
			$navLine = "";

			$url = Title::newFromText('Special:UserLogin')->getLinkUrl('returnto=Special:UserJourney');

			$navLine = "Sorry, but this page is for logged in users. Please <a href='{$url}'>sign in</a> so you can begin your journey!";

		}

		$out = Xml::tags( 'p', null, $navLine ) . "\n";

		return $out;
	}

	function createHeaderLink($msg, $query_param = '' ) {

		$UserJourneyTitle = SpecialPage::getTitleFor( 'UserJourney' );

		if ( $this->mMode == $query_param ) {
			return Xml::element( 'strong',
				null,
				wfMsg( $msg )
			);
		} else {
			return Xml::element( 'a',
				array( 'href' => $UserJourneyTitle->getLocalURL( array( 'show' => $query_param ) ) ),
				wfMsg( $msg )
			);
		}

	}

	public function getDisplayName () {
		$username = $this->getUser()->mName;
	    $userRealName = $this->getUser()->mRealName;
	    if( $userRealName ){
	    	$displayName = $userRealName;
	    }
	    else{
	    	$displayName = $username;
	    }

	    return $displayName;
	}

	public function overview () {
		global $wgOut, $wgRequest;

		$wgOut->setPageTitle( 'UserJourney' );

		// if( $this->getUser()->getID() ){ // Only do stuff if user has an ID

		// 	$html = "";

		// } else {
		// 	$url = Title::newFromText('Special:UserLogin')->getLinkUrl('returnto=Special:UserJourney');

		// 	$html = "Sorry, but this page is for logged in users. Please <a href='{$url}'>sign in</a> so you can begin your journey!";
		// }

		$html = "";

		$wgOut->addHTML( $html );
	}


	public function userHistory () {
		global $wgOut, $wgRequest;

		$wgOut->setPageTitle( 'UserJourney' );

		$wgOut->addModules( 'ext.userjourney.myActivityByYear.nvd3' );

//		$pager = new UserJourneyPager();
//		$pager->filterUser = $wgRequest->getVal( 'filterUser' );
//		$pager->filterPage = $wgRequest->getVal( 'filterPage' );

//		$body = $pager->getBody();
		$html = '';

		$username = $this->getUser()->mName;
		$displayName = self::getDisplayName();

	    $dbr = wfGetDB( DB_SLAVE );

		$userTable = $dbr->tableName( 'user' );
		$userGroupTable = $dbr->tableName( 'user_groups' );
		$revTable = $dbr->tableName( 'revision' );
		$catLinksTable = $dbr->tableName( 'categorylinks' );

	    // Query for user's first revision timestamp
	    $sql = "SELECT
					DATE(rev_timestamp) AS day
				FROM $revTable
				WHERE rev_user_text = '{$username}'
				ORDER BY rev_timestamp ASC
				LIMIT 1";

		$res = $dbr->query( $sql );
	    $row = $dbr->fetchRow( $res );

	    $userFirstRevisionTimestamp = strtotime( $row['day'] ); // Unix timestamp like 1320732000
	    $userFirstRevisionDate =      date('jS', $userFirstRevisionTimestamp); // 3rd or 4th
	    $userFirstRevisionDay =       date('l', $userFirstRevisionTimestamp); // Monday or Tuesday
	    $userFirstRevisionMonth =     date('F', $userFirstRevisionTimestamp); // November
	    $userFirstRevisionYear =      date('Y', $userFirstRevisionTimestamp); // 2011

	    $userDaysSinceFirstRevision = round( ( ( strtotime( date('Ymd H:m:s') ) - $userFirstRevisionTimestamp ) / (60 * 60 * 24) ), 0);
	    $userDaysSinceFirstRevisionFormatted = number_format($userDaysSinceFirstRevision);

	    // Query for user's total number of revisions and total number of distinct pages revised
	    $sql = "SELECT
					COUNT(rev_timestamp) AS revisions,
					COUNT(DISTINCT rev_page) AS pages
				FROM $revTable
				WHERE rev_user_text = '{$username}'";

		$res = $dbr->query( $sql );
	    $row = $dbr->fetchRow( $res );

	    $userTotalRevisions = $row['revisions'];
	    $userTotalRevisionsFormatted = number_format( $row['revisions'] );
	    $userTotalDistinctPagesRevised = $row['pages'];
	    $userTotalDistinctPagesRevisedFormatted = number_format( $userTotalDistinctPagesRevised );
	    $userHoursSavedEstimate = round( $userTotalDistinctPagesRevised * (5 / 60), 1); // Hours saved

		// The story
		$html .= "<p>";
		$html .= "This is a tale of the wiki journey of {$displayName}.";
		$html .= " Our hero began a quest to share knowledge on the ";
		$html .= "{$userFirstRevisionDate} day of {$userFirstRevisionMonth} in the year {$userFirstRevisionYear}.";
		$html .= " But this was no ordinary {$userFirstRevisionDay}.";
		$html .= " On this fateful day, {$displayName} joined the brave legion of sharing information, citing references, and saving others' time.";
		$html .= "</p>";

		$html .= "<p>";
		$html .= "Over the past {$userDaysSinceFirstRevisionFormatted} days, ";
		$html .= "{$displayName} has made {$userTotalRevisionsFormatted} revisions to {$userTotalDistinctPagesRevisedFormatted} pages.";
		$html .= " What if we make a low-ball estimation that for each page ${displayName} has made a contribution";
		$html .= ", it saved just one person 5 minutes otherwise spent looking up the information from a share drive or giant document?";
		$html .= " That would be a time savings of {$userHoursSavedEstimate} hours!";
		$html .= "</p>";

		$html .= "<h2>History Overivew</h2>";

		// Query for revisions and pages grouped by year
		$sql = "SELECT
					YEAR(rev_timestamp) AS year,
					COUNT(rev_timestamp) AS revisions,
					COUNT(DISTINCT rev_page) AS pages
				FROM $revTable
				WHERE rev_user_text = '{$username}'
				GROUP BY YEAR(rev_timestamp)
				";

		$res = $dbr->query ( $sql );

	    while( $row = $dbr->fetchRow( $res ) ) {

			list($year, $revisions, $pages) = array($row['year'], $row['revisions'], $row['pages']);

			$userdata['Revisions'][] = array(
				'x' => intval($year),
				'y' => floatval( $revisions ),
			);

			$userdata['Pages'][] = array(
				'x' => intval($year),
				'y' => floatval( $pages ),
			);

	    }

  		$data[] = array(
    		'key' => 'Pages',
    		'values' => $userdata['Pages'],
  		);
  		$data[] = array(
    		'key' => 'Revisions',
    		'values' => $userdata['Revisions'],
  		);

	    $html .= "<div id='userjourney-my-activity-by-year-plot'><svg height='200px'></svg></div>";
	    $html .= "<script type='text/template-json' id='userjourney-data'>" . json_encode( $data ) . "</script>";


		/*
		*  SUMMARY OF EACH YEAR
		*/
		$html .= "Let's take a closer look. For each year, we have listed the most-revised pages for {$displayName}.";
		$html .= " Without all these contributions, those pages wouldn't be the useful resource they are today.";

		// Get array of years with contributions from user
		$sql = "SELECT
					YEAR(rev_timestamp) as year
				FROM $revTable
				WHERE rev_user_text = '{$username}'
				GROUP BY year
				ORDER BY year ASC";

		$res = $dbr->query( $sql );
		$years = array();
		while( $row = $dbr->fetchRow( $res ) ){
			$years[] = $row['year'];
		}

		// Parse through array of years and output section with summary
		foreach( $years as $year ){

			$html .= "<h3>{$year}</h3>";

			$html .= "Most-Revised Pages:";
			$html .= "<ol>";

			// Get list of pages most-revised in that year
			$startTimeStamp = $year * 10000000000;
			$endTimeStamp = ( $year + 1 ) * 10000000000;
			$sql = "SELECT
						page_id,
						COUNT(*) as count
					FROM
					(SELECT
						rev_page
					FROM $revTable
					WHERE rev_user_text = '{$username}'
					AND rev_timestamp < {$endTimeStamp}
					AND rev_timestamp > {$startTimeStamp}
					)a
					JOIN
					(SELECT
						page_id,
						page_namespace,
						page_title
					FROM page)b
					ON page_id=rev_page
					GROUP BY page_title
					ORDER BY count DESC
					LIMIT 10";

			$res = $dbr->query( $sql );
			while( $row = $dbr->fetchRow( $res ) ){

				list($page, $revisions) = array($row['page_id'], $row['count']);

				$pageTitle = Title::newFromID( $page );
				$pageURL = $this->getSkin()->link( $pageTitle );

				$html .= "<li>{$pageURL} ({$revisions} revisions in this year)</li>";
			}

			$html .= "</ol>";

			$html .= "Categories of Pages Most-Revised:";
			$html .= "<ol>";

			// Get list of pages most-revised in that year
			$startTimeStamp = $year * 10000000000;
			$endTimeStamp = ( $year + 1 ) * 10000000000;

			$sql = "SELECT
						cl_to as category,
						COUNT(*) as count
					FROM
					(SELECT
						rev_page
					FROM $revTable
					WHERE rev_user_text = '{$username}'
					AND rev_timestamp < {$endTimeStamp}
					AND rev_timestamp > {$startTimeStamp}
					)a,
					(SELECT
						cl_from,
						cl_to
					FROM $catLinksTable
					)b
					WHERE rev_page=cl_from
					GROUP BY cl_to
					ORDER BY count DESC
					LIMIT 10";

			$res = $dbr->query( $sql );
			while( $row = $dbr->fetchRow( $res ) ){

				list($category, $revisions) = array($row['category'], $row['count']);

				$pageTitle = Title::newFromText( $category, NS_CATEGORY );
				$pageURL = $this->getSkin()->link( $pageTitle );

				$html .= "<li>{$pageURL} ({$revisions} revisions to pages in this category in this year)</li>";
			}

			$html .= "</ol>";

		}

		$wgOut->addHTML( $html );
	}




	public function userBadges () {
		// TO-DO move each of these to functions and just call them from here
		global $wgOut, $wgRequest;

		$wgOut->setPageTitle( 'UserJourney' );

		// $pager = new UserJourneyPager();
		// $pager->filterUser = $wgRequest->getVal( 'filterUser' );
		// $pager->filterPage = $wgRequest->getVal( 'filterPage' );

		// $body = $pager->getBody();
		$html = '';

		$username = $this->getUser()->mName;
		$displayName = self::getDisplayName();

	    $dbr = wfGetDB( DB_SLAVE );

		$userTable = $dbr->tableName( 'user' );
		$userGroupTable = $dbr->tableName( 'user_groups' );
		$revTable = $dbr->tableName( 'revision' );
		$catTable = $dbr->tableName( 'category' );
		$catLinksTable = $dbr->tableName( 'categorylinks' );

	    // Query for user's first revision timestamp
	    $sql = "SELECT
					DATE(rev_timestamp) AS day
				FROM $revTable
				WHERE rev_user_text = '{$username}'
				ORDER BY rev_timestamp ASC
				LIMIT 1";

		$res = $dbr->query( $sql );
	    $row = $dbr->fetchRow( $res );

	    $userFirstRevisionTimestamp = strtotime( $row['day'] ); // Unix timestamp like 1320732000

	    // Query for user's total number of revisions and total number of distinct pages revised
	    $sql = "SELECT
					COUNT(rev_timestamp) AS revisions,
					COUNT(DISTINCT rev_page) AS pages
				FROM $revTable
				WHERE rev_user_text = '{$username}'";

		$res = $dbr->query( $sql );
	    $row = $dbr->fetchRow( $res );

	    $userTotalRevisions = $row['revisions'];
	    $userTotalDistinctPagesRevised = $row['pages'];

		// Quantity of revisions
		if( $userTotalRevisions == 0 ){
			$userTotalRevisionsLevel = 0;
		} else {
			$userTotalRevisionsLevel = floor( log( $userTotalRevisions, 10 ) + 1 ) ;
		}
		$html .= "Revisions Level {$userTotalRevisionsLevel}";
		$html .= "<br /><br />";

		// Quantity of pages revised
		if( $userTotalDistinctPagesRevised == 0 ){
			$userTotalDistinctPagesLevel = 0;
		} else {
			$userTotalDistinctPagesLevel = floor( log( $userTotalDistinctPagesRevised, 5 ) + 1 ) ;
		}
		$html .= "Distinct Pages Level {$userTotalDistinctPagesLevel}";
		$html .= "<br /><br />";

		/*
		*  List of categories with pages revised by user
		*  Limit to categories with at least 10 pages
		*  Output: catTitle, pagesInCatRevisedByUser, pagesInCat
		*/
		$html .= "Categories:";
		$html .= "<ol>";

		$sql = "SELECT
					catTitle,
					pagesInCatRevisedByUser,
					pagesInCat
				FROM
				(
					(SELECT
						cat_pages as pagesInCat,
						cat_title as catTitle
					FROM $catTable
					)d /* number of pages in categories */
					JOIN
					(
					SELECT
						COUNT(cat) as pagesInCatRevisedByUser,
						cat
					FROM
						(SELECT
							DISTINCT(rev_page)
						FROM $revTable
						WHERE rev_user_text = '{$username}'
						)a /* distinct pages user has revised */
						JOIN
						(SELECT
							cl_from,
							cl_to as cat
						FROM $catLinksTable
						)b /* pages and their categories */
						WHERE rev_page=cl_from
						GROUP BY cat
						)c /* number of pages in each category revised by user */
				) WHERE cat=catTitle
				AND pagesInCat > 9
				AND pagesInCatRevisedByUser / pagesInCat >= 0.2
				ORDER BY (pagesInCatRevisedByUser / pagesInCat) DESC";

		$res = $dbr->query( $sql );

		while( $row = $dbr->fetchRow( $res ) ){

			// Badges based on % of pages in category edited (>50%, >80%, 100%)

			list($category, $catPagesRevised, $catPages) = array($row['catTitle'], $row['pagesInCatRevisedByUser'], $row['pagesInCat']);

			$sql = "SELECT
						COUNT(DISTINCT(cl_from)) as count,
						username
					FROM
						(SELECT /* pages in EVA category */
							cl_from, /* pageID */
							cl_to /* catName */
						FROM $catLinksTable
						WHERE cl_to = '{$category}'
						)a
						JOIN
						(SELECT /* page revisions */
							rev_page, /* pageID */
							rev_user_text as username
						FROM $revTable
						)b
						WHERE cl_from=rev_page

					GROUP BY username
					ORDER BY count DESC";

			if( $catPagesRevised == $catPages ){

				$pageTitle = Title::newFromText( $category, NS_CATEGORY );
				$pageURL = $this->getSkin()->link( $pageTitle );

				$res2 = $dbr->query( $sql );

				$html .= "<li>100%: {$pageURL}";
				$html .= "<ol>";
				while( $row2 = $dbr->fetchRow( $res2 ) ){

					list($count, $user) = array($row2['count'], $row2['username']);

					if( ( $count > ($catPages * 0.2) ) && ( $user != $username ) ){
						$percent = floor( 100 * $count / $catPages );
						$person = User::newFromName("$user");
						$realName = $person->getRealName();
						if( empty($realName) ){
							$nameToUse = $user;
						} else {
							$nameToUse = $realName;
						}
						$html .= "<li>{$percent}%: {$nameToUse}</li>";
					}
				}
				$html .= "</ol>";
				$html .= "</li>";
			} else if( $catPagesRevised >= ( $catPages * 0.8 ) ){

				$pageTitle = Title::newFromText( $category, NS_CATEGORY );
				$pageURL = $this->getSkin()->link( $pageTitle );

				$res2 = $dbr->query( $sql );

				$html .= "<li>80%: {$pageURL}</li>";
				$html .= "<ol>";
				while( $row2 = $dbr->fetchRow( $res2 ) ){

					list($count, $user) = array($row2['count'], $row2['username']);

					if( ( $count > ($catPages * 0.2) ) && ( $user != $username ) ){
						$percent = floor( 100 * $count / $catPages );
						$person = User::newFromName("$user");
						$realName = $person->getRealName();
						if( empty($realName) ){
							$nameToUse = $user;
						} else {
							$nameToUse = $realName;
						}
						$html .= "<li>{$percent}%: {$nameToUse}</li>";
					}
				}
				$html .= "</ol>";
				$html .= "</li>";
			} else if( $catPagesRevised >= ( $catPages * 0.5 ) ){

				$pageTitle = Title::newFromText( $category, NS_CATEGORY );
				$pageURL = $this->getSkin()->link( $pageTitle );

				$res2 = $dbr->query( $sql );

				$html .= "<li>50%: {$pageURL}</li>";
				$html .= "<ol>";
				while( $row2 = $dbr->fetchRow( $res2 ) ){

					list($count, $user) = array($row2['count'], $row2['username']);

					if( ( $count > ($catPages * 0.2) ) && ( $user != $username ) ){
						$percent = floor( 100 * $count / $catPages );
						$person = User::newFromName("$user");
						$realName = $person->getRealName();
						if( empty($realName) ){
							$nameToUse = $user;
						} else {
							$nameToUse = $realName;
						}
						$html .= "<li>{$percent}%: {$nameToUse}</li>";
					}
				}
				$html .= "</ol>";
				$html .= "</li>";
			} else if( $catPagesRevised >= ( $catPages * 0.2 ) ){

				$pageTitle = Title::newFromText( $category, NS_CATEGORY );
				$pageURL = $this->getSkin()->link( $pageTitle );

				$res2 = $dbr->query( $sql );

				$html .= "<li>50%: {$pageURL}</li>";
				$html .= "<ol>";
				while( $row2 = $dbr->fetchRow( $res2 ) ){

					list($count, $user) = array($row2['count'], $row2['username']);

					if( ( $count > ($catPages * 0.2) ) && ( $user != $username ) ){
						$percent = floor( 100 * $count / $catPages );
						$person = User::newFromName("$user");
						$realName = $person->getRealName();
						if( empty($realName) ){
							$nameToUse = $user;
						} else {
							$nameToUse = $realName;
						}
						$html .= "<li>{$percent}%: {$nameToUse}</li>";
					}
				}
				$html .= "</ol>";
				$html .= "</li>";
			}

		}

		$html .= "</ol>";

		$wgOut->addHTML( $html );
	}



	public function myScoreData() {
		global $wgOut;

	    $username = $this->getUser()->mName;
	    $displayName = self::getDisplayName();

		$wgOut->setPageTitle( "UserJourney: Activity Data for $displayName" );

		$html = '<table class="wikitable sortable"><tr><th>Date</th><th>Score</th><th>Pages</th><th>Revisions</th></tr>';

		$dbr = wfGetDB( DB_SLAVE );

		$userTable = $dbr->tableName( 'user' );
		$userGroupTable = $dbr->tableName( 'user_groups' );
		$revTable = $dbr->tableName( 'revision' );
		$catTable = $dbr->tableName( 'category' );
		$catLinksTable = $dbr->tableName( 'categorylinks' );

    $sql = "SELECT
              DATE(rev_timestamp) AS day,
              COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 AS score,
              COUNT(DISTINCT rev_page) as pages,
              COUNT(rev_id) as revisions
            FROM $revTable
            WHERE
              rev_user_text IN ( '$username' )
            GROUP BY day
            ORDER BY day DESC";

    $res = $dbr->query( $sql );

    $previous = null;

    while( $row = $dbr->fetchRow( $res ) ) {

      list($day, $score, $pages, $revisions) = array($row['day'], $row['score'], $row['pages'], $row['revisions']);
      $date = date('Y-m-d', strtotime( $day ));
      $score = round($score, 1);
      $html .= "<tr><td>$date</td><td>$score</td><td>$pages</td><td>$revisions</td></tr>";

    }

		$html .= "</table>";

		$wgOut->addHTML( $html );

	}


	/**
	* Function generates plot of contribution score for logged-in user over time
	*
	* @param $tbd - no parameters now
	* @return nothing - generates special page
	*/
	function myScorePlot( ){
		//TO-DO Make one plotting function to handle all 3 options, using parameters
		//			Probably: $competitors (from this list, determine solo vs group comparison), time to compare, time to plot
		//TO-DO add if statement to not show unless logged-in user
		//DO-DO Adjust $queryDT - some have one parameter, others two
		//TO-DO add dropdown menu to select groups (but hide Viewer and Contributor and any groups > x people )
    global $wgOut;

    $username = $this->getUser()->mName;
	$displayName = self::getDisplayName();

    $wgOut->setPageTitle( "UserJourney: Activity Plot for $displayName" );

    if( $this->getUser()->getID() ){ // Only do stuff if user has an ID

	    $wgOut->addModules( 'ext.userjourney.myActivity.nvd3' );

	    $dbr = wfGetDB( DB_SLAVE );

		$userTable = $dbr->tableName( 'user' );
		$userGroupTable = $dbr->tableName( 'user_groups' );
		$revTable = $dbr->tableName( 'revision' );
		$catTable = $dbr->tableName( 'category' );
		$catLinksTable = $dbr->tableName( 'categorylinks' );

		$competitors = array($username);

	    // $queryScore = "COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2"; // How to calculate score

	    $queryDT = function( $competitor, $revTable ){
	    	$output = "INSERT INTO temp_union (day, {$competitor})
				SELECT
					DATE(rev_timestamp) AS day,
					COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 AS {$competitor}
				FROM $revTable
				WHERE
					rev_user_text IN ( '{$competitor}' )
	        /* AND rev_timestamp > 20150101000000 */
				GROUP BY day";

				return $output;
	    };

			// Create temp table
			$sql = "CREATE TEMPORARY TABLE temp_union(
				day date NULL";
			// Add column for user "dummy"
			$sql .= ", dummy float NULL";
			foreach( $competitors as $competitor ){
				$sql .= ", {$competitor} float NULL";
			}
			$sql .= " )ENGINE = MEMORY";

	    $res = $dbr->query( $sql );

	    // Add column with dummy user to generate a 0 value for every day during comparison period
	    $sql = "SELECT
					DATE(rev_timestamp) AS day
				FROM $revTable
				WHERE rev_user_text in ( ''";
			foreach( $competitors as $competitor ){
				$sql .= ", '{$competitor}'";
			}
		$sql .= ") ORDER BY rev_timestamp ASC
			LIMIT 1";

		$res = $dbr->query( $sql );
	    $row = $dbr->fetchRow( $res );
	    $firstContributionDateFromGroup = $row['day'];

	    $lastDate = date("Ymd", time()); // Today as YYYYMMDD
	    $firstDate = date('Ymd', strtotime( $firstContributionDateFromGroup ) ); // Date of first contribution from users in this group
	    $date = $firstDate;
	    while( $date <= $lastDate ){
	    	$dateTime = date('Y-m-d', strtotime($date * 1000000) ); // Append 0 value for HHMMSS to match timestamp format in revision table

				// $sql = $queryDT('dummy', $dateTime);
				$sql = "INSERT INTO temp_union (day, dummy) VALUES ('{$dateTime}', '0')";

				$res = $dbr->query( $sql );

				$date = date('Ymd', strtotime($date . ' +1 day') );
	    }

			// Add each competitor's score to temp table
			foreach( $competitors as $competitor ){
				$sql = $queryDT($competitor, $revTable);

				$res = $dbr->query( $sql );
			}

			// Consolidate rows so each day only has one row
	    $sql = "SELECT
				day";
			foreach( $competitors as $competitor ){
				$sql .= ", max({$competitor}) {$competitor}";
			}
			$sql .= " FROM temp_union GROUP BY day";

	    $res = $dbr->query( $sql );

		while( $row = $dbr->fetchRow( $res ) ) {

			foreach( $competitors as $competitor ){

				list($day, $score) = array($row['day'], $row["$competitor"]);

				$userdata["$competitor"][] = array(
					'x' => strtotime( $day ) * 1000,
					'y' => floatval( $score ),
				);
			}

	    }

			// Remove temp table
	    $sql = "DROP TABLE temp_union";
	    $res = $dbr->query ( $sql );

	    foreach( $competitors as $competitor ){

		    $person = User::newFromName("$competitor");
				$realName = $person->getRealName();
				if( empty($realName) ){
					$nameToUse = $competitor;
				} else {
					$nameToUse = $realName;
				}

		    $data[] = array(
	    		'key' => $nameToUse,
	    		'values' => $userdata["$competitor"],
	  		);

	    }


			$html = '';
	    $html .= '<div id="userjourney-my-activity-plot"><svg height="400px"></svg></div>';
	    $html .= "<script type='text/template-json' id='userjourney-data'>" . json_encode( $data ) . "</script>";
	  } else {
			$html = '<br />Sorry, but this feature is not available for anonymous users.<br />';
	  }

    $wgOut->addHTML( $html );

	}








	//This is currently not displayed. The function need to be updated to match compareActivityByPeers, but show data table
	//instead of a plot.
	public function compareScoreData() {
		global $wgOut;

    $username = $this->getUser()->mName;
	$displayName = self::getDisplayName();

    $username2 = 'Ejmontal'; //Competitor
    $competitors = array( // TO-DO: move this array to where func it called and pass as parameter
    	$username,
    	'Ejmontal',
    	'Swray'
    	);

		$james = User::newFromName("Ejmontal");
		$name2 = $james->getRealName();

		$wgOut->setPageTitle( "UserJourney: Compare scores: $displayName vs. $name2" );

		$html = '<table class="wikitable sortable"><tr><th>Date</th><th>' . $displayName . '</th><th>' . 'TBD' . '</th></tr>';

		$dbr = wfGetDB( DB_SLAVE );

		$userTable = $dbr->tableName( 'user' );
		$userGroupTable = $dbr->tableName( 'user_groups' );
		$revTable = $dbr->tableName( 'revision' );
		$catTable = $dbr->tableName( 'category' );
		$catLinksTable = $dbr->tableName( 'categorylinks' );

		$queryScore = "COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2"; // How to calculate score

    $sql = "SELECT
							COALESCE(user_day, user2_day) AS day,
							user_score,
							user2_score
						FROM
						(
							SELECT * FROM
								(
								SELECT
									DATE(rev_timestamp) AS user_day,
									{$queryScore} AS user_score
								FROM $revTable
								WHERE
									rev_user_text IN ( '$username' )
								GROUP BY user_day
								) user
							LEFT JOIN
							(
								SELECT
									DATE(rev_timestamp) AS user2_day,
									{$queryScore} AS user2_score
								FROM $revTable
								WHERE
									rev_user_text IN ( '$username2' )
								GROUP BY user2_day
							) user2
							ON user.user_day=user2.user2_day
							UNION
							SELECT * FROM
							(
								SELECT
									DATE(rev_timestamp) AS user_day,
									{$queryScore} AS user_score
								FROM $revTable
								WHERE
									rev_user_text IN ( '$username' )
								GROUP BY user_day
							) user
							RIGHT JOIN
							(
								SELECT
									DATE(rev_timestamp) AS user2_day,
									{$queryScore} AS user2_score
								FROM $revTable
								WHERE
									rev_user_text IN ( '$username2' )
								GROUP BY user2_day
							) user2
							ON user.user_day=user2.user2_day
						)results
						ORDER BY day DESC";

    $res = $dbr->query( $sql );

    $previous = null;

    while( $row = $dbr->fetchRow( $res ) ) {

      list($day, $userScore, $user2Score) = array($row['day'], $row['user_score'], $row['user2_score']);
      $date = date('Y-m-d', strtotime( $day ));
      $userScore = round($userScore, 1);
      $user2Score = round($user2Score, 1);
      $html .= "<tr><td>$date</td><td>$userScore</td><td>$user2Score</td></tr>";

    }

		$html .= "</table>";

		$wgOut->addHTML( $html );

	}




function compareActivityByPeers( ){
		// TO-DO Modify plots to have some granular/moving-average and some just showing 1-month or 3-month average values
		// TO-DO Maybe have one page with all data and another page with last 30-60 days
    global $wgOut;
    //TO-DO make $scoreCeiling an eg configured in LocalSettings
    $scoreCeiling = 100; // Limit score to this value or below

    $daysToDetermineCompetitors = 14; // Number of days in which to compare scores of logged-in user against others (used to find suitable competitors)
    $daysToPlot = 365;
    $daysToDetermineCompetitors += 100; //TO-DO remove - number increased for testing on old wiki
    $daysToPlot += 100; //TO-DO remove - number increased for testing on old wiki

    $username = $this->getUser()->mName;
	$displayName = self::getDisplayName();

    $competitors = array( // For this function, start with only the logged-in user. More are added later.
    	$username,
    	);

    $wgOut->setPageTitle( "UserJourney: Score comparison plot" ); //TO-DO is this even doing anything?

		if( $this->getUser()->getID() ){ // Only do stuff if user has an ID

	    $wgOut->addModules( 'ext.userjourney.compare.nvd3' );
	    // $wgOut->addModules( 'ext.userjourney.compareScoreStackedPlot.nvd3' );

	    $dbr = wfGetDB( DB_SLAVE );

		$userTable = $dbr->tableName( 'user' );
		$userGroupTable = $dbr->tableName( 'user_groups' );
		$revTable = $dbr->tableName( 'revision' );

	    // $queryScore = "COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2"; // How to calculate score

	    // Determine score of logged in user
			$date = time() - ( 60 * 60 * 24 * $daysToDetermineCompetitors );
			$dateString = $dbr->timestamp( $date );

	    $sql = "SELECT
	    	LEAST({$scoreCeiling}, COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 ) as score
	      FROM $revTable
	      WHERE rev_timestamp > '$dateString'
	      AND rev_user_text = '{$username}'
	    ";

	    $res = $dbr->query( $sql );
	    $row = $dbr->fetchRow( $res );
	    $userRecentScore = round($row['score'], 5);

	    // Determine users with relatively similar scores for the past $daysToDetermineCompetitors
	    $commonQuery = "
				(SELECT
					user_name,
					score
				FROM
					(SELECT user_id,
						user_name,
						page_count,
						rev_count,
						page_count+SQRT(rev_count-page_count)*2 AS score
					FROM $userTable u
					JOIN
					(SELECT rev_user,
						COUNT(DISTINCT rev_page) AS page_count,
						COUNT(rev_id) AS rev_count
						FROM $revTable
						WHERE rev_timestamp > '$dateString'
						AND rev_user_text != '{$username}'
						GROUP BY rev_user
						ORDER BY page_count DESC
					) s ON user_id=rev_user
	    ";

	    $sql = "
	    	{$commonQuery}
	    	ORDER BY score ASC ) t1
				WHERE score > {$userRecentScore}
				LIMIT 3 )
				UNION
				{$commonQuery}
				ORDER BY score DESC ) t2
				WHERE score < {$userRecentScore}
				LIMIT 2 )
				ORDER BY score DESC
	    ";

	    $res = $dbr->query( $sql );

			while( $row = $dbr->fetchRow( $res ) ) {

					list($competitor, $score) = array($row['user_name'], $row['score']);

					$competitors[] = "$competitor";

	    }

	    $queryDT = function( $competitor, $dateString, $scoreCeiling, $revTable ){
	    	$output = "INSERT INTO temp_union (day, {$competitor})
				SELECT
					DATE(rev_timestamp) AS day,
					LEAST({$scoreCeiling}, COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 ) AS {$competitor}
				FROM $revTable
				WHERE
					rev_user_text IN ( '{$competitor}' )
					AND rev_timestamp > '$dateString'
				GROUP BY day";

				return $output;
	    };

			// Create temp table
			$sql = "CREATE TEMPORARY TABLE temp_union(
				day date NULL";
			// Add column for user "dummy"
			$sql .= ", dummy float NULL";
			foreach( $competitors as $competitor ){
				$sql .= ", {$competitor} float NULL";
			}
			$sql .= " )ENGINE = MEMORY";

	    $res = $dbr->query( $sql );

	    // Add column with dummy user to generate a 0 value for every day during comparison period
	    $lastDate = date("Ymd", time()); // Today as YYYYMMDD
	    $firstDate = date('Ymd', strtotime($lastDate . " - {$daysToPlot} days")); // Today - $daysToPlot days
	    $date = $firstDate;
	    while( $date <= $lastDate ){
	    	$dateTime = date('Y-m-d', strtotime($date * 1000000) ); // Append 0 value for HHMMSS to match timestamp format in revision table

				// $sql = $queryDT('dummy', $dateTime);
				$sql = "INSERT INTO temp_union (day, dummy) VALUES ('{$dateTime}', '0')";

				$res = $dbr->query( $sql );

				$date = date('Ymd', strtotime($date . ' +1 day') );
	    }

			// Add each competitor's score to temp table
			foreach( $competitors as $competitor ){
				// TO-DO update to UPDATE rows instead of INSERT (after generating table with one row for eacy day)
				$date = time() - ( 60 * 60 * 24 * $daysToPlot );
				$dateString = $dbr->timestamp( $date );

				$sql = $queryDT($competitor, $dateString, $scoreCeiling, $revTable);

				$res = $dbr->query( $sql );
			}

			// Consolidate rows so each day only has one row
	    $sql = "SELECT
				day";
			foreach( $competitors as $competitor ){
				$sql .= ", max({$competitor}) {$competitor}";
			}
			$sql .= " FROM temp_union GROUP BY day";

	    $res = $dbr->query( $sql );

			while( $row = $dbr->fetchRow( $res ) ) {

				foreach( $competitors as $competitor ){

					list($day, $score) = array($row['day'], $row["$competitor"]);

					$userdata["$competitor"][] = array(
						'x' => strtotime( $day ) * 1000,
						'y' => floatval( $score ),
					);
				}

	    }

			// Remove temp table
	    $sql = "DROP TABLE temp_union";
	    $res = $dbr->query ( $sql );

	    foreach( $competitors as $competitor ){

		    $person = User::newFromName("$competitor");
				$realName = $person->getRealName();
				if( empty($realName) ){
					$nameToUse = $competitor;
				} else {
					$nameToUse = $realName;
				}

		    $data[] = array(
	    		'key' => $nameToUse,
	    		'values' => $userdata["$competitor"],
	  		);

	    }

			$html = '';
			$html .= '<h2>Line with Window</h2>';
	    $html .= '<div id="userjourney-compare-chart-line-with-window"><svg height="400px"></svg></div>';
			$html .= '<h2>Stacked Area</h2>';
	    $html .= '<div id="userjourney-compare-chart-stacked"><svg height="400px"></svg></div>';
			// $html .= '<h2>Stacked Area Stream</h2>';
	  //   $html .= '<div id="userjourney-compare-chart-stream"><svg height="400px"></svg></div>';

	    $html .= "<script type='text/template-json' id='userjourney-data'>" . json_encode( $data ) . "</script>";
		} else {
			$html = '<br />Sorry, but this feature is not available for anonymous users.<br />';
		}
    $wgOut->addHTML( $html );
  }






function compareScoreByUserGroup( ){
		//TO-DO add dropdown menu to select groups (but hide Viewer and Contributor and any groups > x people )
    global $wgOut;
    //TO-DO make $scoreCeiling an eg configured in LocalSettings
    $scoreCeiling = 100; // Limit score to this value or below

    $userGroup = "sysop"; // CX3, sysop, Curator, Manager, Beta-tester, use Contributor with caution

    $username = $this->getUser()->mName;
	$displayName = self::getDisplayName();

    $competitors = array();

    $wgOut->setPageTitle( "UserJourney: Score comparison plot" );
    $wgOut->addModules( 'ext.userjourney.compare.nvd3' );

    $dbr = wfGetDB( DB_SLAVE );

	$userTable = $dbr->tableName( 'user' );
	$userGroupTable = $dbr->tableName( 'user_groups' );
	$revTable = $dbr->tableName( 'revision' );
	$catTable = $dbr->tableName( 'category' );
	$catLinksTable = $dbr->tableName( 'categorylinks' );

	// Determine list of competitors based on $userGroup
    $sql = "SELECT
				user_name
			FROM (
			SELECT
				ug_user,
				ug_group
			FROM $userGroupTable
			WHERE ug_group = '{$userGroup}'
			) a
			 JOIN
			(
			SELECT
				user_id,
				user_name
			FROM $userTable
			) b
			ON ug_user=user_id
    ";

    $res = $dbr->query( $sql );

		while( $row = $dbr->fetchRow( $res ) ) {

				$competitors[] = $row['user_name'];

    }

    // $queryScore = "COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2"; // How to calculate score

    $queryDT = function( $competitor, $scoreCeiling, $revTable ){
    	$output = "INSERT INTO temp_union (day, {$competitor})
			SELECT
				DATE(rev_timestamp) AS day,
				LEAST({$scoreCeiling}, COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 ) AS {$competitor}
			FROM $revTable
			WHERE
				rev_user_text IN ( '{$competitor}' )
        /* AND rev_timestamp > 20150101000000 */
			GROUP BY day";

			return $output;
    };

		// Create temp table
		$sql = "CREATE TEMPORARY TABLE temp_union(
			day date NULL";
		// Add column for user "dummy"
		$sql .= ", dummy float NULL";
		foreach( $competitors as $competitor ){
			$sql .= ", {$competitor} float NULL";
		}
		$sql .= " )ENGINE = MEMORY";

    $res = $dbr->query( $sql );

    // Add column with dummy user to generate a 0 value for every day during comparison period
    $sql = "SELECT
				DATE(rev_timestamp) AS day
			FROM $revTable
			WHERE rev_user_text in ( ''";
		foreach( $competitors as $competitor ){
			$sql .= ", '{$competitor}'";
		}
		$sql .= ") ORDER BY rev_timestamp ASC
			LIMIT 1";

		$res = $dbr->query( $sql );
    $row = $dbr->fetchRow( $res );
    $firstContributionDateFromGroup = $row['day'];

    $lastDate = date("Ymd", time()); // Today as YYYYMMDD
    $firstDate = date('Ymd', strtotime( $firstContributionDateFromGroup ) ); // Date of first contribution from users in this group
    $date = $firstDate;
    while( $date <= $lastDate ){
    	$dateTime = date('Y-m-d', strtotime($date * 1000000) ); // Append 0 value for HHMMSS to match timestamp format in revision table

			// $sql = $queryDT('dummy', $dateTime);
			$sql = "INSERT INTO temp_union (day, dummy) VALUES ('{$dateTime}', '0')";

			$res = $dbr->query( $sql );

			$date = date('Ymd', strtotime($date . ' +1 day') );
    }

		// Add each competitor's score to temp table
		foreach( $competitors as $competitor ){
			$sql = $queryDT($competitor, $scoreCeiling, $revTable);

			$res = $dbr->query( $sql );
		}

		// Consolidate rows so each day only has one row
    $sql = "SELECT
			day";
		foreach( $competitors as $competitor ){
			$sql .= ", max({$competitor}) {$competitor}";
		}
		$sql .= " FROM temp_union GROUP BY day";

    $res = $dbr->query( $sql );

		while( $row = $dbr->fetchRow( $res ) ) {

			foreach( $competitors as $competitor ){

				list($day, $score) = array($row['day'], $row["$competitor"]);

				$userdata["$competitor"][] = array(
					'x' => strtotime( $day ) * 1000,
					'y' => floatval( $score ),
				);
			}

    }

		// Remove temp table
    $sql = "DROP TABLE temp_union";
    $res = $dbr->query ( $sql );

    foreach( $competitors as $competitor ){

	    $person = User::newFromName("$competitor");
			$realName = $person->getRealName();
			if( empty($realName) ){
				$nameToUse = $competitor;
			} else {
				$nameToUse = $realName;
			}

	    $data[] = array(
    		'key' => $nameToUse,
    		'values' => $userdata["$competitor"],
  		);

    }


		$html = '';
		$html .= '<h2>Stacked Area</h2>';
	  $html .= '<div id="userjourney-compare-chart-stacked"><svg height="400px"></svg></div>';
		// $html .= '<h2>Stacked Area Stream</h2>';
	 //  $html .= '<div id="userjourney-compare-chart-stream"><svg height="400px"></svg></div>';
    $html .= "<script type='text/template-json' id='userjourney-data'>" . json_encode( $data ) . "</script>";

    $wgOut->addHTML( $html );
  }




// Plot contributions pitting one group against another (1 v 1)
function compareScoreBetweenGroups( ){
		//TO-DO add dropdown menu to select groups (but hide Viewer and Contributor and any groups > x people )
    global $wgOut;
    //TO-DO make $scoreCeiling an eg configured in LocalSettings
    $scoreCeiling = 100; // Limit score to this value or below

    // $userGroup = "sysop"; // CX3, sysop, Curator, Manager, Beta-tester, use Contributor with caution
    // for now, 2nd group is CX3 && !sysop
    // for now, 3rd group is !CX3 (&& !sysop)

    $username = $this->getUser()->mName;
	$displayName = self::getDisplayName();

    $wgOut->setPageTitle( "UserJourney: Score comparison plot" );
    $wgOut->addModules( 'ext.userjourney.compare.nvd3' );

    $dbr = wfGetDB( DB_SLAVE );

		$userTable = $dbr->tableName( 'user' );
		$userGroupTable = $dbr->tableName( 'user_groups' );
		$revTable = $dbr->tableName( 'revision' );
		$catTable = $dbr->tableName( 'category' );
		$catLinksTable = $dbr->tableName( 'categorylinks' );

	// Determine list of users in sysop
    $sqlSysop = "SELECT
				user_name
			FROM (
			SELECT
				ug_user,
				ug_group
			FROM $userGroupTable
			WHERE ug_group = 'sysop'
			) a
			 JOIN
			(
			SELECT
				user_id,
				user_name
			FROM $userTable
			) b
			ON ug_user=user_id
    ";

    $res = $dbr->query( $sqlSysop );

		while( $row = $dbr->fetchRow( $res ) ) {

			$usersInSysop[] = $row['user_name'];

    }

    // Determine list of users in CX3 and not in sysop
    $sqlCX3NotSysop = "SELECT
						user_name
					FROM
						(SELECT
							b.ug_user
						FROM
							(SELECT
								ug_user,
								ug_group
							FROM $userGroupTable
							WHERE ug_group IN ('sysop')
							)a
							RIGHT JOIN
							(SELECT
								ug_user,
								ug_group
							FROM $userGroupTable
							WHERE ug_group IN ('CX3')
							)b
							ON a.ug_user=b.ug_user
							WHERE a.ug_user is NULL)c
					 JOIN
					(
					SELECT
						user_id,
						user_name
					FROM $userTable
					)d
					ON c.ug_user=d.user_id";

    $res = $dbr->query( $sqlCX3NotSysop );

		while( $row = $dbr->fetchRow( $res ) ) {

			$usersInCX3[] = $row['user_name'];

    }

    // Determine list of users not in CX3
    $sqlNotInCX3 = "SELECT
					user_name
				FROM
					(SELECT
						DISTINCT b.ug_user
					FROM
						(SELECT
							ug_user,
							ug_group
						FROM $userGroupTable
						WHERE ug_group IN ('CX3')
						)a
						RIGHT JOIN
						(SELECT
							ug_user,
							ug_group
						FROM $userGroupTable
						WHERE ug_group NOT IN ('CX3')
						)b
						ON a.ug_user=b.ug_user
						WHERE a.ug_user is NULL)c
				 JOIN
				(
				SELECT
					user_id,
					user_name
				FROM $userTable
				)d
				ON c.ug_user=d.user_id";

    $res = $dbr->query( $sqlNotInCX3 );

		while( $row = $dbr->fetchRow( $res ) ) {

			$usersNotInCX3[] = $row['user_name'];

    }

    $competitors = array(
    	'Admins' => $usersInSysop,
    	'CX3' => $usersInCX3,
    	'Others' => $usersNotInCX3,
    	);

    // $queryScore = "COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2"; // How to calculate score

    $queryDT = function( $competitorTeamName, $competitorUsernames, $scoreCeiling, $revTable ){
    	$output = "INSERT INTO temp_union (day, {$competitorTeamName})
			SELECT
				DATE(rev_timestamp) AS day,
				LEAST({$scoreCeiling}, COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 ) AS {$competitorTeamName}
			FROM $revTable
			WHERE
				rev_user_text IN ( ";

		foreach( $competitorUsernames as $person ){
			$output .= " '{$person}', ";
		}
		$output .= " '' ";

		$output .= " )
        /* AND rev_timestamp > 20150101000000 */
			GROUP BY day";

		return $output;
    };

	// Create temp table
	$sql = "CREATE TEMPORARY TABLE temp_union(
		day date NULL";
	// Add column for user "dummy"
	$sql .= ", dummy float NULL";
	foreach( $competitors as $competitorTeamName => $competitorUsernames ){
		$sql .= ", {$competitorTeamName} float NULL";
	}
	$sql .= " )ENGINE = MEMORY";

    $res = $dbr->query( $sql );

    // Add column with dummy user to generate a 0 value for every day during comparison period
    // For this function I removed WHERE condition limiting time window to competitors; just use entire wiki history
    $sql = "SELECT
				DATE(rev_timestamp) AS day
			FROM $revTable
			ORDER BY rev_timestamp ASC
			LIMIT 1";

	$res = $dbr->query( $sql );
    $row = $dbr->fetchRow( $res );
    $firstContributionDateFromGroup = $row['day'];

    $lastDate = date("Ymd", time()); // Today as YYYYMMDD
    $firstDate = date('Ymd', strtotime( $firstContributionDateFromGroup ) ); // Date of first contribution from users in this group
    $date = $firstDate;
    while( $date <= $lastDate ){
    	$dateTime = date('Y-m-d', strtotime($date * 1000000) ); // Append 0 value for HHMMSS to match timestamp format in revision table

			// $sql = $queryDT('dummy', $dateTime);
			$sql = "INSERT INTO temp_union (day, dummy) VALUES ('{$dateTime}', '0')";

			$res = $dbr->query( $sql );

			$date = date('Ymd', strtotime($date . ' +1 day') );
    }

	// Add each competitor's score to temp table
	foreach( $competitors as $competitorTeamName => $competitorUsernames ){
		$sql = $queryDT($competitorTeamName, $competitorUsernames, $scoreCeiling, $revTable);

		$res = $dbr->query( $sql );
	}

	// Consolidate rows so each day only has one row
    $sql = "SELECT
			day";
		foreach( $competitors as $competitorTeamName => $competitorUsernames ){
			$sql .= ", max({$competitorTeamName}) {$competitorTeamName}";
		}
		$sql .= " FROM temp_union GROUP BY day";

    $res = $dbr->query( $sql );

	while( $row = $dbr->fetchRow( $res ) ) {

		foreach( $competitors as $competitorTeamName => $competitorUsernames ){

			list($day, $score) = array($row['day'], $row["$competitorTeamName"]);

			$userdata["$competitorTeamName"][] = array(
				'x' => strtotime( $day ) * 1000,
				'y' => floatval( $score ),
			);
		}

    }

		// Remove temp table
    $sql = "DROP TABLE temp_union";
    $res = $dbr->query ( $sql );

    foreach( $competitors as $competitorTeamName => $competitorUsernames ){

	    $person = User::newFromName("$competitorTeamName");
			$realName = $person->getRealName();
			if( empty($realName) ){
				$nameToUse = $competitorTeamName;
			} else {
				$nameToUse = $realName;
			}

	    $data[] = array(
    		'key' => $nameToUse,
    		'values' => $userdata["$competitorTeamName"],
  		);

    }


		$html = '';
		$html .= '<h2>Stacked Area</h2>';
	  $html .= '<div id="userjourney-compare-chart-stacked"><svg height="400px"></svg></div>';
		// $html .= '<h2>Stacked Area Stream</h2>';
	 //  $html .= '<div id="userjourney-compare-chart-stream"><svg height="400px"></svg></div>';
    $html .= "<script type='text/template-json' id='userjourney-data'>" . json_encode( $data ) . "</script>";

    $wgOut->addHTML( $html );
  }





// Plot unique user-page views pitting one group against another (1 v 1)
function compareViewsBetweenGroups( ){
	//TO-DO this has a dependency on Extension:Wiretap
	//TO-DO add dropdown menu to select groups (but hide Viewer and Contributor and any groups > x people )
    global $wgOut;

    // $userGroup = "sysop"; // CX3, sysop, Curator, Manager, Beta-tester, use Contributor with caution
    // for now, 2nd group is CX3 && !sysop
    // for now, 3rd group is !CX3 (&& !sysop)

    $username = $this->getUser()->mName;
	$displayName = self::getDisplayName();

    $wgOut->setPageTitle( "UserJourney: Unique user-page view comparison plot" );
    $wgOut->addModules( 'ext.userjourney.compare.nvd3' );

    $dbr = wfGetDB( DB_SLAVE );

	$userTable = $dbr->tableName( 'user' );
	$userGroupTable = $dbr->tableName( 'user_groups' );
	$revTable = $dbr->tableName( 'revision' );
	$catTable = $dbr->tableName( 'category' );
	$catLinksTable = $dbr->tableName( 'categorylinks' );
	$wiretapTable = $dbr->tableName( 'wiretap' );

	// Determine list of users in sysop
    $sqlSysop = "SELECT
				user_name
			FROM (
			SELECT
				ug_user,
				ug_group
			FROM $userGroupTable
			WHERE ug_group = 'sysop'
			) a
			 JOIN
			(
			SELECT
				user_id,
				user_name
			FROM $userTable
			) b
			ON ug_user=user_id
    ";

    $res = $dbr->query( $sqlSysop );

		while( $row = $dbr->fetchRow( $res ) ) {

			$usersInSysop[] = $row['user_name'];

    }

    // Determine list of users in CX3 and not in sysop
    $sqlCX3NotSysop = "SELECT
						user_name
					FROM
						(SELECT
							b.ug_user
						FROM
							(SELECT
								ug_user,
								ug_group
							FROM $userGroupTable
							WHERE ug_group IN ('sysop')
							)a
							RIGHT JOIN
							(SELECT
								ug_user,
								ug_group
							FROM $userGroupTable
							WHERE ug_group IN ('CX3')
							)b
							ON a.ug_user=b.ug_user
							WHERE a.ug_user is NULL)c
					 JOIN
					(
					SELECT
						user_id,
						user_name
					FROM $userTable
					)d
					ON c.ug_user=d.user_id";

    $res = $dbr->query( $sqlCX3NotSysop );

		while( $row = $dbr->fetchRow( $res ) ) {

			$usersInCX3[] = $row['user_name'];

    }

    // Determine list of users not in CX3
    $sqlNotInCX3 = "SELECT
					user_name
				FROM
					(SELECT
						DISTINCT b.ug_user
					FROM
						(SELECT
							ug_user,
							ug_group
						FROM $userGroupTable
						WHERE ug_group IN ('CX3')
						)a
						RIGHT JOIN
						(SELECT
							ug_user,
							ug_group
						FROM $userGroupTable
						WHERE ug_group NOT IN ('CX3')
						)b
						ON a.ug_user=b.ug_user
						WHERE a.ug_user is NULL)c
				 JOIN
				(
				SELECT
					user_id,
					user_name
				FROM $userTable
				)d
				ON c.ug_user=d.user_id";

    $res = $dbr->query( $sqlNotInCX3 );

		while( $row = $dbr->fetchRow( $res ) ) {

			$usersNotInCX3[] = $row['user_name'];

    }

    $competitors = array(
    	'Admins' => $usersInSysop,
    	'CX3' => $usersInCX3,
    	'Others' => $usersNotInCX3,
    	);

    // $queryScore = "COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2"; // How to calculate score

    $queryDT = function( $competitorTeamName, $competitorUsernames, $wiretapTable ){
    	// parts of this query stolen from Extension:Wiretap function getUniqueRows()
    	$output = "INSERT INTO temp_union (day, {$competitorTeamName})
			SELECT
				DATE(hit_timestamp) AS day,
				COUNT(DISTINCT(CONCAT(user_name,'UNIQUESEPARATOR',page_id))) AS {$competitorTeamName} -- For unique-page views
				-- COUNT(DISTINCT(user_name)) AS {$competitorTeamName} -- For non-unique-page views
			FROM $wiretapTable
			WHERE
				user_name IN ( ";

		foreach( $competitorUsernames as $person ){
			$output .= " '{$person}', ";
		}
		$output .= " '' ";

		$output .= " )
        /* AND rev_timestamp > 20150101000000 */
        /* AND user_name NOT IN ('Dmeza') */ -- Because he scraped the EVA wiki on 2015-11-03, resulting in 4714 unique user-page views
			GROUP BY day";

		return $output;
    };

	// Create temp table
	$sql = "CREATE TEMPORARY TABLE temp_union(
		day date NULL";
	// Add column for user "dummy"
	$sql .= ", dummy float NULL";
	foreach( $competitors as $competitorTeamName => $competitorUsernames ){
		$sql .= ", {$competitorTeamName} float NULL";
	}
	$sql .= " )ENGINE = MEMORY";

    $res = $dbr->query( $sql );

    // Add column with dummy user to generate a 0 value for every day during comparison period
    // For this function I removed WHERE condition limiting time window to competitors; just use entire wiki history
   //  $sql = "SELECT
			// 	DATE(hit_timestamp) AS day
			// FROM wiretap
			// ORDER BY hit_timestamp ASC
			// LIMIT 1";

	// Use this instead of above to make x axis span life of wiki, not life of Extension:Wiretap
	$sql = "SELECT
			DATE(rev_timestamp) AS day
		FROM $revTable
		ORDER BY rev_timestamp ASC
		LIMIT 1";

	$res = $dbr->query( $sql );
    $row = $dbr->fetchRow( $res );
    $firstContributionDateFromGroup = $row['day'];

    $lastDate = date("Ymd", time()); // Today as YYYYMMDD
    $firstDate = date('Ymd', strtotime( $firstContributionDateFromGroup ) ); // Date of first contribution from users in this group
    $date = $firstDate;
    while( $date <= $lastDate ){
    	$dateTime = date('Y-m-d', strtotime($date * 1000000) ); // Append 0 value for HHMMSS to match timestamp format in revision table

			// $sql = $queryDT('dummy', $dateTime);
			$sql = "INSERT INTO temp_union (day, dummy) VALUES ('{$dateTime}', '0')";

			$res = $dbr->query( $sql );

			$date = date('Ymd', strtotime($date . ' +1 day') );
    }

	// Add each competitor's score to temp table
	foreach( $competitors as $competitorTeamName => $competitorUsernames ){
		$sql = $queryDT($competitorTeamName, $competitorUsernames, $wiretapTable);

		$res = $dbr->query( $sql );
	}

	// Consolidate rows so each day only has one row
    $sql = "SELECT
			day";
		foreach( $competitors as $competitorTeamName => $competitorUsernames ){
			$sql .= ", max({$competitorTeamName}) {$competitorTeamName}";
		}
		$sql .= " FROM temp_union GROUP BY day";

    $res = $dbr->query( $sql );

	while( $row = $dbr->fetchRow( $res ) ) {

		foreach( $competitors as $competitorTeamName => $competitorUsernames ){

			list($day, $score) = array($row['day'], $row["$competitorTeamName"]);

			$userdata["$competitorTeamName"][] = array(
				'x' => strtotime( $day ) * 1000,
				'y' => floatval( $score ),
			);
		}

    }

		// Remove temp table
    $sql = "DROP TABLE temp_union";
    $res = $dbr->query ( $sql );

    foreach( $competitors as $competitorTeamName => $competitorUsernames ){

	    $person = User::newFromName("$competitorTeamName");
			$realName = $person->getRealName();
			if( empty($realName) ){
				$nameToUse = $competitorTeamName;
			} else {
				$nameToUse = $realName;
			}

	    $data[] = array(
    		'key' => $nameToUse,
    		'values' => $userdata["$competitorTeamName"],
  		);

    }


		$html = '';
		$html .= '<h2>Stacked Area</h2>';
	  $html .= '<div id="userjourney-compare-chart-stacked"><svg height="400px"></svg></div>';
		// $html .= '<h2>Stacked Area Stream</h2>';
	 //  $html .= '<div id="userjourney-compare-chart-stream"><svg height="400px"></svg></div>';
    $html .= "<script type='text/template-json' id='userjourney-data'>" . json_encode( $data ) . "</script>";

    $wgOut->addHTML( $html );
  }












}

class UserJourneyPager extends ReverseChronologicalPager {
	protected $rowCount = 0;
	public $filterUser;
	public $filterPage;

	function __construct() {
		parent::__construct();
		// global $wgRequest;
		// $this->filterUsers = $wgRequest->getVal( 'filterusers' );
		// $this->filterUserList = explode("|", $this->filterUsers);
		// $this->ignoreUsers = $wgRequest->getVal( 'ignoreusers' );
		// $this->ignoreUserList = explode("|", $this->ignoreUsers);
	}

	function getIndexField() {
		return "hit_timestamp";
	}

	function getExtraSortFields() {
		return array();
	}

	function isNavigationBarShown() {
		return true;
	}

	function getQueryInfo() {
		$conds = array();
		// if ( $this->filterUsers ) {
			// $includeUsers = "user_name in ( '";
			// $includeUsers .= implode( "', '", $this->filterUserList ) . "')";
			// $conds[] = $includeUsers;
		// }
		// if ( $this->ignoreUsers ) {
			// $excludeUsers = "user_name not in ( '";
			// $excludeUsers .= implode( "', '", $this->ignoreUserList ) . "')";
			// $conds[] = $excludeUsers;
		// }

		if ( $this->filterUser ) {
			$conds[] = "user_name = '{$this->filterUser}'";
		}
		if ( $this->filterPage ) {
			$conds[] = "page_name = '{$this->filterPage}'";
		}

		return array(
			'tables' => 'userjourney',
			'fields' => array(
				'page_id',
				'page_name',
				'user_name',
				// "concat(substr(hit_timestamp, 1, 4),'-',substr(hit_timestamp,5,2),'-',substr(hit_timestamp,7,2),' ',substr(hit_timestamp,9,2),':',substr(hit_timestamp,11,2),':',substr(hit_timestamp,13,2)) AS hit_timestamp",
				'hit_timestamp',
				'referer_title',
				'user_points',
				'user_badges',
				'user_actions',
				'page_action',
			),
			'conds' => $conds
		);
	}

	function formatRow( $row ) {
		$userPage = Title::makeTitle( NS_USER, $row->user_name );
		$name = $this->getSkin()->makeLinkObj( $userPage, htmlspecialchars( $userPage->getText() ) );


		if ( $this->filterUser ) {
			// do nothing for now...
		}
		else {
			$url = Title::newFromText('Special:UserJourney')->getLocalUrl(
				array( 'filterUser' => $row->user_name )
			);
			$msg = wfMsg( 'userjourney-filteruser' );

			$name .= ' (' . Xml::element(
				'a',
				array( 'href' => $url ),
				$msg
			) . ')';
		}


		$pageTitle = Title::newFromID( $row->page_id );
		if ( ! $pageTitle )
			$pageTitle = Title::newFromText( $row->page_name );

		if ( ! $pageTitle )
			$page = $row->page_name; // if somehow still no page, just show text
		else
			$page = $this->getSkin()->link( $pageTitle );


		if ( $this->filterPage ) {
			// do nothing for now...
		}
		else {
			$url = Title::newFromText('Special:UserJourney')->getLocalUrl(
				array( 'filterPage' => $row->page_name )
			);
			$msg = wfMsg( 'userjourney-filterpage' );

			$page .= ' (' . Xml::element(
				'a',
				array( 'href' => $url ),
				$msg
			) . ')';
		}

		if ( $row->referer_title ) {
			$referer = Title::newFromText( $row->referer_title );
			$referer = $this->getSkin()->link( $referer );
		}
		else
			$referer = '';

		$points = $row->user_points;
		$badge = $row->user_badges;
		$pageAction = $row->page_action;
		$userAction = $row->user_actions;

		global $wgLang;
		$timestamp = $wgLang->timeanddate( wfTimestamp( TS_MW, $row->hit_timestamp ), true );

		return "<tr><td>$name</td><td>$page</td><td>$timestamp</td><td>$referer</td><td>$pageAction</td><td>$points</td><td>$badge</td><td>$userAction</td></tr>\n";
	}

	function getForm() {
		$out = '<form name="filteruser" id="filteruser" method="post">';
		$out .='Usernames: <input type="text" name="filterusers" value="' . $this->filterUsers . '">';
		$out .='<input type="submit" value="Filter">';
		$out .='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		$out .='Usernames: <input type="text" name="ignoreusers" value="' . $this->ignoreUsers . '">';
		$out .='<input type="submit" value="Exclude">';
		$out .='</form><br /><hr /><br />';
		return $out;
	}

	//
	// Preserve filter offset parameters when paging
	// @return array
	//
	function getDefaultQuery() {
		$query = parent::getDefaultQuery();
		// if( $this->filterUsers != '' )
			// $query['filterusers'] = $this->filterUsers;
		// if( $this->ignoreUsers != '' )
			// $query['ignoreusers'] = $this->ignoreUsers;
		return $query;
	}

}

