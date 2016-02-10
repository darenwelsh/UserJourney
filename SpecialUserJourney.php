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

		if ($this->mMode == 'user-score-data') {
			$this->myScoreData();
		}
		else if ( $this->mMode == 'user-score-plot' ) {
      $this->myScorePlot();
		}

		if ($this->mMode == 'compare-score-data') {
			$this->compareScoreData();
		}
		else if ( $this->mMode == 'compare-score-plot' ) {
      $this->compareScoreLineWindowPlot();
		}
		else if ( $this->mMode == 'compare-score-stacked-plot' ) {
      $this->compareScoreStackedPlot();
		}
		else if ( $this->mMode == 'compare-score-stacked-plot2' ) {
      $this->compareScoreStackedPlot2();
		}
		else if ( $this->mMode == 'compare-score-stacked-plot3' ) {
      $this->compareScoreStackedPlot3();
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

		$navLine .= "<li>" . wfMessage( 'userjourney-myscore' )->text()
			. ": (" . $this->createHeaderLink( 'userjourney-rawdata', 'user-score-data' )
			. ") (" . $this->createHeaderLink( 'userjourney-plot', 'user-score-plot' )
			. ")</li>";

		$navLine .= "<li>" . wfMessage( 'userjourney-comparescore' )->text()
			. ": (" . $this->createHeaderLink( 'userjourney-rawdata', 'compare-score-data' )
			. ") (" . $this->createHeaderLink( 'userjourney-plot', 'compare-score-plot' )
			. ") (" . $this->createHeaderLink( 'userjourney-plot', 'compare-score-stacked-plot' )
			. ") (" . $this->createHeaderLink( 'userjourney-plot', 'compare-score-stacked-plot2' )
			. ") (" . $this->createHeaderLink( 'userjourney-plot', 'compare-score-stacked-plot3' )
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

		$html .= '<p>Test</p>';
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

		$wgOut->setPageTitle( "UserJourney: User Score Data for $displayName" );

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
    global $wgOut;

    $username = $this->getUser()->mName;
    $userRealName = $this->getUser()->mRealName;
    if( $userRealName ){
    	$displayName = $userRealName;
    }
    else{
    	$displayName = $username;
    }

    $wgOut->setPageTitle( "UserJourney: User Score Plot for $displayName" );
    $wgOut->addModules( 'ext.userjourney.myScorePlot.nvd3' );

    $html = '<div id="userjourney-chart"><svg height="400px"></svg></div>';

    $dbr = wfGetDB( DB_SLAVE );

    $sql = "SELECT
              DATE(rev_timestamp) AS day,
              COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 AS score
            FROM `revision`
            WHERE
              rev_user_text IN ( '$username' )
              /* AND rev_timestamp > 20150101000000 */
            GROUP BY day
            ORDER BY day DESC";

    $res = $dbr->query( $sql );

    $previous = null;

    while( $row = $dbr->fetchRow( $res ) ) {

      list($day, $score) = array($row['day'], $row['score']);

      // $day = strtotime( $day ) * 1000;

      $data[] = array(
        'x' => strtotime( $day ) * 1000,
        'y' => floatval( $score ),
      );
    }

    $data = array(
      array(
        'key' => 'Daily Score',
        'values' => $data,
      ),
    );

    $html .= "<script type='text/template-json' id='userjourney-data'>" . json_encode( $data ) . "</script>";

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



	public function compareScoreData2() { // backup of what works during testing
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

		$wgOut->setPageTitle( "UserJourney: Compare scores: $displayName vs. TBD" );

		$html = '<table class="wikitable sortable"><tr><th>Date</th><th>' . $displayName . '</th><th>' . 'TBD' . '</th></tr>';

		$dbr = wfGetDB( DB_SLAVE );

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
									COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 AS user_score
								FROM `revision`
								WHERE
									rev_user_text IN ( '$username' )
								GROUP BY user_day
								) user
							LEFT JOIN
							(
								SELECT
									DATE(rev_timestamp) AS user2_day,
									COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 AS user2_score
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
									COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 AS user_score
								FROM `revision`
								WHERE
									rev_user_text IN ( '$username' )
								GROUP BY user_day
							) user
							RIGHT JOIN
							(
								SELECT
									DATE(rev_timestamp) AS user2_day,
									COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 AS user2_score
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




  /**
  * Function generates stacked area plot of contribution scores
  *
  * @param $tbd - no parameters now
  * @return nothing - generates special page
  */
  function compareScoreStackedPlot( ){
    global $wgOut;

    $username = $this->getUser()->mName;
    $userRealName = $this->getUser()->mRealName;
    if( $userRealName ){
    	$displayName = $userRealName;
    }
    else{
    	$displayName = $username;
    }

    $competitors = array( // TO-DO: move this array to where func it called and pass as parameter
    	$username,
    	'Ejmontal',
    	// 'Swray'
    	);
    $username1 = $username;
    $username2 = 'Ejmontal';

    $wgOut->setPageTitle( "UserJourney: Score comparison plot" );
    $wgOut->addModules( 'ext.userjourney.compareScoreStackedPlot.nvd3' );

    $html = '<div id="userjourney-chart"><svg height="400px"></svg></div>';

    $dbr = wfGetDB( DB_SLAVE );

    // COUNT(rev_id) = Revisions
    // COUNT(DISTINCT rev_page) = Pages
    // COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 = Score

    $queryScore = "COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2"; // How to calculate score
    $queryDT1 = "(
								SELECT
									DATE(rev_timestamp) AS user_day1,
									{$queryScore} AS user_score1
								FROM `revision`
								WHERE
									rev_user_text IN ( '$username1' )
								GROUP BY user_day1
								) user1";

		$queryDT2 = "(
								SELECT
									DATE(rev_timestamp) AS user_day2,
									{$queryScore} AS user_score2
								FROM `revision`
								WHERE
									rev_user_text IN ( '$username2' )
								GROUP BY user_day2
								) user2";

    $sql = "SELECT
							COALESCE(user_day1, user_day2) AS day,
							user_score1,
							user_score2
						FROM
						(
							SELECT * FROM $queryDT1
							LEFT JOIN $queryDT2	ON user1.user_day1=user2.user_day2
							UNION
							SELECT * FROM $queryDT1
							RIGHT JOIN $queryDT2 ON user1.user_day1=user2.user_day2
						)results
						ORDER BY day ASC";

    $res = $dbr->query( $sql );

		while( $row = $dbr->fetchRow( $res ) ) {

      list($day, $userScore1, $userScore2) = array($row['day'], $row['user_score1'], $row['user_score2']);

      $userdata["$username1"][] = array(
				'x' => strtotime( $day ) * 1000,
				'y' => floatval( $userScore1 ),
			);

			$userdata["$username2"][] = array(
				'x' => strtotime( $day ) * 1000,
				'y' => floatval( $userScore2 ),
			);

    }

    foreach( $competitors as $competitor ){

	    $data[] = array(
    		'key' => $competitor,
    		'values' => $userdata["$competitor"],
  		);

    }


    $html .= "<script type='text/template-json' id='userjourney-data'>" . json_encode( $data ) . "</script>";

    $wgOut->addHTML( $html );
  }




  /**
  * Function generates stacked area plot of contribution scores
  *
  * @param $tbd - no parameters now
  * @return nothing - generates special page
  */
  function compareScoreStackedPlot2( ){
    global $wgOut;

    $username = $this->getUser()->mName;
    $userRealName = $this->getUser()->mRealName;
    if( $userRealName ){
    	$displayName = $userRealName;
    }
    else{
    	$displayName = $username;
    }

    $competitors = array( // TO-DO: move this array to where func it called and pass as parameter
    	$username,
    	'Ejmontal',
    	'Swray'
    	);
    $username1 = $competitors[0];
    $username2 = $competitors[1];
    $username3 = $competitors[2];

    $wgOut->setPageTitle( "UserJourney: Score comparison plot" );
    $wgOut->addModules( 'ext.userjourney.compareScoreStackedPlot.nvd3' );

    $html = '<div id="userjourney-chart"><svg height="400px"></svg></div>';
    $html .= '<div id="userjourney-chart-stream"><svg height="400px"></svg></div>';

    $dbr = wfGetDB( DB_SLAVE );

    $queryScore = "COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2"; // How to calculate score
    $queryDT1 = "(
								SELECT
									DATE(rev_timestamp) AS user_day1,
									{$queryScore} AS user_score1
								FROM `revision`
								WHERE
									rev_user_text IN ( '$username1' )
								GROUP BY user_day1
								) user1";

		$queryDT2 = "(
								SELECT
									DATE(rev_timestamp) AS user_day2,
									{$queryScore} AS user_score2
								FROM `revision`
								WHERE
									rev_user_text IN ( '$username2' )
								GROUP BY user_day2
								) user2";

		$queryDT3 = "(
								SELECT
									DATE(rev_timestamp) AS user_day3,
									{$queryScore} AS user_score3
								FROM `revision`
								WHERE
									rev_user_text IN ( '$username3' )
								GROUP BY user_day3
								) user3";

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

    $sql = "SELECT
							COALESCE(user_day1, user_day2, user_day3) AS day,
							user_score1,
							user_score2,
							user_score3
						FROM
						(
							SELECT * FROM $queryDT1
							LEFT JOIN $queryDT2 ON user1.user_day1=user2.user_day2
							LEFT JOIN $queryDT3 ON user2.user_day2=user3.user_day3
							UNION
							SELECT * FROM $queryDT1
							RIGHT JOIN $queryDT2 ON user1.user_day1=user2.user_day2
							LEFT JOIN $queryDT3 ON user2.user_day2=user3.user_day3
							UNION
							SELECT * FROM $queryDT1
							RIGHT JOIN $queryDT2 ON user1.user_day1=user2.user_day2
							RIGHT JOIN $queryDT3 ON user2.user_day2=user3.user_day3
						)results
						ORDER BY day ASC";

    $res = $dbr->query( $sql );

		while( $row = $dbr->fetchRow( $res ) ) {

      list($day, $userScore1, $userScore2, $userScore3) = array($row['day'], $row['user_score1'], $row['user_score2'], $row['user_score3']);

      $userdata["$username1"][] = array(
				'x' => strtotime( $day ) * 1000,
				'y' => floatval( $userScore1 ),
			);

			$userdata["$username2"][] = array(
				'x' => strtotime( $day ) * 1000,
				'y' => floatval( $userScore2 ),
			);

			$userdata["$username3"][] = array(
				'x' => strtotime( $day ) * 1000,
				'y' => floatval( $userScore3 ),
			);

    }

    foreach( $competitors as $competitor ){

	    $data[] = array(
    		'key' => $competitor,
    		'values' => $userdata["$competitor"],
  		);

    }


    $html .= "<script type='text/template-json' id='userjourney-data'>" . json_encode( $data ) . "</script>";

    $wgOut->addHTML( $html );
  }




