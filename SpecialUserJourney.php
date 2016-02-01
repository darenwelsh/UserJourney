<?php

// class ContributionScores extends IncludableSpecialPage {
class SpecialUserJourney extends IncludableSpecialPage {
   public function __construct() {
       // parent::__construct( 'ContributionScores' );
   	parent::__construct( 'UserJourney' );
   }


   /// Generates a "User Journey" plot for a given user and date range
   /**
    * Function generates Contribution Scores tables in HTML format (not wikiText)
    *
    * @param $days int Days in the past to run report for
    * @param $user int User to report information about
    * @return Html Table representing the requested Contribution Scores.
    */
    function genUserJourneyPlot2( ){
      global $wgOut;

      $wgOut->setPageTitle( 'UserJourney: Plot' );
      $wgOut->addModules( 'ext.wiretap.charts.nvd3' );

      $html = '<div id="wiretap-chart"><svg height="400px"></svg></div>';

      $dbr = wfGetDB( DB_SLAVE );

      $res = $dbr->select(
        array('w' => 'wiretap'),
        array(
          "w.hit_year AS year",
          "w.hit_month AS month",
          "w.hit_day AS day",
          "count(*) AS num_hits",
        ),
        null, //'w.hit_timestamp > 20140801000000', //null, // CONDITIONS? 'wiretap.hit_timestamp>20131001000000',
        __METHOD__,
        array(
          "DISTINCT",
          "GROUP BY" => "w.hit_year, w.hit_month, w.hit_day",
          "ORDER BY" => "w.hit_year ASC, w.hit_month ASC, w.hit_day ASC",
          "LIMIT" => "100000",
        ),
        null // join conditions
      );

      $previous = null;

      while( $row = $dbr->fetchRow( $res ) ) {

        list($year, $month, $day, $hits) = array($row['year'], $row['month'], $row['day'], $row['num_hits']);

        $currentDateString = "$year-$month-$day";
        $current = new DateTime( $currentDateString );

        while ( $previous && $previous->modify( '+1 day' )->format( 'Y-m-d') !== $currentDateString ) {
          $data[] = array(
            'x' => $previous->getTimestamp() * 1000, // x value timestamp in milliseconds
            'y' => 0, // y value = zero hits for this day
          );
        }

        $data[] = array(
          'x' => strtotime( $currentDateString ) * 1000, // x value time in milliseconds
          'y' => intval( $hits ),
        );

        $previous = new DateTime( $currentDateString );
      }

      $data = array(
        array(
          'key' => 'Daily Hits',
          'values' => $data,
        ),
      );

      $html .= "<script type='text/template-json' id='wiretap-data'>" . json_encode( $data ) . "</script>";

      // REMOVE - output raw data
      $html .= "<pre>" . print_r( $data, true ) . "</pre>";

      $wgOut->addHTML( $html );
    }


    function genUserJourneyPlot( ){
      global $wgOut;

      $wgOut->setPageTitle( 'UserJourney: Plot' );
      $wgOut->addModules( 'ext.wiretap.charts.nvd3' );

      $html = '<div id="wiretap-chart"><svg height="400px"></svg></div>';

      $dbr = wfGetDB( DB_SLAVE );

      $sql = "SELECT
                DATE(rev_timestamp) AS day,
                COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 AS score
              FROM `revision`
              WHERE
                rev_user_text IN ( 'Lwelsh' )
              GROUP BY day
              ORDER BY day DESC";

      $res = $dbr->query( $sql );

      $previous = null;

      while( $row = $dbr->fetchRow( $res ) ) {

        list($day, $score) = array($row['day'], $row['score']);

        $day = strtotime( $day ) * 1000;

        $data[] = array(
          'x' => $day,
          'y' => $score,
        );
      }

      $data = array(
        array(
          'key' => 'Daily Score',
          'values' => $data,
        ),
      );

      $html .= "<script type='text/template-json' id='wiretap-data'>" . json_encode( $data ) . "</script>";

      // REMOVE - output raw data
      $html .= "<pre>" . print_r( $data, true ) . "</pre>";

      $wgOut->addHTML( $html );
    }





