<?php
/**
 * This extension tracks each user's journey as a wiki contributor
 *
 * Documentation: https://github.com/darenwelsh/UserJourney
 * Support:       https://github.com/darenwelsh/UserJourney
 * Source code:   https://github.com/darenwelsh/UserJourney
 *
 * @file UserJourney.php
 * @addtogroup Extensions
 * @author Daren Welsh
 * @copyright © 2014 by Daren Welsh
 * @licence GNU GPL v3+
 */

# Not a valid entry point, skip unless MEDIAWIKI is defined
if (!defined('MEDIAWIKI')) {
	die( "UserJourney extension" );
}

// if wfLoadExtension exists (MW 1.25+) use that method of registering extensions
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'UserJourney' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['UserJourney'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['UserJourneyMagic'] = __DIR__ . '/Magic.php';
	wfWarn(
		'Deprecated PHP entry point used for UserJourney extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;

// For MW 1.24 and lower, use normal extension registration
// This should be removed when this extension drops support for <1.25
} else {
	
	$wgExtensionCredits['specialpage'][] = array(
		'path' => __FILE__,
		'name' => 'UserJourney',
		'author'=> array(
			'[https://www.mediawiki.org/wiki/User:Darenwelsh Daren Welsh]'
		),
		'descriptionmsg' => 'userjourney-desc',
		'version' => 0.1,
		'url' => 'https://www.mediawiki.org/wiki/Extension:UserJourney',
	);

}


/*
 * Configure global variables
 */

// Number of revisions (in string form used within MySQL queries)
// $wgUJnumRevisionsAlias = "rev_count"; // Alias used for MySQL queries

// Number of pages revised (in string form used within MySQL queries)
// $wgUJnumPagesRevisedAlias = "page_count"; // Alias used for MySQL queries

// How the score is calculated (in string form used within MySQL queries)
// function getScoreDefinition( $scope = "explicit" ){

//   global $wgUJnumRevisionsAlias, $wgUJnumPagesRevisedAlias;

//   if( $scope === "explicit" ){

//     $rev_count = "COUNT(rev_id)";
//     $page_count = "COUNT(DISTINCT rev_page)";

//   } else {

//     $rev_count = $wgUJnumRevisionsAlias;
//     $page_count = $wgUJnumPagesRevisedAlias;

//   }

//   $output = "{$page_count} + SQRT( {$rev_count} - {$page_count} ) * 2";

//   return $output;
// }

// $wgUJscoreDefinition = getScoreDefinition( "explicit" );
// $wgUJscoreDefinitionUsingAliases = getScoreDefinition( "relative" );

// Max score counted toward plots with moving averages
// $wgUJscoreCeiling = 100;

// Number of days in which to compare scores of logged-in user against others
// (used to find suitable competitors)
// $wgUJdaysToDetermineCompetitors = 14;

// Number of days to plot for competitions
// $wgUJdaysToPlotCompetition = 30;


/*
	STUFF FROM CONTRIBUTION SCORES

*/
  /*

define( 'CONTRIBUTIONSCORES_MAXINCLUDELIMIT', 50 );
$wgContribScoreReports = null;

// These settings can be overridden in LocalSettings.php.

// Set to true to exclude bots from the reporting.
$wgContribScoreIgnoreBlockedUsers = false;

// Set to true to exclude blocked users from the reporting.
$wgContribScoreIgnoreBots = false;

// Set to true to use real user names when available. Only for MediaWiki 1.19 and later.
$wgContribScoresUseRealName = false;

// Set to true to disable cache for parser function and inclusion of table.
$wgContribScoreDisableCache = false;

// POSSIBLY MODIFY LATER
// $wgAutoloadClasses['ContributionScores'] = __DIR__ . '/ContributionScores_body.php';
// $wgSpecialPages['ContributionScores'] = 'ContributionScores';

// REMOVE
// $wgMessagesDirs['ContributionScores'] = __DIR__ . '/i18n';
// REMOVE
// $wgExtensionMessagesFiles['ContributionScoresAlias'] = __DIR__ . '/ContributionScores.alias.php';
$wgExtensionMessagesFiles['ContributionScoresMagic'] =
   __DIR__ . '/ContributionScores.i18n.magic.php';

$wgHooks['ParserFirstCallInit'][] = 'efContributionScores_Setup';

function efContributionScores_Setup( &$parser ) {
   $parser->setFunctionHook( 'cscore', 'efContributionScores_Render' );

   return true;
}

function efContributionScores_Render( &$parser, $usertext, $metric = 'score' ) {
   global $wgContribScoreDisableCache;

   if ( $wgContribScoreDisableCache ) {
       $parser->disableCache();
   }

   $user = User::newFromName( $usertext );
   $dbr = wfGetDB( DB_SLAVE );

   if ( $user instanceof User && $user->isLoggedIn() ) {
       global $wgLang;

       if ( $metric == 'score' ) {
           $res = $dbr->select( 'revision',
               'COUNT(DISTINCT rev_page)+SQRT(COUNT(rev_id)-COUNT(DISTINCT rev_page))*2 AS wiki_rank',
               array( 'rev_user' => $user->getID() ) );
           $row = $dbr->fetchObject( $res );
           $output = $wgLang->formatNum( round( $row->wiki_rank, 0 ) );
       } elseif ( $metric == 'changes' ) {
           $res = $dbr->select( 'revision',
               'COUNT(rev_id) AS rev_count',
               array( 'rev_user' => $user->getID() ) );
           $row = $dbr->fetchObject( $res );
           $output = $wgLang->formatNum( $row->rev_count );
       } elseif ( $metric == 'pages' ) {
           $res = $dbr->select( 'revision',
               'COUNT(DISTINCT rev_page) AS page_count',
               array( 'rev_user' => $user->getID() ) );
           $row = $dbr->fetchObject( $res );
           $output = $wgLang->formatNum( $row->page_count );
       } else {
           $output = wfMessage( 'contributionscores-invalidmetric' )->text();
       }
   } else {
       $output = wfMessage( 'contributionscores-invalidusername' )->text();
   }

   return $parser->insertStripItem( $output, $parser->mStripState );
}

*/

/*
	ORIGINAL STUFF

*/


/*
$wgExtensionMessagesFiles['UserJourney'] = __DIR__ . '/UserJourney.i18n.php';
$GLOBALS['wgMessagesDirs']['UserJourney'] = __DIR__ . '/i18n';

// Necessary?
$wgExtensionMessagesFiles['UserJourneyAlias'] = __DIR__ . '/UserJourney.alias.php';

// Not currently used
//$wgAutoloadClasses['UserJourney'] = __DIR__ . '/UserJourney.body.php'; // autoload body class

// Special Page
$wgAutoloadClasses['SpecialUserJourney'] = __DIR__ . '/SpecialUserJourney.php'; // autoload special page class
$wgSpecialPages['UserJourney'] = 'SpecialUserJourney'; // register special page

/*
No Longer Used


// 1 of the earliest hooks in page load
// $wgHooks['ArticlePageDataBefore'][] = 'UserJourney::onArticlePageDataBefore';

// collects extension info from hook that provides necessary inputs
// but does not record the information in the database
$wgHooks['BeforeInitialize'][] = 'UserJourney::onBeforeInitialize';

// $wgHooks['ArticleUpdateBeforeRedirect'][] = 'UserJourney::onArticleUpdateBeforeRedirect';

// After save page request has been completed
$wgHooks['PageContentSaveComplete'][] = 'UserJourney::onPageContentSaveComplete';

// records the information at the latest possible time in order to
// record the length of time required to build the page.
$wgHooks['AfterFinalPageOutput'][] = 'UserJourney::recordInDatabase';

// update database (using maintenance/update.php)
$wgHooks['LoadExtensionSchemaUpdates'][] = 'UserJourney::updateDatabase';

// 1 of the last hooks in page load
// $wgHooks['BeforePageDisplay'][] = 'UserJourney::onBeforePageDisplay';


No Longer Used
*/
/*
 $userjourneyResourceTemplate = array(
 	'localBasePath' => __DIR__ . '/modules',
 	'remoteExtPath' => 'UserJourney/modules',
 );

 $wgResourceModules += array(
	// 'ext.userjourney.charts' => $userjourneyResourceTemplate + array(
  // 'position' => 'bottom', // added this since "bottom" was default pre-1.26
	// 	'styles' => 'charts/ext.userjourney.charts.css',
	// 	'scripts' => array(
	// 		'charts/Chart.js',
	// 		'charts/ext.userjourney.charts.js',
	// 	),

	// ),

	// 'ext.userjourney.d3.js' => $userjourneyResourceTemplate + array(
  // 'position' => 'bottom', // added this since "bottom" was default pre-1.26
	// 	'scripts' => array(
	// 		'd3js/ext.userjourney.d3.js',
	// 	),

	// ),

  // For "Overview"
  'ext.userjourney.myActivityByYear.nvd3' => $userjourneyResourceTemplate + array(
    'position' => 'bottom', // added this since "bottom" was default pre-1.26
    'styles' => array(
      'nvd3js/nv.d3.css',
      // 'nvd3js/ext.userjourney.my-activity-by-year.nvd3.css',
    ),
    'scripts' => array(
      'nvd3js/nv.d3.js',
      'nvd3js/ext.userjourney.my-activity-by-year.nvd3.js',
    ),
    'dependencies' => array(
      // 'ext.userjourney.d3.js',
      'd3.js',
    ),

  ),

  // For "My Activity"
	'ext.userjourney.myActivity.nvd3' => $userjourneyResourceTemplate + array(
    'position' => 'bottom', // added this since "bottom" was default pre-1.26
		'styles' => array(
			'nvd3js/nv.d3.css',
			'nvd3js/ext.userjourney.my-activity.nvd3.css',
		),
		'scripts' => array(
			'nvd3js/nv.d3.js',
			'nvd3js/ext.userjourney.my-activity.nvd3.js',
		),
		'dependencies' => array(
      // 'ext.userjourney.d3.js',
      'd3.js',
		),

	),

  // For "Compare Activity"
  'ext.userjourney.compare.nvd3' => $userjourneyResourceTemplate + array(
    'position' => 'bottom', // added this since "bottom" was default pre-1.26
    'styles' => array(
      'nvd3js/nv.d3.css',
      'nvd3js/ext.userjourney.compare.nvd3.css',
    ),
    'scripts' => array(
      'nvd3js/nv.d3.js',
      'nvd3js/ext.userjourney.compare-line-with-window.nvd3.js',
      'nvd3js/ext.userjourney.compare-stacked.nvd3.js',
      // 'nvd3js/ext.userjourney.compare-stream.nvd3.js',
    ),
    'dependencies' => array(
      // 'ext.userjourney.d3.js',
      'd3.js',
    ),

  ),

  // For "Compare Scores Stacked Plot"
  // 'ext.userjourney.compareScoreStackedPlot.nvd3' => $userjourneyResourceTemplate + array(
  // 'position' => 'bottom', // added this since "bottom" was default pre-1.26
  //   'styles' => array(
  //     'nvd3js/nv.d3.css',
  //     // 'nvd3js/ext.userjourney.comparescoresstacked.nvd3.css',
  //   ),
  //   'scripts' => array(
  //     'nvd3js/nv.d3.js',
  //     'nvd3js/ext.userjourney.comparescoresstacked.nvd3.js',
  //     'nvd3js/ext.userjourney.comparescoresstackedstream.nvd3.js',
  //   ),
  //   'dependencies' => array(
  //     // 'ext.userjourney.d3.js',
  //     'd3.js',
  //   ),

  // ),

);
