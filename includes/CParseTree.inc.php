<?php


class CParseTree {
	
	private 
		$mChildNodes;
	private 
		$mParentNode;
	private
		$mType;
	private
		$mToken;
	private
		$mLookAheadSet;
	private
		$mASTSymbol;
	private
		$mEnclosingRule;
		
	
	const
		RULES			= 'parse.rules',
		RULE			= 'parse.rule',
		TERMINAL		= 'parse.terminal',
		ALTERNATE		= 'parse.alternate',
		SEQUENCE		= 'parse.sequence',
		CALL			= 'parse.call',
		NONE_OR_ONE		= 'parse.noneOrOne', 	// [ .. ]
		NONE_OR_MANY	= 'parse.noneOrMany', 	// { .. }
		ONE_OR_MANY		= 'parse.oneOrMany', 	// +...
		CODE			= 'parse.code', 		// PHP tags
		ERROR			= 'parse.error'
		;
		
		
	function __construct() {
		
		$this->mToken 			= '-';
		$this->mType 			= '-';
	 	$this->mChildNodes  	= array();
	 	$this->mParentNode  	= null;
	 	$this->mLookAheadSet  	= array();
	 	$this->mASTSymbol  		= null;
	 	$this->mEnclosingRule  	= null;

	}
	
	static function errorTree() {
		
		$tree = new CParseTree();
		$tree->setType( self::ERROR );
		
		return $tree;
	}
	
	function setType( $newType ) {

		$this->mType = $newType;
		
	}
	
	function getType() {
		
		return $this->mType;
		
	}

	function setASTSymbol( $aASTSymbol ) {

		$this->mASTSymbol = $aASTSymbol;
		
	}
	
	function getASTSymbol() {
		
		return $this->mASTSymbol;
		
	}

	function setEnclosingRule( CParseTree $rule ) {

		$this->mEnclosingRule = $rule;
	
	}

	function getEnclosingRule() {
	
		return $this->mEnclosingRule;
	
	}


	function setToken( $newToken ) {

		$this->mToken = $newToken;
		
	}
	
	function getToken() {
		
		return $this->mToken;
		
	}
	
	function appendChild( $subTree ) {
		
		assert( null != $subTree );
		
		$this->mChildNodes[] = $subTree;
		$subTree->mParentNode = $this;
		
	}

	function getChildNodes( ) {
		
		return $this->mChildNodes;
		
	}

	function getChildNode( $index ) {
		
		return $this->mChildNodes[ $index ];
		
	}


	public function getTreeForVariable( $aPlaceholderName ) {

		if ( self::RULE !== $this->mType ) {
			return $this->mEnclosingRule->getTreeForVariableUsingTree( $aPlaceholderName, $this->mEnclosingRule ) ;
		} else {
			return $this->getTreeForVariableUsingTree( $aPlaceholderName, $tree ) ;
		}
		
	}

	private function getTreeForVariableUsingTree( $aPlaceholderName, $rule ) {

		
		if ( $this->mASTSymbol === $aPlaceholderName ) {
			return $this;
		}
		
		foreach ( $this->mChildNodes as $key => $son ) {

			$foundTree = $son->getTreeForVariableUsingTree( $aPlaceholderName, $rule  );
			if ( null !== $foundTree ) {
				return $foundTree;
			}
		}
		
		return null;
	}
	

	public function getLHSVariable( ) {
		
		$rule = $this->getEnclosingRule();
		
		return $rule->mASTSymbol ;
		
	}
	
	
	public function getAllVariables( ) {

		if ( self::RULE !== $this->mType ) {
			return $this->mEnclosingRule->realGetAllVariables(  ) ;
		} else {
			return $this->realGetAllVariables( ) ;
		}
		
	}

	private function realGetAllVariables( ) {

		$varSet = array();
		
		if ( null != $this->mASTSymbol ) {
			$varSet[ $this->mASTSymbol  ] = $this->mASTSymbol ;
		}
		
		foreach ( $this->mChildNodes as $key => $son ) {

			$subVarSet = $son->realGetAllVariables( );
			
			foreach( $subVarSet as $aVarName => $noUseValue ) {
				$varSet[ $aVarName ] = $aVarName;
			}
		}
		
		return $varSet;
		
	}
	
	

	function __toString() {
		return $this->getNiceString( FALSE );
	}
	