   function genUserJourneyPlotOld( $user, $days  ) {
    global $wgContribScoreIgnoreBots, $wgContribScoreIgnoreBlockedUsers, $wgContribScoresUseRealName;

    $dbr = wfGetDB( DB_SLAVE );

       $userTable = $dbr->tableName( 'user' );
       $userGroupTable = $dbr->tableName( 'user_groups' );
       $revTable = $dbr->tableName( 'revision' );
       $ipBlocksTable = $dbr->tableName( 'ipblocks' );
       $limit = 500; //TO-DO need ot fix up
       $opts = array();
	//$days = 30;

       $sqlWhere = "";
       $nextPrefix = "WHERE";

       if ( $days > 0 ) {
           $date = time() - ( 60 * 60 * 24 * $days ) - (60*60*24*90);
           $dateString = $dbr->timestamp( $date );
           $dateString2 = $dbr->timestamp( $date + (60 * 60 * 24) );
           $sqlWhere .= " {$nextPrefix} rev_timestamp > '$dateString'";
           $sqlWhere .= " AND rev_timestamp < '$dateString2'";
          // $sqlWhere .= " AND rev_user IN array( 'rev_user' => $user->getID() )";
	//$sqlWhere .= " AND user_name IN array( 'Lwelsh' , 'Swray' )";
           $nextPrefix = "AND";
       }

       if ( $wgContribScoreIgnoreBlockedUsers ) {
           $sqlWhere .= " {$nextPrefix} rev_user NOT IN " .
               "(SELECT ipb_user FROM {$ipBlocksTable} WHERE ipb_user <> 0)";
           $nextPrefix = "AND";
       }

       if ( $wgContribScoreIgnoreBots ) {
           $sqlWhere .= " {$nextPrefix} rev_user NOT IN " .
               "(SELECT ug_user FROM {$userGroupTable} WHERE ug_group='bot')";
       }

       $sqlMostPages = "SELECT rev_user,
                        COUNT(DISTINCT rev_page) AS page_count,
                        COUNT(rev_id) AS rev_count
                        FROM {$revTable}
                        {$sqlWhere}
                        GROUP BY rev_user
                        ORDER BY page_count DESC
                        LIMIT {$limit}";

       $sqlMostRevs = "SELECT rev_user,
                        COUNT(DISTINCT rev_page) AS page_count,
                        COUNT(rev_id) AS rev_count
                        FROM {$revTable}
                        {$sqlWhere}
                        GROUP BY rev_user
                        ORDER BY rev_count DESC
                        LIMIT {$limit}";

       $sql = "SELECT user_id, " .
           "user_name, " .
           "user_real_name, " .
           "page_count, " .
           "rev_count, " .
           "page_count+SQRT(rev_count-page_count)*2 AS wiki_rank " .
           "FROM $userTable u JOIN (($sqlMostPages) UNION ($sqlMostRevs)) s ON (user_id=rev_user) " .
	//"WHERE user_name IN array( 'Lwelsh' ) " .
           "ORDER BY wiki_rank DESC " .
           "LIMIT $limit";

       $res = $dbr->query( $sql );

$output = 0;
foreach( $res as $row ) {
	if( $row->user_name == "Swray" ) {
		//print_r(floor($row->wiki_rank));
		$output = $row->wiki_rank;
	}
};

//while($row = mysql_fetch_array($res)) {
//echo $row['fieldname'];
//}


//$data = array();

//    for ($x = 0; $x < mysql_num_rows($res); $x++) {
//        $data[] = mysql_fetch_assoc($res);
//    }

// Need data to look something like this:
// [
//   {"key":
//   "Daily Hits","values":
//     [
//       {"x":1384236000000,"y":298},
//       {"x":1384322400000,"y":1067},
//       {"x":1384408800000,"y":1605},
//       {"x":1384495200000,"y":996},
//     ]
//   }
// ]

    $data = json_encode($res);

       $sortable = in_array( 'nosort', $opts ) ? '' : ' sortable';

       //$output = "";
//       $output .= print_r($data);
       // $output = "<table class=\"wikitable contributionscores plainlinks{$sortable}\" >\n" .
       //     "<tr class='header'>\n" .
       //     Html::element( 'th', array(), $this->msg( 'contributionscores-rank' )->text() ) .
       //     Html::element( 'th', array(), $this->msg( 'contributionscores-score' )->text() ) .
       //     Html::element( 'th', array(), $this->msg( 'contributionscores-pages' )->text() ) .
       //     Html::element( 'th', array(), $this->msg( 'contributionscores-changes' )->text() ) .
       //     Html::element( 'th', array(), $this->msg( 'contributionscores-username' )->text() );

       $altrow = '';
       $user_rank = 1;

       $lang = $this->getLanguage();


       return $output;
   }

