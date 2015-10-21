<?php
/*
 * Copyright (c) 2014-2015 The MITRE Corporation
 * Copyright (c) 2012 Van de Bugger
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

if ( ! defined( 'MEDIAWIKI' ) ) {
	die( 'This is an extension to the MediaWiki package and cannot be run standalone' );
}

$GLOBALS['wgExtensionCredits']['semantic'][] = array(
	'path' => __FILE__,
	'name' => 'Semantic Title',
	'version' => '3.1',
	'author' => array(
		'[https://www.mediawiki.org/wiki/User:Cindy.cicalese Cindy Cicalese]',
		'[https://www.mediawiki.org/wiki/User:Van_de_Bugger Van de Bugger]'
	),
	'descriptionmsg' => 'semantictitle-desc',
	'url' => 'https://www.mediawiki.org/wiki/Extension:SemanticTitle',
	'license-name' => 'MIT'
);

$GLOBALS['wgAutoloadClasses']['SemanticTitle'] =
	__DIR__ . '/SemanticTitle.class.php';

$GLOBALS['wgHooks']['ParserFirstCallInit'][] = 'SemanticTitle::setup';
$GLOBALS['wgHooks']['ParserBeforeStrip'][] =
  'SemanticTitle::onParserBeforeStrip';
$GLOBALS['wgHooks']['BeforePageDisplay'][] =
	'SemanticTitle::onBeforePageDisplay';
$GLOBALS['wgHooks']['LinkBegin'][] = 'SemanticTitle::onLinkBegin';
$GLOBALS['wgHooks']['SelfLinkBegin'][] = 'SemanticTitle::onSelfLinkBegin';

$GLOBALS['wgMessagesDirs']['SemanticTitle'] = __DIR__ . '/i18n';
$GLOBALS['wgExtensionMessagesFiles']['SemanticTitle'] =
	__DIR__ . '/SemanticTitle.i18n.php';

$GLOBALS['wgExtensionMessagesFiles']['SemanticTitleMagic'] =
	__DIR__ . '/SemanticTitle.i18n.magic.php';
