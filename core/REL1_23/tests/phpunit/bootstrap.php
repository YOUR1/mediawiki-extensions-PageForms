<?php
/**
 * Bootstrapping for MediaWiki PHPUnit tests
 * This file is included by phpunit and is NOT in the global scope.
 *
 * @file
 */

if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
	echo <<<EOF
You are running these tests directly from phpunit. You may not have all globals correctly set.
Running phpunit.php instead is recommended.
EOF;
	require_once __DIR__ . "/phpunit.php";
}


# $IP doesn't get passed to this script, so let's
# figure it out ourselves.

$IP2=getenv( 'MW_INSTALL_PATH' );
if ( $IP2 === false ) {
	$IP2=dirname(dirname( __DIR__));
}
require_once $IP2 . "/extensions/SemanticMediaWiki/tests/bootstrap.php";
