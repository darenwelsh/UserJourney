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
      $this->compareScorePlot();
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
    $wgOut->addModules( 'ext.userjourney.charts.nvd3' );

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
									rev_user_text IN ( 'Lwelsh' )
								GROUP BY user_day
								) user
							LEFT JOIN
							(
								SELECT
									DATE(rev_timestamp) AS user2_day,
									COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 AS user2_score
								FROM `revision`
								WHERE
									rev_user_text IN ( 'Ejmontal' )
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
									rev_user_text IN ( 'Lwelsh' )
								GROUP BY user_day
							) user
							RIGHT JOIN
							(
								SELECT
									DATE(rev_timestamp) AS user2_day,
									COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 AS user2_score
								FROM `revision`
								WHERE
									rev_user_text IN ( 'Ejmontal' )
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
  function compareScorePlot( ){
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
    $wgOut->addModules( 'ext.userjourney.charts.nvd3' );

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