   /// Generates a "User Journey" table for a given user and date range
   /**
    * Function generates Contribution Scores tables in HTML format (not wikiText)
    *
    * @param $days int Days in the past to run report for
    * @param $user int User to report information about
    * @return Html Table representing the requested Contribution Scores.
    */
   function genUserJourneyTable( $user, $days ) {
		global $wgContribScoreIgnoreBots, $wgContribScoreIgnoreBlockedUsers, $wgContribScoresUseRealName;

		$dbr = wfGetDB( DB_SLAVE );

       $userTable = $dbr->tableName( 'user' );
       $userGroupTable = $dbr->tableName( 'user_groups' );
       $revTable = $dbr->tableName( 'revision' );
       $ipBlocksTable = $dbr->tableName( 'ipblocks' );
       $limit = 500; //TO-DO need ot fix up
       $opts = array();

       $sqlWhere = "";
       $nextPrefix = "WHERE";

       if ( $days > 0 ) {
           $date = time() - ( 60 * 60 * 24 * $days ) - (60*60*24*90);
           $dateString = $dbr->timestamp( $date );
           $dateString2 = $dbr->timestamp( $date + (60 * 60 * 24) );
           $sqlWhere .= " {$nextPrefix} rev_timestamp > '$dateString'";
           $sqlWhere .= " AND rev_timestamp < '$dateString2'";
           // $sqlWhere .= " AND rev_user IN array( 'rev_user' => $user->getID() )";
           $nextPrefix = "AND";
       }

       if ( $wgContribScoreIgnoreBlockedUsers ) {
           $sqlWhere .= " {$nextPrefix} rev_user NOT IN " .
               "(SELECT ipb_user FROM {$ipBlocksTable} WHERE ipb_user <> 0)";
           $nextPrefix = "AND";
       }

       if ( $wgContribScoreIgnoreBots ) {
           $sqlWhere .= " {$nextPrefix} rev_user NOT IN " .
               "(SELECT ug_user FROM {$userGroupTable} WHERE ug_group='bot')";
       }

       $sqlMostPages = "SELECT rev_user,
                        COUNT(DISTINCT rev_page) AS page_count,
                        COUNT(rev_id) AS rev_count
                        FROM {$revTable}
                        {$sqlWhere}
                        GROUP BY rev_user
                        ORDER BY page_count DESC
                        LIMIT {$limit}";

       $sqlMostRevs = "SELECT rev_user,
                        COUNT(DISTINCT rev_page) AS page_count,
                        COUNT(rev_id) AS rev_count
                        FROM {$revTable}
                        {$sqlWhere}
                        GROUP BY rev_user
                        ORDER BY rev_count DESC
                        LIMIT {$limit}";

       $sql = "SELECT user_id, " .
           "user_name, " .
           "user_real_name, " .
           "page_count, " .
           "rev_count, " .
           "page_count+SQRT(rev_count-page_count)*2 AS wiki_rank " .
           "FROM $userTable u JOIN (($sqlMostPages) UNION ($sqlMostRevs)) s ON (user_id=rev_user) " .
           "ORDER BY wiki_rank DESC " .
           "LIMIT $limit";

       $res = $dbr->query( $sql );

       $sortable = in_array( 'nosort', $opts ) ? '' : ' sortable';

       $output = "";
       // $output = "<table class=\"wikitable contributionscores plainlinks{$sortable}\" >\n" .
       //     "<tr class='header'>\n" .
       //     Html::element( 'th', array(), $this->msg( 'contributionscores-rank' )->text() ) .
       //     Html::element( 'th', array(), $this->msg( 'contributionscores-score' )->text() ) .
       //     Html::element( 'th', array(), $this->msg( 'contributionscores-pages' )->text() ) .
       //     Html::element( 'th', array(), $this->msg( 'contributionscores-changes' )->text() ) .
       //     Html::element( 'th', array(), $this->msg( 'contributionscores-username' )->text() );

       $altrow = '';
       $user_rank = 1;

       $lang = $this->getLanguage();
       foreach ( $res as $row ) {
           // Use real name if option used and real name present.
           if ( $wgContribScoresUseRealName && $row->user_real_name !== '' ) {
               $userLink = Linker::userLink(
                   $row->user_id,
                   $row->user_name,
                   $row->user_real_name
               );
           } else {
               $userLink = Linker::userLink(
                   $row->user_id,
                   $row->user_name
               );
           }

           $output .= Html::closeElement( 'tr' );
           $output .= "<tr class='{$altrow}'>\n" .
               "<td class='content' style='padding-right:10px;text-align:right;'>" .
               date('Y-m-d', strtotime($dateString)) .
               // "\n</td><td class='content' style='padding-right:10px;text-align:right;'>" .
               // $lang->formatNum( round( $user_rank, 0 ) ) .
               "\n</td><td class='content' style='padding-right:10px;text-align:right;'>" .
               $lang->formatNum( round( $row->wiki_rank, 0 ) ) .
               // "\n</td><td class='content' style='padding-right:10px;text-align:right;'>" .
               // $lang->formatNum( $row->page_count ) .
               // "\n</td><td class='content' style='padding-right:10px;text-align:right;'>" .
               // $lang->formatNum( $row->rev_count ) .
               "\n</td><td class='content'>" .
               $userLink;

           # Option to not display user tools
           if ( !in_array( 'notools', $opts ) ) {
               $output .= Linker::userToolLinks( $row->user_id, $row->user_name );
           }

           $output .= Html::closeElement( 'td' ) . "\n";

           if ( $altrow == '' && empty( $sortable ) ) {
               $altrow = 'odd ';
           } else {
               $altrow = '';
           }

           $user_rank++;
       }
       // $output .= Html::closeElement( 'tr' );
       // $output .= Html::closeElement( 'table' );

       $dbr->freeResult( $res );

       if ( !empty( $title ) ) {
           $output = Html::rawElement( 'table',
               array(
                   'style' => 'border-spacing: 0; padding: 0',
                   'class' => 'contributionscores-wrapper',
                   'lang' => htmlspecialchars( $lang->getCode() ),
                   'dir' => $lang->getDir()
               ),
               "\n" .
               "<tr>\n" .
               "<td style='padding: 0px;'>{$title}</td>\n" .
               "</tr>\n" .
               "<tr>\n" .
               "<td style='padding: 0px;'>{$output}</td>\n" .
               "</tr>\n"
           );
       }

       return $output;
   }

