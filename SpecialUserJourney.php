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

		$wgOut->setPageTitle( "UserJourney: Compare scores: $displayName vs. TBD" );

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
  * Function generates plot of contribution score for logged-in user over time
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
    $username2 = 'Ejmontal';

    $wgOut->setPageTitle( "UserJourney: Score comparison plot" );
    $wgOut->addModules( 'ext.userjourney.compareScoreStackedPlot.nvd3' );

    $html = '<div id="userjourney-chart"><svg height="400px"></svg></div>';

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


		// DEBUG
		print_r('--------------------------------------------------');
		print_r('test');
		echo '<br />';
		print_r('--------------------------------------------------');
		// END DEBUG







    $html .= "<script type='text/template-json' id='userjourney-data'>" . json_encode( $data ) . "</script>";

    $wgOut->addHTML( $html );
  }



  /*
	* This is MAJOR BROKE for now. I think part of it is handling the start and end dates as just the date.
	*	Since timestamps also include a time of day, I think it's preventing the association of the query results
	* into the fullData array.
  */
  function BROKENcompareScoreStackedPlot( ){
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

    $wgOut->setPageTitle( "UserJourney: Score comparison plot" );
    $wgOut->addModules( 'ext.userjourney.compareScoreStackedPlot.nvd3' );

    $html = '<div id="userjourney-chart"><svg height="400px"></svg></div>';

    $dbr = wfGetDB( DB_SLAVE );

    $data = array();

		// Determine start and end date based on competitors
		$firstDay = null;
		$lastDay = null;
    foreach( $competitors as $competitor ){
    	// Determine start date
    	$sql = "SELECT
	              DATE(rev_timestamp) AS firstDay
	            FROM `revision`
	            WHERE
	              rev_user_text IN ( '$competitor' )
	              /* AND rev_timestamp > 20150101000000 */
              ORDER BY firstDay ASC
	            LIMIT 1";

	    $res = $dbr->query( $sql );

	    $row = $dbr->fetchRow( $res );
	    $competitorFirstDay = strtotime( $row['firstDay'] ) * 1000;

	    if( isset( $firstDay ) ){

	    	if( $competitorFirstDay < $firstDay ){
		    	$firstDay = $competitorFirstDay;
	    	}

	    } else {
	    	$firstDay = $competitorFirstDay;
	    }

    	// Determine end date
    	$sql = "SELECT
	              DATE(rev_timestamp) AS lastDay
	            FROM `revision`
	            WHERE
	              rev_user_text IN ( '$competitor' )
	              /* AND rev_timestamp > 20150101000000 */
              ORDER BY lastDay DESC
	            LIMIT 1";

	    $res = $dbr->query( $sql );

	    $row = $dbr->fetchRow( $res );
	    $competitorLastDay = strtotime( $row['lastDay'] ) * 1000;

	    if( isset( $lastDay ) ){

	    	if( $competitorLastDay > $lastDay ){
		    	$lastDay = $competitorLastDay;
	    	}

	    } else {
	    	$lastDay = $competitorLastDay;
	    }

	  }

	  $res = null;

		// DEBUG
		print_r('--------------------------------------------------');
		print_r($firstDay . ' - ' . $lastDay);
		echo '<br />';
		print_r('--------------------------------------------------');
		print_r(date('Y-m-d H:m:s', ($firstDay / 1000) ) . ' - ' . date('Y-m-d', ($lastDay / 1000) ) );
		echo '<br />';
		print_r('--------------------------------------------------');
		print_r(strtotime( date('Y-m-d H:m:s', ($firstDay / 1000) ) .  "+ 1 day" ) * 1000 );
		echo '<br />';
		print_r('--------------------------------------------------');
		//print_r(date('Y-m-d H:m:s', $firstDay "+1 day"));
		print_r(date('Y-m-d H:m:s', strtotime( date('Y-m-d H:m:s', ($firstDay / 1000) ) .  "+ 1 day" ) )  );
		// END DEBUG


	  // Generate array of keys from firstDay to lastDay
	  // $tempDay = $firstDay;
	  // while( $tempDay < $lastDay ){
			// $fullData[] = array(
			// 	'x' => $tempDay,
			// 	'y' => 0,
			// );

			// $tempDay = strtotime( date('Y-m-d H:m:s', ($tempDay / 1000 ) ) .  "+ 1 day" );

	  // }




    foreach($competitors as $competitor){
    	// Generate array of keys from firstDay to lastDay
		  $tempDay = $firstDay;
		  // while( $tempDay < $lastDay ){
				// $fullData[] = array(
				// 	'x' => $tempDay,
				// 	'y' => 0,
				// );

				// $tempDay = strtotime( date('Y-m-d H:m:s', ($tempDay / 1000 ) ) .  "+ 1 day" );

		  // }

		  while( $tempDay < $lastDay ){
				$fullData[$tempDay] = 0;

				$tempDay = strtotime( date('Y-m-d H:m:s', ($tempDay / 1000 ) ) .  "+ 1 day" ) * 1000;

		  }

    	$sql = "SELECT
	              DATE(rev_timestamp) AS day,
	              COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 AS score
	            FROM `revision`
	            WHERE
	              rev_user_text IN ( '$competitor' )
	              /* AND rev_timestamp > 20150101000000 */
	            GROUP BY day
	            ORDER BY day ASC";

	    $res = $dbr->query( $sql );

	    // while( $row = $dbr->fetchRow( $res ) ) {

	    //   list($day, $score) = array($row['day'], $row['score']);

	    //   $userdata[] = array(
	    //     'x' => strtotime( $day ) * 1000,
	    //     'y' => floatval( $score ),
	    //   );

	    // }

	    // replace previous while loop with this
	    $userdata = array();
	    while( $row = $dbr->fetchRow( $res ) ) {

	      list($day, $score) = array($row['day'], $row['score']);
	      $dayTimestamp = strtotime( $day ) * 1000;

	      $userdata[$dayTimestamp] = floatval( $score );

	    }

/*

	    // Parse $fullData keys from firstDay to lastDay; fill if value exists in $userdata
		  $tempDay = $firstDay;
		  while( $tempDay < $lastDay ){
				if( isset($userdata[$tempDay] ) ){
	    		$fullData[$tempDay] = $userdata[$tempDay];
	    	}

				$tempDay = strtotime( date('Y-m-d H:m:s', ($tempDay / 1000 ) ) .  "+ 1 day" );

		  }

		  // Convert $fullData into format used in other plots
		  $competitorData = array();
		  while( $dataRow = current($fullData) ){
		  	$competitorData[] = array(
        'x' => strtotime( key($fullData) ) * 1000,
        'y' => floatval( $dataRow ),
        );
		  };

*/

	    // $tempDay = $firstDay;
	    // $competitorData = array();
	    // while( $tempDay < $lastDay ){
	    // 	if( isset($userdata[$tempDay] ) ){
	    // 		$competitorData[] = array(
	    // 			'x' => $tempDay,
	    // 			'y' => ,
	    // 		);
	    // 	} else {
	    // 		$competitorData[] = array(
	    // 			'x' => $tempDay,
	    // 			'y' => 0,
	    // 		);
	    // 	}

	    // }
	    // end replacement stuff

	    // Determine if zero values should be added before this person's first day with a score
	    // if( $userdata[0].x < $firstDay ){
	    // 	$tempDay = $firstDay;
	    // 	while( $tempDay < $userdata[0].x ){
	    // 		$prependedDates[] = array(
	    // 			'x' => $tempDay,
	    // 			'y' => 0,
	    // 			);
	    // 		$tempDay->modify('+1 day');
	    // 	}

	    // 	// Add zero values for days from $firstDay to person's first day with a score
	    // 	array_unshift($userdata, $prependedDates);
	    // }

	    // Parse through the days and add zero value to missing dates
			// $tempDay = $firstDay;
			// while( $tempDay < $lastDay ){
			// 	if( true ){ // if null value on this day

			// 	}
			// 	$tempDay->modify('+1 day');
			// }

			// End of new stuff

	    $data[] = array(
      		'key' => $competitor,
      		// 'values' => $userdata,
      		'values' => $competitorData,
      		);

	    unset($competitorData);
	    unset($data);
	    $userdata = NULL; // without this, 2nd person gets 1st person's data plus theirs
	  }



    // $html .= "<script type='text/template-json' id='userjourney-data'>" . json_encode( $data ) . "</script>";
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