function compareScoreStackedPlot3( ){
    global $wgOut;

    $username = $this->getUser()->mName;
    $userRealName = $this->getUser()->mRealName;
    if( $userRealName ){
    	$displayName = $userRealName;
    }
    else{
    	$displayName = $username;
    }

    $competitors = array( // TO-DO: move this array to where func it called and pass as parameter
    	// $username,
			'Abattocl',
			'Abolinge',
			'Ajarvis',
			'Akanelak',
			'Apdecker',
			'Athomaso',
			'Balpert',
			'Bmader',
			'Bscheib',
			'Cmavridi',
			'Cmundy',
			'Dcoan',
			'Dmbarret',
			'Dsimon',
			'Ecandrew',
			'Egslusse',
			'Ejmontal',
			'Fsabur',
			'Gsbrown',
			'Jgaustad',
			'Jkagey',
			'Jmularsk',
			'Jstoffel',
			'Keversle',
			'Kgjohns',
			'Lbolch',
			'Lshore',
			'Lwelsh',
			'Lwilt',
			'Mbbollin',
			'Mdino',
			'Mdumanta',
			'Mrmurphe',
			'Mwillsey',
			'Pdum',
			'Rcheney',
			'Sfletch',
			'Sgeffert',
			'Skorona',
			'Smulhern',
			'Ssjohns',
			'Svilano',
			'Swray',
			'Tahall',
			'Tbcampbe',
			'Tjlindse',
    	);

    $wgOut->setPageTitle( "UserJourney: Score comparison plot" );
    $wgOut->addModules( 'ext.userjourney.compareScoreStackedPlot.nvd3' );

    $html = '<div id="userjourney-chart"><svg height="400px"></svg></div>';
    $html .= '<div id="userjourney-chart-stream"><svg height="400px"></svg></div>';

    $dbr = wfGetDB( DB_SLAVE );

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
		foreach( $competitors as $competitor ){
			$sql .= ", {$competitor} float NULL";
		}
		$sql .= " )ENGINE = MEMORY";

    $res = $dbr->query( $sql );

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


    $html .= "<script type='text/template-json' id='userjourney-data'>" . json_encode( $data ) . "</script>";

    $wgOut->addHTML( $html );
  }







  function BACKUPcompareScoreStackedPlot( ){
    global $wgOut;

    $username = $this->getUser()->mName;
    $userRealName = $this->getUser()->mRealName;
    if( $userRealName ){
    	$displayName = $userRealName;
    }
    else{
    	$displayName = $username;
    }

    $competitors = array( // TO-DO: move this array to where func it called and pass as parameter
    	$username,
    	'Ejmontal',
    	// 'Swray'
    	);
    $username2 = 'Ejmontal';

    $wgOut->setPageTitle( "UserJourney: Score comparison plot" );
    $wgOut->addModules( 'ext.userjourney.compareScoreStackedPlot.nvd3' );

    $html = '<div id="userjourney-chart"><svg height="400px"></svg></div>';

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
						ORDER BY day ASC";

    $res = $dbr->query( $sql );

		while( $row = $dbr->fetchRow( $res ) ) {

      list($day, $userScore, $user2Score) = array($row['day'], $row['user_score'], $row['user2_score']);

      $userdata["$username"][] = array(
				'x' => strtotime( $day ) * 1000,
				'y' => floatval( $userScore ),
			);

			$userdata["$username2"][] = array(
				'x' => strtotime( $day ) * 1000,
				'y' => floatval( $user2Score ),
			);

    }

    foreach( $competitors as $competitor ){

	    $data[] = array(
    		'key' => $competitor,
    		'values' => $userdata["$competitor"],
  		);

    }


    $html .= "<script type='text/template-json' id='userjourney-data'>" . json_encode( $data ) . "</script>";

    $wgOut->addHTML( $html );
  }




  /**
  * Function generates plot of contribution score for logged-in user over time
  *
  * @param $tbd - no parameters now
  * @return nothing - generates special page
  */
  function compareScoreLineWindowPlot( ){ // TO-DO: rename to something meaningful
    global $wgOut;

    $username = $this->getUser()->mName;
    $userRealName = $this->getUser()->mRealName;
    if( $userRealName ){
    	$displayName = $userRealName;
    }
    else{
    	$displayName = $username;
    }

    $competitors = array( // TO-DO: move this array to where func it called and pass as parameter
    	$username,
    	'Ejmontal',
    	'Swray'
    	);

    $wgOut->setPageTitle( "UserJourney: Score comparison plot" );
    $wgOut->addModules( 'ext.userjourney.compareScorePlot.nvd3' );

    $html = '<div id="userjourney-chart"><svg height="400px"></svg></div>';

    $dbr = wfGetDB( DB_SLAVE );

    $data = array();

    foreach( $competitors as $competitor ){
    	$sql = "SELECT
	              DATE(rev_timestamp) AS day,
	              COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 AS score
	            FROM `revision`
	            WHERE
	              rev_user_text IN ( '$competitor' )
	              /* AND rev_timestamp > 20150101000000 */
	            GROUP BY day
	            ORDER BY day DESC";

	    $res = $dbr->query( $sql );

	    while( $row = $dbr->fetchRow( $res ) ) {

	      list($day, $score) = array($row['day'], $row['score']);

	      $userdata[] = array(
	        'x' => strtotime( $day ) * 1000,
	        'y' => floatval( $score ),
	      );

	    }

	    $data[] = array(
      		'key' => $competitor,
      		'values' => $userdata,
      		);

	    $userdata = NULL; // without this, 2nd person gets 1st person's data plus theirs
	  }

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

