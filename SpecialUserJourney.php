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

		if ($this->mMode == 'user-activity-data') {
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
		// else if ( $this->mMode == 'compare-activity-stacked-plot' ) {
      // $this->compareScoreStackedPlot();
      // $this->compareSimilarScoresPlots();
		// }
		// else if ( $this->mMode == 'compare-activity-stacked-plot2' ) {
  //     $this->compareScoreStackedPlot2();
		// }
		else if ( $this->mMode == 'compare-activity-by-user-group' ) {
      $this->compareScoreByUserGroup();
		}
		else {
			$this->overview();
		}
	}

	public function getPageHeader() {
		global $wgRequest;

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

		//TO-DO add if statement to show extra data plots if logged-in user is in groups sysop or Manager
		//TO-DO add pull-down menus so these views can show any user's data for sysop or Manager

		$navLine .= "<li>" . wfMessage( 'userjourney-my-activity' )->text()
			. ": (" . $this->createHeaderLink( 'userjourney-rawdata', 'user-activity-data' )
			. ") (" . $this->createHeaderLink( 'userjourney-plot', 'user-activity-plot' )
			. ")</li>";

		$navLine .= "<li>" . wfMessage( 'userjourney-compare-activity' )->text()
			// . ": (" . $this->createHeaderLink( 'userjourney-rawdata', 'compare-activity-data' ) // not currently displayed, maybe later for admins/Managers
			. ": (" . $this->createHeaderLink( 'userjourney-plot-by-peers', 'compare-activity-by-similar-activity' )
			// . ") (" . $this->createHeaderLink( 'userjourney-plot', 'compare-activity-stacked-plot' )
			// . ") (" . $this->createHeaderLink( 'userjourney-plot', 'compare-activity-stacked-plot2' )
			. ") (" . $this->createHeaderLink( 'userjourney-plot-by-group', 'compare-activity-by-user-group' )
			. ")</li>";

		$navLine .= "</ul>";

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

	public function overview () {
		global $wgOut, $wgRequest;

		$wgOut->setPageTitle( 'UserJourney' );

		$pager = new UserJourneyPager();
		$pager->filterUser = $wgRequest->getVal( 'filterUser' );
		$pager->filterPage = $wgRequest->getVal( 'filterPage' );

		$body = $pager->getBody();
		$html = '';

		$wgOut->addHTML( $html );
	}



	public function myScoreData() {
		global $wgOut;

    $username = $this->getUser()->mName;
    $userRealName = $this->getUser()->mRealName;
    if( $userRealName ){
    	$displayName = $userRealName;
    }
    else{
    	$displayName = $username;
    }

		$wgOut->setPageTitle( "UserJourney: Activity Data for $displayName" );

		$html = '<table class="wikitable sortable"><tr><th>Date</th><th>Score</th><th>Pages</th><th>Revisions</th></tr>';

		$dbr = wfGetDB( DB_SLAVE );

    $sql = "SELECT
              DATE(rev_timestamp) AS day,
              COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 AS score,
              COUNT(DISTINCT rev_page) as pages,
              COUNT(rev_id) as revisions
            FROM `revision`
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
    $userRealName = $this->getUser()->mRealName;
    if( $userRealName ){
    	$displayName = $userRealName;
    }
    else{
    	$displayName = $username;
    }

    $wgOut->setPageTitle( "UserJourney: Activity Plot for $displayName" );

    if( $this->getUser()->getID() ){ // Only do stuff if user has an ID

	    $wgOut->addModules( 'ext.userjourney.myActivity.nvd3' );

	    $dbr = wfGetDB( DB_SLAVE );

			$competitors = array($username);

	    // $queryScore = "COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2"; // How to calculate score

	    $queryDT = function( $competitor ){
	    	$output = "INSERT INTO temp_union (day, {$competitor})
				SELECT
					DATE(rev_timestamp) AS day,
					COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 AS {$competitor}
				FROM `revision`
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
				FROM revision
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
				$sql = $queryDT($competitor);

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
	  }

    $wgOut->addHTML( $html );
  }









	public function compareScoreData() {
		global $wgOut;

    $username = $this->getUser()->mName;
    $userRealName = $this->getUser()->mRealName;
    if( $userRealName ){
    	$displayName = $userRealName;
    }
    else{
    	$displayName = $username;
    }

    $username2 = 'Ejmontal'; //Competitor
    $competitors = array( // TO-DO: move this array to where func it called and pass as parameter
    	$username,
    	'Ejmontal',
    	'Swray'
    	);

		// set each person's score query as a variable to further simplify
		// allow for 3 people http://stackoverflow.com/questions/2384298/why-does-mysql-report-a-syntax-error-on-full-outer-join
		// with two tables t1, t2:

		// SELECT * FROM t1
		// LEFT JOIN t2 ON t1.id = t2.id
		// UNION
		// SELECT * FROM t1
		// RIGHT JOIN t2 ON t1.id = t2.id

		// with three tables t1, t2, t3:

		// SELECT * FROM t1
		// LEFT JOIN t2 ON t1.id = t2.id
		// LEFT JOIN t3 ON t2.id = t3.id
		// UNION
		// SELECT * FROM t1
		// RIGHT JOIN t2 ON t1.id = t2.id
		// LEFT JOIN t3 ON t2.id = t3.id
		// UNION
		// SELECT * FROM t1
		// RIGHT JOIN t2 ON t1.id = t2.id
		// RIGHT JOIN t3 ON t2.id = t3.id

		$james = User::newFromName("Ejmontal");
		$name2 = $james->getRealName();

		$wgOut->setPageTitle( "UserJourney: Compare scores: $displayName vs. $name2" );

		$html = '<table class="wikitable sortable"><tr><th>Date</th><th>' . $displayName . '</th><th>' . 'TBD' . '</th></tr>';

		$dbr = wfGetDB( DB_SLAVE );

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
								FROM `revision`
								WHERE
									rev_user_text IN ( '$username' )
								GROUP BY user_day
								) user
							LEFT JOIN
							(
								SELECT
									DATE(rev_timestamp) AS user2_day,
									{$queryScore} AS user2_score
								FROM `revision`
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
								FROM `revision`
								WHERE
									rev_user_text IN ( '$username' )
								GROUP BY user_day
							) user
							RIGHT JOIN
							(
								SELECT
									DATE(rev_timestamp) AS user2_day,
									{$queryScore} AS user2_score
								FROM `revision`
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






function compareScoreByUserGroup( ){
		//TO-DO add dropdown menu to select groups (but hide Viewer and Contributor and any groups > x people )
    global $wgOut;

    $userGroup = "sysop"; // CX3, sysop, Curator, Manager, Beta-tester, use Contributor with caution

    $username = $this->getUser()->mName;
    $userRealName = $this->getUser()->mRealName;
    if( $userRealName ){
    	$displayName = $userRealName;
    }
    else{
    	$displayName = $username;
    }

    $competitors = array();

    $wgOut->setPageTitle( "UserJourney: Score comparison plot" );
    $wgOut->addModules( 'ext.userjourney.compare.nvd3' );

    $dbr = wfGetDB( DB_SLAVE );

		// Determine list of competitors based on $userGroup
    $sql = "
			SELECT
				user_name
			FROM (
			SELECT
				ug_user,
				ug_group
			FROM user_groups
			WHERE ug_group = '{$userGroup}'
			) a
			 JOIN
			(
			SELECT
				user_id,
				user_name
			FROM user
			) b
			ON ug_user=user_id
    ";

    $res = $dbr->query( $sql );

		while( $row = $dbr->fetchRow( $res ) ) {

				$competitors[] = $row['user_name'];

    }

    // $queryScore = "COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2"; // How to calculate score

    $queryDT = function( $competitor ){
    	$output = "INSERT INTO temp_union (day, {$competitor})
			SELECT
				DATE(rev_timestamp) AS day,
				COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 AS {$competitor}
			FROM `revision`
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
			FROM revision
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
			$sql = $queryDT($competitor);

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
		$html .= '<h2>Stacked Area Stream</h2>';
	  $html .= '<div id="userjourney-compare-chart-stream"><svg height="400px"></svg></div>';
    $html .= "<script type='text/template-json' id='userjourney-data'>" . json_encode( $data ) . "</script>";

    $wgOut->addHTML( $html );
  }






function compareActivityByPeers( ){
		// TO-DO Modify plots to have some granular/moving-average and some just showing 1-month or 3-month average values
		// TO-DO Maybe have one page with all data and another page with last 30-60 days
    global $wgOut;

    $daysToDetermineCompetitors = 14; // Number of days in which to compare scores of logged-in user against others (used to find suitable competitors)
    $daysToPlot = 365;
    $daysToDetermineCompetitors += 100; //TO-DO remove - number increased for testing on old wiki
    $daysToPlot += 100; //TO-DO remove - number increased for testing on old wiki

    $username = $this->getUser()->mName;
    $userRealName = $this->getUser()->mRealName;
    if( $userRealName ){
    	$displayName = $userRealName;
    }
    else{
    	$displayName = $username;
    }

    $competitors = array( // For this function, start with only the logged-in user. More are added later.
    	$username,
    	);

    $wgOut->setPageTitle( "UserJourney: Score comparison plot" ); //TO-DO is this even doing anything?

		if( $this->getUser()->getID() ){ // Only do stuff if user has an ID

	    $wgOut->addModules( 'ext.userjourney.compare.nvd3' );
	    // $wgOut->addModules( 'ext.userjourney.compareScoreStackedPlot.nvd3' );

	    $dbr = wfGetDB( DB_SLAVE );

	    // $queryScore = "COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2"; // How to calculate score

	    // Determine score of logged in user
			$date = time() - ( 60 * 60 * 24 * $daysToDetermineCompetitors );
			$dateString = $dbr->timestamp( $date );

	    $sql = "
	    	SELECT COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 as score
	      FROM revision
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
					FROM user u
					JOIN
					(SELECT rev_user,
						COUNT(DISTINCT rev_page) AS page_count,
						COUNT(rev_id) AS rev_count
						FROM revision
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

	    $queryDT = function( $competitor, $dateString ){
	    	$output = "INSERT INTO temp_union (day, {$competitor})
				SELECT
					DATE(rev_timestamp) AS day,
					COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 AS {$competitor}
				FROM `revision`
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

				$sql = $queryDT($competitor, $dateString);

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
			$html .= '<h2>Stacked Area Stream</h2>';
	    $html .= '<div id="userjourney-compare-chart-stream"><svg height="400px"></svg></div>';

	    $html .= "<script type='text/template-json' id='userjourney-data'>" . json_encode( $data ) . "</script>";
		} else {
			$html = '<br />Sorry, but this feature is not available for anonymous users.<br />';
		}
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

