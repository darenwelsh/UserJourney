<?php

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

/*
	STUFF FROM CONTRIBUTION SCORES

*/

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



/*
	ORIGINAL STUFF

*/



$wgExtensionMessagesFiles['UserJourney'] = __DIR__ . '/UserJourney.i18n.php';
$GLOBALS['wgMessagesDirs']['UserJourney'] = __DIR__ . '/i18n';

// Necessary?
$wgExtensionMessagesFiles['UserJourneyAlias'] = __DIR__ . '/UserJourney.alias.php';

$wgAutoloadClasses['UserJourney'] = __DIR__ . '/UserJourney.body.php'; // autoload body class

// Special Page
$wgAutoloadClasses['SpecialUserJourney'] = __DIR__ . '/SpecialUserJourney.php'; // autoload special page class
$wgSpecialPages['UserJourney'] = 'SpecialUserJourney'; // register special page

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
$wgHooks['BeforePageDisplay'][] = 'UserJourney::onBeforePageDisplay';

 $wiretapResourceTemplate = array(
 	'localBasePath' => __DIR__ . '/modules',
 	'remoteExtPath' => 'UserJourney/modules',
 );

 $wgResourceModules += array(

	'ext.wiretap.charts' => $wiretapResourceTemplate + array(
		'styles' => 'charts/ext.wiretap.charts.css',
		'scripts' => array(
			'charts/Chart.js',
			'charts/ext.wiretap.charts.js',
		),

	),

	'ext.wiretap.d3.js' => $wiretapResourceTemplate + array(
		'scripts' => array(
			'd3js/ext.wiretap.d3.js',
		),

	),

	'ext.wiretap.charts.nvd3' => $wiretapResourceTemplate + array(
		'styles' => array(
			'nvd3js/nv.d3.css',
			'nvd3js/ext.wiretap.nvd3.css',
		),
		'scripts' => array(
			'nvd3js/nv.d3.js',
			'nvd3js/ext.wiretap.nvd3.js',
		),
		'dependencies' => array(
			'ext.wiretap.d3.js',
		),

	),


 );
