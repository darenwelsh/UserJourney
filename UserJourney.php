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

$wgExtensionMessagesFiles['UserJourney'] = __DIR__ . '/UserJourney.i18n.php';
$GLOBALS['wgMessagesDirs']['UserJourney'] = __DIR__ . '/i18n';

// Necessary?
//$wgExtensionMessagesFiles['UserJourneyAlias'] = __DIR__ . '/UserJourney.alias.php';

$wgAutoloadClasses['UserJourney'] = __DIR__ . '/UserJourney.body.php'; // autoload body class

$wgAutoloadClasses['SpecialUserJourney'] = __DIR__ . '/SpecialUserJourney.php'; // autoload special page class
$wgSpecialPages['UserJourney'] = 'SpecialUserJourney'; // register special page

// collects extension info from hook that provides necessary inputs
// but does not record the information in the database
$wgHooks['BeforeInitialize'][] = 'UserJourney::updateTable';

// records the information at the latest possible time in order to
// record the length of time required to build the page.
$wgHooks['AfterFinalPageOutput'][] = 'UserJourney::recordInDatabase';

// update database (using maintenance/update.php)
$wgHooks['LoadExtensionSchemaUpdates'][] = 'UserJourney::updateDatabase';

// After save page request has been completed
$wgHooks['PageContentSaveComplete'][] = 'UserJourney::saveComplete';

$wiretapResourceTemplate = array(
	'localBasePath' => __DIR__ . '/modules',
	'remoteExtPath' => 'UserJourney/modules',
);

$wgResourceModules += array(

/*	'ext.wiretap.charts' => $wiretapResourceTemplate + array(
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
*/

);
