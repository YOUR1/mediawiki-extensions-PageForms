<?php

/**
 * SMW result printer for graphs using graphViz.
 * In order to use this printer you need to have both
 * the graphViz library installed on your system and
 * have the graphViz MediaWiki extension installed.
 * 
 * @file SRF_Graph.php
 * @ingroup SemanticResultFormats
 *
 * @licence GNU GPL v2+
 * @author Frank Dengler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

class SRFGraph extends SMWResultPrinter {
	
	public static $NODE_SHAPES = array(
		'box',
		'box3d',
		'circle',
		'component',
		'diamond',
		'doublecircle',
		'doubleoctagon',
		'egg',
		'ellipse',
		'folder',
		'hexagon',
		'house',
		'invhouse',
		'invtrapezium',
		'invtriangle',
		'Mcircle',
		'Mdiamond',
		'Msquare',
		'none',
		'note',
		'octagon',
		'parallelogram',
		'pentagon ',
		'plaintext',
		'point',
		'polygon',
		'rect',
		'rectangle',
		'septagon',
		'square',
		'tab',
		'trapezium',
		'triangle',
		'tripleoctagon',
		'image'
	);
	
	protected $m_graphName;
	protected $m_graphLabel;
	protected $m_graphColor;
	protected $m_graphLegend;
	protected $m_graphLink;
	protected $m_rankdir;
	protected $m_graphSize;
	protected $m_labelArray = array();
	protected $m_graphColors = array( 'black', 'red', 'green', 'blue', 'darkviolet', 'gold', 'deeppink', 'brown', 'bisque', 'darkgreen', 'yellow', 'darkblue', 'magenta', 'steelblue2' );
	protected $m_nameProperty;
	protected $m_nodeShape;
	protected $m_parentRelation;
	protected $m_wordWrapLimit;
		
	/**
	 * (non-PHPdoc)
	 * @see SMWResultPrinter::handleParameters()
	 */
	protected function handleParameters( array $params, $outputmode ) {
		parent::handleParameters( $params, $outputmode );
		
		$this->m_graphName = trim( $params['graphname'] );
		$this->m_graphSize = trim( $params['graphsize'] );
		
		$this->m_graphLegend = $params['graphlegend'];
		$this->m_graphLabel = $params['graphlabel'];
		
		$this->m_rankdir = strtoupper( trim( $params['arrowdirection'] ) );
		
		$this->m_graphLink = $params['graphlink'];
		$this->m_graphColor =$params['graphcolor'];
		
		$this->m_nameProperty = $params['nameproperty'] === false ? false : trim( $params['nameproperty'] );
		
		$this->m_parentRelation = strtolower( trim( $params['relation'] ) ) == 'parent';
		
		$this->m_nodeShape = $params['nodeshape'];
		$this->m_wordWrapLimit = $params['wordwraplimit'];
		
	}
	
	protected function getResultText( SMWQueryResult $res, $outputmode ) {
		if ( !is_callable( 'GraphViz::graphvizParserHook' ) ) {
			wfWarn( 'The SRF Graph printer needs the GraphViz extension to be installed.' );
			return '';
		}
		
		$this->isHTML = true;

		$graphInput = "digraph $this->m_graphName {";
		
		if ( $this->m_graphSize != '' ) $graphInput .= "size=\"$this->m_graphSize\";";
		
		$graphInput .= "rankdir=$this->m_rankdir;";
		
		while ( $row = $res->getNext() ) {
			$graphInput .= $this->getGVForItem( $row, $outputmode );
		}
		
		$graphInput .= "}";
		
		// Calls graphvizParserHook function from MediaWiki GraphViz extension
		$result = GraphViz::graphvizParserHook( $graphInput, "", $GLOBALS['wgParser'], true );
		
		if ( $this->m_graphLegend && $this->m_graphColor ) {
			$arrayCount = 0;
			$arraySize = count( $this->m_graphColors );
			$result .= "<P>";
			
			foreach ( $this->m_labelArray as $m_label ) {
				if ( $arrayCount >= $arraySize ) {
					$arrayCount = 0;
				}				
				
				$color = $this->m_graphColors[$arrayCount];
				$result .= "<font color=$color>$color: $m_label </font><br />";
				
				$arrayCount += 1;
			}
			
			$result .= "</P>";
		}
		
		
		return $result;
	}

	/**
	 * Returns the GV for a single subject.
	 * 
	 * @since 1.5.4
	 * 
	 * @param array $row
	 * @param $outputmode
	 * 
	 * @return string
	 */
	protected function getGVForItem( array /* of SMWResultArray */ $row, $outputmode ) {	
		$segments = array();
		
		// Loop throught all fields of the record.
		foreach ( $row as $i => $resultArray ) {

			// Loop throught all the parts of the field value.
			while ( ( $object = $resultArray->getNextDataValue() ) !== false ) {
				$propName = $resultArray->getPrintRequest()->getLabel();
				$isName = $this->m_nameProperty ? ( $i != 0 && $this->m_nameProperty === $propName ) : $i == 0;
				
				if ( $isName ) {
					$name = $this->getWordWrappedText( $object->getShortText( $outputmode ), $this->m_wordWrapLimit );
				}
				
				if ( !( $this->m_nameProperty && $i == 0 ) ) {
					$segments[] = $this->getGVForDataValue( $object, $outputmode, $isName, $name, $propName );
				}
			}
		}

		return implode( "\n", $segments );
	}
	
	/**
	 * Returns the GV for a single SMWDataValue.
	 * 
	 * @since 1.5.4
	 * 
	 * @param SMWDataValue $object
	 * @param $outputmode
	 * @param boolean $isName Is this the name that should be used for the node?
	 * @param string $name
	 * @param string $labelName
	 * 
	 * @return string
	 */	
	protected function getGVForDataValue( SMWDataValue $object, $outputmode, $isName, $name, $labelName ) {
		$graphInput = '';
		$text = $object->getShortText( $outputmode );

		if ( $this->m_graphLink ) {
			$nodeLinkTitle = Title::newFromText( $text );
			$nodeLinkURL = $nodeLinkTitle->getText();
		}
		
		$text = $this->getWordWrappedText( $text, $this->m_wordWrapLimit );
		
		if ( $this->m_graphLink ) {
			$graphInput .= " \"$text\" [URL = \"$nodeLinkURL\"";

			// query the SMWStore to see whether this node has a value for the imageproperty
			if($this->m_imageProperty) {
				$store = smwfGetStore();
				$property = SMWDIProperty::newFromUserLabel( $this->m_imageProperty );
				$subject = SMWDIWikiPage::newFromTitle( $nodeLinkTitle );		
				$prop_vals = $store->getPropertyValues($subject, $property);

				if(sizeof($prop_vals)>0) {
					// Take the first property value for imageproperty. Makes no sense to have multiple images 			
					// for the same node.
					$prop_val = $prop_vals[0];
					if($prop_val && $prop_val->getDIType() === SMWDataItem::TYPE_WIKIPAGE &&
						$prop_val->getNamespace() === NS_FILE ) { // image properties should point to an image page
						$image = $prop_val->getTitle();
						$graphInput .= ",image=\"$image\",shape=none";
					} 
				}
			} 
			if ($this->m_nodeColor) {
				$store = smwfGetStore();
				$property = SMWDIProperty::newFromUserLabel( $this->m_nodeColor );
				$subject = SMWDIWikiPage::newFromTitle( $nodeLinkTitle );
				$prop_vals = $store->getPropertyValues($subject, $property);

				if(sizeof($prop_vals)>0) {
					// Take the first property value for color property. Makes no sense to have multiple colors 			
					// for the same node. 
					$prop_val = $prop_vals[0];

					if($prop_val && $prop_val->getDIType() === SMWDataItem::TYPE_STRING)  {
						$color = $prop_val->getString();
						$graphInput .= ",style=\"filled, $this->m_nodeStyle\", fillcolor=\"$color\"";
					}
				}

			}
			
			$graphInput .= "]; ";
			
		}

		if ( !$isName ) {
			$graphInput .= $this->m_parentRelation ? " \"$text\" -> \"$name\" " : " \"$name\" -> \"$text\" ";
			
			if ( $this->m_graphLabel || $this->m_graphColor || $this->m_customArrows ) {
				$graphInput .= ' [';
				
				if ( array_search( $labelName, $this->m_labelArray, true ) === false ) {
					$this->m_labelArray[] = $labelName;
				}
				
				$color = $this->m_graphColors[array_search( $labelName, $this->m_labelArray, true )];

				if ( $this->m_graphLabel ) {
					$graphInput .= "label=\"$labelName\"";
					if ( $this->m_graphColor ) $graphInput .= ",fontcolor=$color,";
				}
				
				if ( $this->m_graphColor ) {
					$graphInput .= "color=$color";
				}
				
				$graphInput .= ']';
	
			}
			
			$graphInput .= ';';
		}

		return $graphInput;
	}
	
	function getArrowStyle($propertyName, $edgeProperty) {  
		$styleString ='normal';
		$store = smwfGetStore();
		$subject = SMWDIProperty::newFromUserLabel($propertyName);
		$subject = $subject ? $subject->getDiWikiPage() : null;
		
		$styleProperty = SMWDIProperty::newFromUserLabel( $edgeProperty );
		$prop_vals = $subject ? $store->getPropertyValues($subject, $styleProperty) : array();
		
		if(sizeof($prop_vals)>0) {
			// Take the first property value for style property. Makes no sense to have multiple arrow heads
			// for the same node.
			$prop_val = $prop_vals[0];
			if( $prop_val && $prop_val->getDIType() === SMWDataItem::TYPE_STRING ) {
				$styleString = $prop_val->getString();
			}
		}
		
		
		return $styleString;
	}
	
	/**
	 * Determines the arrow head or arrow tail for a property.
	 * 
	 * @param string $propertyName the name of the property for which the arrow head or tail is to be determined
	 * @param string $arrowProperty one of $this->m_arrowHeadProperty or $this->m_arrowTailProperty
	 * @return string the value of the arrow type, as registered in the wiki.
	 */
	function getArrowType($propertyName, $arrowProperty) {
		$arrow = "none";
		if( empty($propertyName) ) {
			return $arrow;
		}
		
		$store = smwfGetStore();
		$subject = SMWDIProperty::newFromUserLabel( $propertyName );
		$subject = $subject ? $subject->getDiWikiPage() : null;
		
		$arrowProperty = SMWDIProperty::newFromUserLabel( $arrowProperty );
		$prop_vals = $subject ? $store->getPropertyValues($subject, $arrowProperty) : array();
				
		if(sizeof($prop_vals)>0) {
			// Take the first property value for arrowheadproperty. Makes no sense to have multiple arrow heads
			// for the same node.
			$prop_val = $prop_vals[0];
			if( $prop_val && $prop_val->getDIType() === SMWDataItem::TYPE_STRING ) {
				$arrow = $prop_val->getString();				
			}
		}
		
		return $arrow;		
	}
	
	/**
	 * Returns the word wrapped version of the provided text. 
	 * 
	 * @since 1.5.4
	 * 
	 * @param string $text
	 * @param integer $charLimit
	 * 
	 * @return string
	 */
	protected function getWordWrappedText( $text, $charLimit ) {
		$charLimit = max( array( $charLimit, 1 ) );
		$segments = array();
		
		while ( strlen( $text ) > $charLimit ) {
			// Find the last space in the allowed range.
			$splitPosition = strrpos( substr( $text, 0, $charLimit ), ' ' );
			
			if ( $splitPosition === false ) {
				// If there is no space (lond word), find the next space.
				$splitPosition = strpos( $text, ' ' );
				
				if ( $splitPosition === false ) {
					// If there are no spaces, everything goes on one line.
					 $splitPosition = strlen( $text ) - 1;
				}
			}
			
			$segments[] = substr( $text, 0, $splitPosition + 1 );
			$text = substr( $text, $splitPosition + 1 );
		}
		
		$segments[] = $text;
		
		return implode( '\n', $segments );
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SMWResultPrinter::getName()
	 */
	public function getName() {
		return wfMessage( 'srf-printername-graph' )->text();
	}

	/**
	 * @see SMWResultPrinter::getParamDefinitions
	 *
	 * @since 1.8
	 *
	 * @param $definitions array of IParamDefinition
	 *
	 * @return array of IParamDefinition|array
	 */
	public function getParamDefinitions( array $definitions ) {
		$params = parent::getParamDefinitions( $definitions );

		$params['graphname'] = array(
			'default' => 'QueryResult',
			'message' => 'srf_paramdesc_graphname',
		);

		$params['graphsize'] = array(
			'type' => 'string',
			'default' => '',
			'message' => 'srf_paramdesc_graphsize',
			'manipulatedefault' => false,
		);

		$params['graphlegend'] = array(
			'type' => 'boolean',
			'default' => false,
			'message' => 'srf_paramdesc_graphlegend',
		);

		$params['graphlabel'] = array(
			'type' => 'boolean',
			'default' => false,
			'message' => 'srf_paramdesc_graphlabel',
		);

		$params['graphlink'] = array(
			'type' => 'boolean',
			'default' => false,
			'message' => 'srf_paramdesc_graphlink',
		);

		$params['graphcolor'] = array(
			'type' => 'boolean',
			'default' => false,
			'message' => 'srf_paramdesc_graphcolor',
		);

		$params['arrowdirection'] = array(
			'aliases' => 'rankdir',
			'default' => 'LR',
			'message' => 'srf_paramdesc_rankdir',
			'values' => array( 'LR', 'RL', 'TB', 'BT' ),
		);

		$params['nodeshape'] = array(
			'default' => false,
			'message' => 'srf-paramdesc-graph-nodeshape',
			'manipulatedefault' => false,
			'values' => self::$NODE_SHAPES,
		);

		$params['relation'] = array(
			'default' => 'child',
			'message' => 'srf-paramdesc-graph-relation',
			'manipulatedefault' => false,
			'values' => array( 'parent', 'child' ),
		);

		$params['nameproperty'] = array(
			'default' => false,
			'message' => 'srf-paramdesc-graph-nameprop',
			'manipulatedefault' => false,
		);

		$params['wordwraplimit'] = array(
			'type' => 'integer',
			'default' => 25,
			'message' => 'srf-paramdesc-graph-wwl',
			'manipulatedefault' => false,
		);
		
		return $params;
	}
	
}