	function getNiceString( $generateAllDetail = TRUE ) {
		
		$rValue = '';
		$lSetString = '';
		
		switch( $this->mType ) {
			case self::RULES:
				$rValue = array();
				foreach ( $this->mChildNodes as $key => $value) {

					$rValue[] = $value . " ; ";
				}
				$rValue = $lSetString . implode( '<br /> ', $rValue );
				break;

			case self::SEQUENCE:
				$rValue = array();
				foreach ( $this->mChildNodes as $key => $value) {

					$rValue[] = ' ' . $value ;
				}
				$rValue = $lSetString . implode( ' ', $rValue );
				break;

			case self::ALTERNATE:
				$rValue = array();
				foreach ( $this->mChildNodes as $key => $value) {

					$rValue[] = ' ' . $value ;
				}
				$rValue = $lSetString . '( ' . implode( ' ) || ( ', $rValue ) . ' )';
				break;

			case self::NONE_OR_ONE:
				$rValue .= '[ ' . $this->mChildNodes[0] . ' ]';
				break;

			case self::NONE_OR_MANY:
				$rValue .= '{ ' . $this->mChildNodes[0] . ' }';
				break;

			case self::ONE_OR_MANY:
				$rValue .= '+ ' . $this->mChildNodes[0] . ' ';
				break;

			case self::RULE:
				$rValue .= $this->mToken . ( ( null !== $this->mASTSymbol ) ? ( '::' . $this->mASTSymbol ) : '' ) . ' =&gt; ' . $this->mChildNodes[0];
				break;

			case self::TERMINAL:
				$rValue = $this->mToken ;
				break;

			case self::CALL:
				$rValue = ' &lt;' . $this->mToken . ( ( null !== $this->mASTSymbol ) ? ( '::' . $this->mASTSymbol ) : '' ) . '&gt; ';
				break;

			case self::CODE:
				if ( TRUE === $generateAllDetail ) {
					$rValue = '<u>' . htmlspecialchars( $this->mToken ) . '</u>';
				} else {
					$rValue = '';
				}
				break;

			case self::ERROR:
				$rValue = ' ERROR ';
				break;
				
				
		}
		
		return $rValue;

		if ( 0 < count( $this->mLookAheadSet ) ) {
			return '[ ' . implode( ',', $this->mLookAheadSet ) . ' : ' . $rValue . ']';
		} else {
			return $rValue;
		}
		
	}

	public function getParentNode() {
		return $this->mParentNode;
	}

	public function getRootNode() {
		
		$node = $this;
		while( null !== $node->mParentNode ) {
			$node = $node->mParentNode;
		}

		if ( ! ( CParseTree::RULES === $node->getType() ) ) {
			echo '<br />Non RULES' . $this . '<br />';
			var_dump(debug_backtrace());
		}
		
		return $node;
	}
	
	
	public function getRuleForSymbol( $ntRuleName ) {
		
		$root = $this->getRootNode();
		
		foreach ( $root->mChildNodes as $key => $value ) {
			if ( $ntRuleName === $value->getToken() ) {
				return $value;
			}
		}
		
		return null;
	}
	
	
	private function appendToLookAheadSet( $aTerminal ) {
		
		if ( isset( $this->mLookAheadSet[$aTerminal] ) ) {
			return FALSE;
		} else {
			$this->mLookAheadSet[$aTerminal] = $aTerminal;
			return TRUE;
		}
		
	}

	private function appendLookAheadSetToLookAheadSet( CParseTree $aSet ) {
		
		foreach( $aSet->mLookAheadSet as $noUseKey => $value ) {
			$this->appendToLookAheadSet( $noUseKey );
		}
		
	}
	
	public function getLookAheadSet() {
		
		return $this->mLookAheadSet;

	}
	
	
	public function buildLookAheadClosure() {
		
		$root = $this->getRootNode();
		
		for( $n=0; $n<50; $n++ ) {
			$root->LAIteration(  );
		}
		
	}