   /// Generates a "Contribution Scores" table for a given LIMIT and date range
   /**
    * Function generates Contribution Scores tables in HTML format (not wikiText)
    *
    * @param $days int Days in the past to run report for
    * @param $limit int Maximum number of users to return (default 50)
    * @param $title Title (default null)
    * @param $options array of options (default none; nosort/notools)
    * @return Html Table representing the requested Contribution Scores.
    */
   function genContributionScoreTable( $days, $limit, $title = null, $options = 'none' ) {
       global $wgContribScoreIgnoreBots, $wgContribScoreIgnoreBlockedUsers, $wgContribScoresUseRealName;

       $opts = explode( ',', strtolower( $options ) );

       $dbr = wfGetDB( DB_SLAVE );

       $userTable = $dbr->tableName( 'user' );
       $userGroupTable = $dbr->tableName( 'user_groups' );
       $revTable = $dbr->tableName( 'revision' );
       $ipBlocksTable = $dbr->tableName( 'ipblocks' );

       $sqlWhere = "";
       $nextPrefix = "WHERE";

       if ( $days > 0 ) {
           $date = time() - ( 60 * 60 * 24 * $days );
           $dateString = $dbr->timestamp( $date );
           $sqlWhere .= " {$nextPrefix} rev_timestamp > '$dateString'";
           $nextPrefix = "AND";
       }

       if ( $wgContribScoreIgnoreBlockedUsers ) {
           $sqlWhere .= " {$nextPrefix} rev_user NOT IN " .
               "(SELECT ipb_user FROM {$ipBlocksTable} WHERE ipb_user <> 0)";
           $nextPrefix = "AND";
       }

       if ( $wgContribScoreIgnoreBots ) {
           $sqlWhere .= " {$nextPrefix} rev_user NOT IN " .
               "(SELECT ug_user FROM {$userGroupTable} WHERE ug_group='bot')";
       }

       $sqlMostPages = "SELECT rev_user,
                        COUNT(DISTINCT rev_page) AS page_count,
                        COUNT(rev_id) AS rev_count
                        FROM {$revTable}
                        {$sqlWhere}
                        GROUP BY rev_user
                        ORDER BY page_count DESC
                        LIMIT {$limit}";

       $sqlMostRevs = "SELECT rev_user,
                        COUNT(DISTINCT rev_page) AS page_count,
                        COUNT(rev_id) AS rev_count
                        FROM {$revTable}
                        {$sqlWhere}
                        GROUP BY rev_user
                        ORDER BY rev_count DESC
                        LIMIT {$limit}";

       $sql = "SELECT user_id, " .
           "user_name, " .
           "user_real_name, " .
           "page_count, " .
           "rev_count, " .
           "page_count+SQRT(rev_count-page_count)*2 AS wiki_rank " .
           "FROM $userTable u JOIN (($sqlMostPages) UNION ($sqlMostRevs)) s ON (user_id=rev_user) " .
           "ORDER BY wiki_rank DESC " .
           "LIMIT $limit";

       $res = $dbr->query( $sql );

       $sortable = in_array( 'nosort', $opts ) ? '' : ' sortable';

       $output = "<table class=\"wikitable contributionscores plainlinks{$sortable}\" >\n" .
           "<tr class='header'>\n" .
           Html::element( 'th', array(), $this->msg( 'contributionscores-rank' )->text() ) .
           Html::element( 'th', array(), $this->msg( 'contributionscores-score' )->text() ) .
           Html::element( 'th', array(), $this->msg( 'contributionscores-pages' )->text() ) .
           Html::element( 'th', array(), $this->msg( 'contributionscores-changes' )->text() ) .
           Html::element( 'th', array(), $this->msg( 'contributionscores-username' )->text() );

       $altrow = '';
       $user_rank = 1;

       $lang = $this->getLanguage();
       foreach ( $res as $row ) {
           // Use real name if option used and real name present.
           if ( $wgContribScoresUseRealName && $row->user_real_name !== '' ) {
               $userLink = Linker::userLink(
                   $row->user_id,
                   $row->user_name,
                   $row->user_real_name
               );
           } else {
               $userLink = Linker::userLink(
                   $row->user_id,
                   $row->user_name
               );
           }

           $output .= Html::closeElement( 'tr' );
           $output .= "<tr class='{$altrow}'>\n" .
               "<td class='content' style='padding-right:10px;text-align:right;'>" .
               $lang->formatNum( round( $user_rank, 0 ) ) .
               "\n</td><td class='content' style='padding-right:10px;text-align:right;'>" .
               $lang->formatNum( round( $row->wiki_rank, 0 ) ) .
               "\n</td><td class='content' style='padding-right:10px;text-align:right;'>" .
               $lang->formatNum( $row->page_count ) .
               "\n</td><td class='content' style='padding-right:10px;text-align:right;'>" .
               $lang->formatNum( $row->rev_count ) .
               "\n</td><td class='content'>" .
               $userLink;

           # Option to not display user tools
           if ( !in_array( 'notools', $opts ) ) {
               $output .= Linker::userToolLinks( $row->user_id, $row->user_name );
           }

           $output .= Html::closeElement( 'td' ) . "\n";

           if ( $altrow == '' && empty( $sortable ) ) {
               $altrow = 'odd ';
           } else {
               $altrow = '';
           }

           $user_rank++;
       }
       $output .= Html::closeElement( 'tr' );
       $output .= Html::closeElement( 'table' );

       $dbr->freeResult( $res );

       if ( !empty( $title ) ) {
           $output = Html::rawElement( 'table',
               array(
                   'style' => 'border-spacing: 0; padding: 0',
                   'class' => 'contributionscores-wrapper',
                   'lang' => htmlspecialchars( $lang->getCode() ),
                   'dir' => $lang->getDir()
               ),
               "\n" .
               "<tr>\n" .
               "<td style='padding: 0px;'>{$title}</td>\n" .
               "</tr>\n" .
               "<tr>\n" .
               "<td style='padding: 0px;'>{$output}</td>\n" .
               "</tr>\n"
           );
       }

       return $output;
   }

