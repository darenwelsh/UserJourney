# UserJourney

MediaWiki extension to track each users' progression from new contributor to mastery.

## Installation

1. Obtain the code from [GitHub](https://github.com/darenwelsh/UserJourney)
1. Extract the files in a directory called ``UserJourney`` in your ``extensions/`` folder
1. Add the following code at the bottom of your "LocalSettings.php" file: ``require_once "$IP/extensions/UserJourney/UserJourney.php";``
1. Go to the directory /maintenance and run ``php update.php``
1. Go to "Special:Version" on your wiki to verify that the extension is successfully installed
1. Done

## Background

This extension is in early development. [Extension:Wiretap] (https://github.com/enterprisemediawiki/Wiretap) was used for a baseline to begin this project.