	private function canReduceToEmpty( ) {
		
		
		if (  ( CParseTree::NONE_OR_ONE  === $this->getType()  )
		   || ( CParseTree::NONE_OR_MANY === $this->getType()  )
		   ) {
			return TRUE;
		}

		if (  ( CParseTree::ALTERNATE  === $this->getType()  )
		   ) {
			
			$mayEmpty = FALSE;
			foreach( $this->getChildNodes() as $key => $son ) {
				$mayEmpty = $mayEmpty || $son->canReduceToEmpty();
			}
			return TRUE;
			
		}

		if (  ( CParseTree::SEQUENCE  === $this->getType()  )
		   ) {
			
			$mayEmpty = TRUE;
			foreach( $this->getChildNodes() as $key => $son ) {
				$mayEmpty = $mayEmpty && $son->canReduceToEmpty();
			}
			return $mayEmpty;
			
		}
		
		return FALSE;
		
	}

	private function LAIteration() {

		
		switch( $this->getType() ) {
			
			case CParseTree::TERMINAL:
				$this->appendToLookAheadSet( $this->getToken()  );
				break;

			case CParseTree::CALL:
				$rule = $this->getRuleForSymbol( $this->getToken() );
				if ( null != $rule ) {
					$rhs = $rule->getChildNode(0);
					foreach( $rhs->mLookAheadSet as $noUseKey => $oneSymbol ) {
						$this->appendToLookAheadSet( $oneSymbol );
					}
				} else {
					// Non defined non-terminal
				}
				break;

			case CParseTree::ALTERNATE:
				foreach( $this->getChildNodes() as $key => $son ) {
					$son->LAIteration();
				}
				foreach( $this->getChildNodes() as $key => $son ) {
					$this->appendLookAheadSetToLookAheadSet( $son );
				}
				break;

			case CParseTree::RULE:
				$this->getChildNode(0)->LAIteration();
				break;

			case CParseTree::SEQUENCE:
				foreach( $this->getChildNodes() as $key => $son ) {
					$son->LAIteration();
				}
				if (  $this->getChildNode( 0 )->canReduceToEmpty() 
				   ) {
					$this->appendLookAheadSetToLookAheadSet( $this->getChildNode( 1 ) );
				}
				$this->appendLookAheadSetToLookAheadSet( $this->getChildNode( 0 ) );
				break;

			case CParseTree::NONE_OR_ONE:
			case CParseTree::NONE_OR_MANY:
			case CParseTree::ONE_OR_MANY:
				foreach( $this->getChildNodes() as $key => $son ) {
					$son->LAIteration();
				}
				$this->appendLookAheadSetToLookAheadSet( $this->getChildNode( 0 ) );
				break;


			case CParseTree::RULES:
				foreach( $this->getChildNodes() as $key => $son ) {
					$son->LAIteration();
				}
				break;

			case CParseTree::CODE:
				break;
				
			default:
				echo '???';
				throw new CFailedParseException();
				break;
			
		}
		
	}
	
	
	public function optimize( ) {

		for( $n=0; $n<100; $n++ ) {
			$this->optimizeIteration();
		}

	}

	public function optimizeIteration( ) {
		
		
		// reduce Alternatives with one and only one alternative
		if (  (  ( self::ALTERNATE === $this->mType )
		      && ( 1 === count( $this->mChildNodes ) )
		      )
		   || (  ( self::SEQUENCE === $this->mType )
			  && ( 1 === count( $this->mChildNodes ) )
			  )
		   ){
			$son = $this->mChildNodes[0];
			
			$this->mType 			= $son->mType;
			$this->mToken 			= $son->mToken;
			$this->mChildNodes 		= $son->mChildNodes;
			$this->mLookAheadSet 	= $son->mLookAheadSet;
			$this->mASTSymbol 	= $son->mASTSymbol;
			$this->mEnclosingRule 	= $son->mEnclosingRule;

			foreach( $this->mChildNodes as $key => $value ) {
				$value->mParentNode = $this;
			}
			
		}

		if (  (  ( self::ALTERNATE === $this->mType )
		      && ( 0 < count( $this->mChildNodes ) ) 
		      && ( self::ALTERNATE === $this->mChildNodes[0]->mType ) 
		      && ( 0 < count( $this->mChildNodes[0]->mChildNodes ) ) 
		      )
		   ){
			
			$son = $this->mChildNodes[0]->mChildNodes[0];
			$this->appendChild( $son );
			
			$son->mParentNode = $this;

			array_shift( $this->mChildNodes[0]->mChildNodes );
			
		}



		foreach( $this->mChildNodes as $key => $value ) {
			$value->optimizeIteration();
		}
		
	}
	
	
} // CParseTree

?>