   function execute( $par ) {
       $this->setHeaders();

       if ( $this->including() ) {
           $this->showInclude( $par );
       } else {
           $this->showPage();
       }

       return true;
   }

   /**
    * Called when being included on a normal wiki page.
    * Cache is disabled so it can depend on the user language.
    * @param $par
    */
   function showInclude( $par ) {
       $days = null;
       $limit = null;
       $options = 'none';

       if ( !empty( $par ) ) {
           $params = explode( '/', $par );

           $limit = intval( $params[0] );

           if ( isset( $params[1] ) ) {
               $days = intval( $params[1] );
           }

           if ( isset( $params[2] ) ) {
               $options = $params[2];
           }
       }

       if ( empty( $limit ) || $limit < 1 || $limit > CONTRIBUTIONSCORES_MAXINCLUDELIMIT ) {
           $limit = 10;
       }
       if ( is_null( $days ) || $days < 0 ) {
           $days = 7;
       }

       if ( $days > 0 ) {
           $reportTitle = $this->msg( 'contributionscores-days' )->numParams( $days )->text();
       } else {
           $reportTitle = $this->msg( 'contributionscores-allrevisions' )->text();
       }
       $reportTitle .= ' ' . $this->msg( 'contributionscores-top' )->numParams( $limit )->text();
       $title = Xml::element( 'h4',
               array( 'class' => 'contributionscores-title' ),
               $reportTitle
           ) . "\n";
       $this->getOutput()->addHTML( $this->genContributionScoreTable(
           $days,
           $limit,
           $title,
           $options
       ) );
   }

