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

class SemanticTitle {

	/**
	 * @since 2.0
	 *
	 * @param Parser &$parser
	 */
	public static function setup( &$parser ) {

		$parser->setFunctionHook( 'semantic-title',
			__CLASS__ . '::hookSemanticTitle' );

		return true;

	}

	/**
	 * Handle #semantic-title parser function.
	 *
	 * @since 1.0
	 *
	 * @param $parser
	 * @param $pagename
	 */
	public static function hookSemanticTitle( $parser, $pagename ) {

		$title = Title::newFromText( $pagename );

		if ( is_null( $title ) ) {

			return $pagename;
		}

		self::getDisplayTitle( $title, $pagename );

		return $pagename;

	}

	/**
	 * Get semantic title text.
	 *
	 * @since 2.1
	 *
	 * @param Title $title
	 */
	public static function getText( Title $title ) {

		$text = $title->getPrefixedText();

		self::getDisplayTitle( $title, $text );

		return $text;

	}

	/**
	 * Handle links. Implements LinkBegin hook of Linker class.
	 * If a link is customized by a user (e. g. [[Target|Text]]) it should
	 * remain intact. Let us assume a link is not customized if its text is
	 * the prefixed or (to support semantic queries) non-prefixed title of
	 * the target page.
	 *
	 * @since 1.0
	 *
	 * @param $dummy
	 * @param Title $target
	 * @param &$text
	 * @param &$customAttribs
	 * @param &$query
	 * @param &$options
	 * @param &$ret
	 */
	public static function onLinkBegin( $dummy, Title $target, &$text,
		&$customAttribs, &$query, &$options, &$ret ) {

		if ( isset( $text ) ) {

			if ( !is_string( $text ) ) {

				return true;

			}

			$title = Title::newFromText( $text );

			if ( is_null( $title ) ) {

				return true;

			}

			if ( $title->getText() != $target->getText() ) {

				return true;

			}

			if ( $title->getSubjectNsText() == $target->getSubjectNsText() ||
				$title->getSubjectNsText() == '' ) {

				self::getDisplayTitle( $target, $text );

			}

		} else {

			self::getDisplayTitle( $target, $text );

		}

		return true;

	}

	/**
	 * Handle self links.
	 *
	 * @since 1.3
	 *
	 * @param Title $nt
	 * @param &$html
	 * @param &$trail
	 * @param &$prefix
	 */
	public static function onSelfLinkBegin( Title $nt, &$html, &$trail,
		&$prefix, &$ret ) {

		self::getDisplayTitle( $nt, $html );

		return true;

	}

	/**
	 * Display subtitle if requested
	 *
	 * @since 1.0
	 *
	 * @param OutputPage &$out
	 * @param Skin &$sk
	 */
	public static function onBeforePageDisplay( OutputPage &$out, Skin &$sk ) {

		if ( isset( $GLOBALS['wgSemanticTitleHideSubtitle'] ) &&
			! $GLOBALS['wgSemanticTitleHideSubtitle'] ) {

			$title = $out->getTitle();
			if ( ! $title instanceof Title ) {

				return true;

			}

			// remove fragment (LiquidThreads interaction)
			$title = Title::newFromText( $title->getPrefixedText() );

			if ( $title->isTalkPage() ) {

				if ( $title->getSubjectPage()->exists() ) {

					$pagename = $title->getSubjectPage()->getPrefixedText();
					$displayTitle = $pagename;
					$found = self::getDisplayTitle( $title->getSubjectPage(),
						$displayTitle );

				}

			} else {

				if ( $title->exists() ) {

					$pagename = $title->getPrefixedText();
					$displayTitle = $pagename;
					$found = self::getDisplayTitle( $title, $displayTitle );

				}

			}


			if ( ! $found || $displayTitle == $pagename ) {

				return true;

			}

			if ( $title->isTalkPage() ) {
				$pagename = $title->getPrefixedText();
			}

			$out->setSubtitle(
				$pagename .
				( $out->getSubtitle() == '' ? '' : ' ' . $out->getSubtitle() )
			);
		}

		return true;

	}

	/**
	 * Handle page title.
	 *
	 * @since 3.0
	 *
	 * @param Parser &$parser
	 * @param &$text
	 * @param &$strip_state
	 */
	public static function onParserBeforeStrip( Parser &$parser, &$text,
		&$strip_state ) {

		$title = $parser->getTitle();
		if ( ! $title instanceof Title ) {

			return true;

		}

		// remove fragment (LiquidThreads interaction)
		$title = Title::newFromText( $title->getPrefixedText() );

		$displayTitle = null;

		if ( $title->isTalkPage() ) {

			if ( $title->getSubjectPage()->exists() ) {

				self::getDisplayTitle( $title->getSubjectPage(), $displayTitle );
				if ( is_null( $displayTitle ) ) {

					return true;

				}

				$displayTitle = wfMessage( 'semantictitle-talkpagetitle',
					$displayTitle )->plain();
			}

		} else {

			if ( $title->exists() ) {

				self::getSemanticTitle( $title, $displayTitle );

			}

		}

		if ( is_null( $displayTitle ) ) {

			return true;

		}

		$parser->mOutput->setTitleText( $displayTitle );

		if ( $title->exists() ) {

			$parser->mOutput->setProperty( 'displaytitle', $displayTitle );
			self::setDisplayTitle( $title, $displayTitle );

		}


		return true;
	}