   /**
    * Show the special page
    */
   function showPage() {
       global $wgContribScoreReports;

       if ( !is_array( $wgContribScoreReports ) ) {
           $wgContribScoreReports = array(
               array( 7, 50 ),
               array( 30, 50 ),
               array( 0, 50 )
           );
       }

       $out = $this->getOutput();
       $out->addWikiMsg( 'contributionscores-info' );

       // TEST PLOT
       /*
       $reportTitle = 'Plot';
       $title = Xml::element( 'h2',
               array( 'class' => 'contributionscores-title' ),
               $reportTitle
           ) . "\n";
       $out->addHTML( $title );


	$plotData = array();
       	for($i=1; $i<30; $i++){
          //$out->addHTML( $this->genUserJourneyPlot( 'lwelsh', $i ) );
          $plotData[$i]['date'] = $i;
          $plotData[$i]['score'] = $this->genUserJourneyPlot( 'lwelsh', $i );
       	}

       //print_r(json_encode($plotData));
	$plotHtml = '<canvas id="userjourneyChart" width="400" height="400"></canvas>';
	$plotHtml .= "<script type='text/template-json' id='userjourney-data'>" . json_encode( $plotData ) . "</script>";
       $out->addHTML( $plotHtml );

        */

       $this->genUserJourneyPlot( );
       // END TEST PLOT

		//Add new section calling new function to generate personal scores over time
       // $reportTitle .= ' ' . $this->msg( 'contributionscores-top' )->numParams( $revs )->text();
       $reportTitle = 'Individual';
       $title = Xml::element( 'h2',
               array( 'class' => 'contributionscores-title' ),
               $reportTitle
           ) . "\n";
       $out->addHTML( $title );

       $out->addHTML( "<table class=\"wikitable contributionscores plainlinks sortable\" >\n" .
           "<tr class='header'>\n" .
           Html::element( 'th', array(), 'date' ) . //TO-DO add date message
           // Html::element( 'th', array(), $this->msg( 'contributionscores-rank' )->text() ) .
           Html::element( 'th', array(), $this->msg( 'contributionscores-score' )->text() ) .
           // Html::element( 'th', array(), $this->msg( 'contributionscores-pages' )->text() ) .
           // Html::element( 'th', array(), $this->msg( 'contributionscores-changes' )->text() ) .
           Html::element( 'th', array(), $this->msg( 'contributionscores-username' )->text() ) );

		for($i=1; $i<30; $i++){
			$out->addHTML( $this->genUserJourneyTable( 'lwelsh', $i ) );
		}
       $out->addHTML( Html::closeElement( 'tr' ) . Html::closeElement( 'table' ) );

       foreach ( $wgContribScoreReports as $scoreReport ) {
           list( $days, $revs ) = $scoreReport;
           if ( $days > 0 ) {
               $reportTitle = $this->msg( 'contributionscores-days' )->numParams( $days )->text();
           } else {
               $reportTitle = $this->msg( 'contributionscores-allrevisions' )->text();
           }
           $reportTitle .= ' ' . $this->msg( 'contributionscores-top' )->numParams( $revs )->text();
           $title = Xml::element( 'h2',
                   array( 'class' => 'contributionscores-title' ),
                   $reportTitle
               ) . "\n";
           $out->addHTML( $title );
           $out->addHTML( $this->genContributionScoreTable( $days, $revs ) );
       }
   }

   protected function getGroupName() {
       return 'wiki';
   }
}