	/**
	 * Get displaytitle page property text.
	 *
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param &$displayTitle
	 */
	private static function getDisplayTitle( Title $title, &$displayTitle ) {

		$id = $title->getArticleID();

		$dbr = wfGetDB( DB_SLAVE );
		$result = $dbr->select(
			'page_props',
			array( 'pp_value' ),
			array(
				'pp_page' => $id,
				'pp_propname' => 'displaytitle'
			),
			__METHOD__
		);

		if ( $result->numRows() > 0 ) {
			$row = $result->fetchRow();
			$displayTitle = $row['pp_value'];
			return true;
		}

		return false;
	}

	/**
	 * Set displaytitle page property text.
	 *
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param &$displayTitle
	 */
	private static function setDisplayTitle( Title $title, &$displayTitle ) {

		$found = self::getDisplayTitle( $title, $old );

		if ( $found === false ) {
			
			$id = $title->getArticleID();

			$dbw = wfGetDB( DB_MASTER );
			$result = $dbw->insert(
				'page_props',
				array(
					'pp_page' => $id,
					'pp_propname' => 'displaytitle',
					'pp_value' => $displayTitle
				),
				__METHOD__
			);

		} else if ( $old !== $displayTitle ) {

			$id = $title->getArticleID();

			$dbw = wfGetDB( DB_MASTER );
			$result = $dbw->update(
				'page_props',
				array(
					'pp_value' => $displayTitle
				),
				array(
					'pp_page' => $id,
					'pp_propname' => 'displaytitle',
				),
				__METHOD__
			);

		}

	}

	/**
	 * Retrieves semantic title text, if any, for the specified title.
	 * The semanticTitle parameter is unchanged if no semantic titie exists
	 * for the current title object.
	 *
	 * @since 2.0
	 *
	 * @param $semanticTitle
	 */
	private static function getSemanticTitle( $title, &$semanticTitle ) {

		if ( $title->isRedirect() ) {
			return;
		}

		if ( ! isset( $GLOBALS['wgSemanticTitleProperties'] ) &&
			! isset( $GLOBALS['wgSemanticTitleCargoFields'] ) ) {
			return;
		}

		$namespace = $title->getNamespace();

		if ( isset( $GLOBALS['wgSemanticTitleProperties'] ) &&
			array_key_exists( $namespace,
			$GLOBALS['wgSemanticTitleProperties'] ) ) {

			$label = $GLOBALS['wgSemanticTitleProperties'][ $namespace ];

			$store = \SMW\StoreFactory::getStore();

			if ( class_exists( 'SMWDataItem' ) ) {
				$subj = SMWDIWikiPage::newFromTitle( $title );
			} else {
				$subj = $title;
			}

			$data = $store->getSemanticData( $subj );
			$property = SMWDIProperty::newFromUserLabel( $label );
			$values = $data->getPropertyValues( $property );

			if ( count( $values ) > 0 ) {

				$value = array_shift( $values );

				if ( $value->getDIType() == SMWDataItem::TYPE_STRING ||
					$value->getDIType() == SMWDataItem::TYPE_BLOB ) {

					$name = $value->getString();

					if ( $name != '' ) {

						$semanticTitle = htmlspecialchars( $name, ENT_QUOTES );
						return;

					}

				}

			}

		}

		if ( isset( $GLOBALS['wgSemanticTitleCargoFields'] ) &&
			array_key_exists( $namespace,
			$GLOBALS['wgSemanticTitleCargoFields'] ) ) {

			$table =
				$GLOBALS['wgSemanticTitleCargoFields'][ $namespace ]['table'];

			$field =
				$GLOBALS['wgSemanticTitleCargoFields'][ $namespace ]['field'];

			$query = CargoSQLQuery::newFromValues( $table, $field,
				'_pageName="' . $title->getPrefixedText() . '"', null,
				'_pageName', null, null, '' );
			$rows = $query->run();

			if ( count( $rows ) > 0 ) {

				$name = $rows[0][$field];
				$semanticTitle = htmlspecialchars( $name, ENT_QUOTES );
				return;

			}
		}
	}

}