// COMMENT OUT OLD STUFF FOR NOW
/*

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

		// $userTarget = isset( $parser ) ? $parser : $wgRequest->getVal( 'username' );
		$this->mMode = $wgRequest->getVal( 'show' );
		//$fileactions = array('actions...?');

		$wgOut->addHTML( $this->getPageHeader() );

		if ($this->mMode == 'hits-list') {
			$this->hitsList();
		}
		else if ($this->mMode == 'total-hits-data') {
			$this->totals();
		}
		else if ( $this->mMode == 'total-hits-chart' ) {
			$this->totalsChart2();
		}

		else if ( $this->mMode == 'unique-user-data' ) {
			$this->uniqueTotals( false );
		}
		else if ( $this->mMode == 'unique-user-chart' ) {
			$this->uniqueTotalsChart( false );
		}

		else if ( $this->mMode == 'unique-user-page-data' ) {
			$this->uniqueTotals( true );
		}
		else if ( $this->mMode == 'unique-user-page-chart' ) {
			$this->uniqueTotalsChart( true );
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

		$navLine .= "<li>" . $this->createHeaderLink( 'userjourney-hits', 'hits-list' ) . $unfilterLink . "</li>";

		$navLine .= "<li>" . wfMessage( 'userjourney-dailytotals' )->text()
			. ": (" . $this->createHeaderLink( 'userjourney-rawdata', 'total-hits-data' )
			. ") (" . $this->createHeaderLink( 'userjourney-chart', 'total-hits-chart' )
			. ")</li>";

		$navLine .= "<li>" . wfMessage( 'userjourney-dailyunique-user-hits' )->text()
			. ": (" . $this->createHeaderLink( 'userjourney-rawdata', 'unique-user-data' )
			. ") (" . $this->createHeaderLink( 'userjourney-chart', 'unique-user-chart' )
			. ")</li>";

		$navLine .= "<li>" . wfMessage( 'userjourney-dailyunique-user-page-hits' )->text()
			. ": (" . $this->createHeaderLink( 'userjourney-rawdata', 'unique-user-page-data' )
			. ") (" . $this->createHeaderLink( 'userjourney-chart', 'unique-user-page-chart' )
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

		// $form = $pager->getForm();
		$body = $pager->getBody();
		$html = '';
		// $html = $form;

		$html .= '<p>Test</p>';
		$wgOut->addHTML( $html );
	}


	public function hitsList () {
		global $wgOut, $wgRequest;

		$wgOut->setPageTitle( 'UserJourney' );

		$pager = new UserJourneyPager();
		$pager->filterUser = $wgRequest->getVal( 'filterUser' );
		$pager->filterPage = $wgRequest->getVal( 'filterPage' );

		// $form = $pager->getForm();
		$body = $pager->getBody();
		$html = '';
		// $html = $form;
		if ( $body ) {
			$html .= $pager->getNavigationBar();
			$html .= '<table class="wikitable sortable" width="100%" cellspacing="0" cellpadding="0">';
			$html .= '<tr><th>Username</th><th>Page</th><th>Time</th><th>Referal Page</th><th>Event</th><th>Points</th><th>Badge</th><th>Action</th></tr>';
			$html .= $body;
			$html .= '</table>';
			$html .= $pager->getNavigationBar();
		}
		else {
			$html .= '<p>' . wfMsgHTML('listusers-noresult') . '</p>';
		}
		$wgOut->addHTML( $html );
	}

	public function totals () {
		global $wgOut;

		$wgOut->setPageTitle( 'UserJourney: Daily Totals' );

		$html = '<table class="wikitable"><tr><th>Date</th><th>Hits</th></tr>';
		// $html = $form;
		// if ( $body ) {

		// }
		// else {
			// $html .= '<p>' . wfMsgHTML('listusers-noresult') . '</p>';
		// }
		// SELECT userjourney.hit_year, userjourney.hit_month, userjourney.hit_day, count(*) AS num_hits
		// FROM userjourney
		// WHERE userjourney.hit_timestamp>20131001000000
		// GROUP BY userjourney.hit_year, userjourney.hit_month, userjourney.hit_day
		// ORDER BY userjourney.hit_year DESC, userjourney.hit_month DESC, userjourney.hit_day DESC
		// LIMIT 100000;
		$dbr = wfGetDB( DB_SLAVE );

		$res = $dbr->select(
			array('w' => 'userjourney'),
			array(
				"w.hit_year AS year",
				"w.hit_month AS month",
				"w.hit_day AS day",
				"count(*) AS num_hits",
			),
			null, // CONDITIONS? 'userjourney.hit_timestamp>20131001000000',
			__METHOD__,
			array(
				"DISTINCT",
				"GROUP BY" => "w.hit_year, w.hit_month, w.hit_day",
				"ORDER BY" => "w.hit_year DESC, w.hit_month DESC, w.hit_day DESC",
				"LIMIT" => "100000",
			),
			null // join conditions
		);
		while( $row = $dbr->fetchRow( $res ) ) {

			list($year, $month, $day, $hits) = array($row['year'], $row['month'], $row['day'], $row['num_hits']);
			$html .= "<tr><td>$year-$month-$day</td><td>$hits</td></tr>";

		}
		$html .= "</table>";

		$wgOut->addHTML( $html );

	}

	public function totalsChart () {
		global $wgOut;

		$wgOut->setPageTitle( 'UserJourney: Daily Totals Chart' );
		$wgOut->addModules( 'ext.userjourney.charts' );

		$html = '<canvas id="userjourneyChart" width="400" height="400"></canvas>';

		$dbr = wfGetDB( DB_SLAVE );

		$res = $dbr->select(
			array('w' => 'userjourney'),
			array(
				"w.hit_year AS year",
				"w.hit_month AS month",
				"w.hit_day AS day",
				"count(*) AS num_hits",
			),
			null, //'w.hit_timestamp > 20140801000000', //null, // CONDITIONS? 'userjourney.hit_timestamp>20131001000000',
			__METHOD__,
			array(
				"DISTINCT",
				"GROUP BY" => "w.hit_year, w.hit_month, w.hit_day",
				"ORDER BY" => "w.hit_year ASC, w.hit_month ASC, w.hit_day ASC",
				"LIMIT" => "100000",
			),
			null // join conditions
		);
		$previous = null;

		while( $row = $dbr->fetchRow( $res ) ) {

			list($year, $month, $day, $hits) = array($row['year'], $row['month'], $row['day'], $row['num_hits']);

			$currentDateString = "$year-$month-$day";
			$current = new DateTime( $currentDateString );

			while ( $previous && $previous->modify( '+1 day' )->format( 'Y-m-d') !== $currentDateString ) {
				$data[ $previous->format( 'Y-m-d' ) ] = 0;
			}

			$data[ $currentDateString ] = $hits;

			$previous = new DateTime( $currentDateString );
		}

		//$html .= "<pre>" . print_r( $data, true ) . "</pre>";
		$html .= "<script type='text/template-json' id='userjourney-data'>" . json_encode( $data ) . "</script>";

		$wgOut->addHTML( $html );

	}

	protected function getUniqueRows ( $uniquePageHits = true, $order = "DESC" ) {

		$dbr = wfGetDB( DB_SLAVE );

		$fields = array(
			"CONCAT(w.hit_year, '-', w.hit_month, '-', w.hit_day) AS date",
		);

		if ( $uniquePageHits ) {
			$fields[] = "COUNT(DISTINCT(CONCAT(w.user_name,'UNIQUESEPARATOR',w.page_id))) as hits";
		}
		else {
			$fields[] = "COUNT(DISTINCT(w.user_name)) as hits";
		}

		$res = $dbr->select(
			array('w' => 'userjourney'),
			$fields,
			null, // CONDITIONS? 'userjourney.hit_timestamp>20131001000000',
			__METHOD__,
			array(
				// "DISTINCT",
				"GROUP BY" => "w.hit_year, w.hit_month, w.hit_day",
				"ORDER BY" => "w.hit_timestamp $order",
				"LIMIT" => "100000",
			),
			null // join conditions
		);

		$output = array();
		while( $row = $dbr->fetchRow( $res ) ) {

			// list($year, $month, $day, $hits) = array($row['year'], $row['month'], $row['day'], $row['hits']);

			$output[] = array( 'date' => $row['date'], 'hits' => $row['hits'] );

		}

		return $output;
	}

	public function uniqueTotals ( $showUniquePageHits = false ) {
		global $wgOut;

		if ( $showUniquePageHits ) {
			$pageTitleText = "Daily Unique User-Page-Hits";
		}
		else {
			$pageTitleText = "Daily Unique User-Hits";
		}

		$wgOut->setPageTitle( 'UserJourney: ' . $pageTitleText );

		$html = '<table class="wikitable"><tr><th>Date</th><th>Hits</th></tr>';

		$rows = $this->getUniqueRows( $showUniquePageHits, "DESC" );

		foreach($rows as $row) {
			$html .= "<tr><td>{$row['date']}</td><td>{$row['hits']}</td></tr>";
		}

		$html .= "</table>";

		$wgOut->addHTML( $html );

	}

	public function uniqueTotalsChart ( $showUniquePageHits = false ) {

		global $wgOut;

		if ( $showUniquePageHits ) {
			$pageTitleText = "Daily Unique User-Page-Hits";
		}
		else {
			$pageTitleText = "Daily Unique User-Hits";
		}

		$wgOut->setPageTitle( "UserJourney: $pageTitleText Chart" );
		$wgOut->addModules( 'ext.userjourney.charts.nvd3' );

		$html = '<div id="userjourney-chart"><svg height="400px"></svg></div>';

		$rows = $this->getUniqueRows( $showUniquePageHits, "ASC" );

		$previous = null;

		foreach ( $rows as $row ) {

			list($currentDateString, $hits) = array($row['date'], $row['hits']);

			$current = new DateTime( $currentDateString );

			while ( $previous && $previous->modify( '+1 day' )->format( 'Y-m-d') !== $currentDateString ) {
				$data[] = array(
					'x' => $previous->getTimestamp() * 1000, // x value timestamp in milliseconds
					'y' => 0, // y value = zero hits for this day
				);
			}

			$data[] = array(
				'x' => strtotime( $currentDateString ) * 1000, // x value time in milliseconds
				'y' => intval( $hits ),
			);

			$previous = new DateTime( $currentDateString );
		}

		$data = array(
			array(
				'key' => $pageTitleText,
				'values' => $data,
			),
		);

		//$html .= "<pre>" . print_r( $data, true ) . "</pre>";
		$html .= "<script type='text/template-json' id='userjourney-data'>" . json_encode( $data ) . "</script>";

		$wgOut->addHTML( $html );

	}

	public function totalsChart2 () {
		global $wgOut;

		$wgOut->setPageTitle( 'UserJourney: Daily Totals Chart' );
		$wgOut->addModules( 'ext.userjourney.charts.nvd3' );

		$html = '<div id="userjourney-chart"><svg height="400px"></svg></div>';

		$dbr = wfGetDB( DB_SLAVE );

		$res = $dbr->select(
			array('w' => 'userjourney'),
			array(
				"w.hit_year AS year",
				"w.hit_month AS month",
				"w.hit_day AS day",
				"count(*) AS num_hits",
			),
			null, //'w.hit_timestamp > 20140801000000', //null, // CONDITIONS? 'userjourney.hit_timestamp>20131001000000',
			__METHOD__,
			array(
				"DISTINCT",
				"GROUP BY" => "w.hit_year, w.hit_month, w.hit_day",
				"ORDER BY" => "w.hit_year ASC, w.hit_month ASC, w.hit_day ASC",
				"LIMIT" => "100000",
			),
			null // join conditions
		);

		$previous = null;

		while( $row = $dbr->fetchRow( $res ) ) {

			list($year, $month, $day, $hits) = array($row['year'], $row['month'], $row['day'], $row['num_hits']);

			$currentDateString = "$year-$month-$day";
			$current = new DateTime( $currentDateString );

			while ( $previous && $previous->modify( '+1 day' )->format( 'Y-m-d') !== $currentDateString ) {
				$data[] = array(
					'x' => $previous->getTimestamp() * 1000, // x value timestamp in milliseconds
					'y' => 0, // y value = zero hits for this day
				);
			}

			$data[] = array(
				'x' => strtotime( $currentDateString ) * 1000, // x value time in milliseconds
				'y' => intval( $hits ),
			);

			$previous = new DateTime( $currentDateString );
		}

		$data = array(
			array(
				'key' => 'Daily Hits',
				'values' => $data,
			),
		);

		//$html .= "<pre>" . print_r( $data, true ) . "</pre>";
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

*/
