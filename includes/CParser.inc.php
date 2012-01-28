<?php


require_once( 'CLexer.inc.php' );
require_once( 'CParseTree.inc.php' );
require_once( 'CFailedParseException.inc.php' );
require_once( 'CBindingStack.inc.php' );
require_once( 'AST.inc.php' );


class CParser {
	
	private
		$_sourceString,
		$_ebnfLexer,
		$_debugLevel,
		$_parseTree,
		$_bindingStack;
		
	const 
		PARSER_SECTION_PARSER	= '@@rules',
		BIG_STR = '________________________________________';
		
	public function __construct() {
		
		$this->_ebnfLexer = new CLexer();
		$this->_ebnfLexer->loadPatternsFromFile( dirname(__FILE__) . '/ebnf.lex' );

		$this->loadSourceFromFile( dirname(__FILE__) . '/../ebnf.syn' );
		$this->_bindingStack = new CBindingStack();
		
		$this->_debugLevel = 0;
	}
	
	public function incDebug( $someStr ) {
		
		if ( 50 < $this->_debugLevel ) {
			
			exit;
		}
		
		$this->_debugLevel ++;
		
		echo '<br/>&gt;' . substr( self::BIG_STR, 0, $this->_debugLevel ) . $someStr;
	}

	public function decDebug( $someStr ) {
		echo '<br/>&lt;' . substr( self::BIG_STR, 0, $this->_debugLevel ) . $someStr;
		$this->_debugLevel --;
	}
	
	public function loadSourceFromFile( $aFileName ) {
		
		$this->_ebnfLexer->loadSourceFromFile( $aFileName );
		
	} // loadSourceFromFile


	private function loadEBNFLexerPatterns() {
	
		// matches mix-in PHP code
		$this->_ebnfLexer->addPattern( '$$inlineCode', '/^(\\<\\?.*\\?\\>)/msU' );
		// matches singl- or double-quotes strings
		$this->_ebnfLexer->addPattern( '$$stringLiteral', '/^(\\\'[^\\\']*\\\')|(\\"[^\\"]*\\")/' );
		// matches singl- or double-quotes strings
		$this->_ebnfLexer->addPattern( '$$opAlternate', '/^\\|/' );
		// matches singl- or double-quotes strings
		$this->_ebnfLexer->addPattern( '$$opEnd', '/^;/' );
		// matches singl- or double-quotes strings
		$this->_ebnfLexer->addPattern( '$$ident', '/^[a-zA-Z][a-zA-Z0-9]*/' );
		$this->_ebnfLexer->addPattern( '$$tokenClass', '/^(@[a-zA-Z][a-zA-Z0-9]*)/' );

		$this->_ebnfLexer->addPattern( '$$opManyOpen', '/^\\{/' );
		$this->_ebnfLexer->addPattern( '$$opManyClose', '/^\\}/' );

		$this->_ebnfLexer->addPattern( '$$opOptionOpen', '/^\\[/' );
		$this->_ebnfLexer->addPattern( '$$opOptionClose', '/^\\]/' );

		$this->_ebnfLexer->addPattern( '$$opParOpen', '/^\\(/' );
		$this->_ebnfLexer->addPattern( '$$opParClose', '/^\\)/' );

		$this->_ebnfLexer->addPattern( '$$opOneOrMany', '/^\\+/' );
		
		$this->_ebnfLexer->addPattern( '$$doubleDots', '/^::/' );
		
		
		
	}
	
	
	public function loadPatternsFromFile( $aFileName ) {
		
		$this->_ebnfLexer->loadPatternsFromFile( $aFileName );

		$this->loadEBNFLexerPatterns();
		
	} // loadSourceFromFile

	
	
	public function createLexer( $pathToLexerDefintion ) {

		$this->_ebnfLexer = new CLexer();
		$this->_ebnfLexer->loadPatternsFromFile( $pathToLexerDefintion );
		
		$this->loadEBNFLexerPatterns();
		
	}
	
	
	public function buildParseTree() {
		
		$parseTree = new CParseTree();
		$parseTree->setType( CParseTree::RULES );
		
		try {

			$success = $this->_ebnfLexer->consumeToken( "rules", $matchedString );

			while( $this->_ebnfLexer->isNextToken( '$$ident' ) ) {

				$subTree = $this->parseRule();
				
				$parseTree->appendChild( $subTree );
			
			}
			
		} catch ( Exception $ex ) {

			echo '<p />Failed to parse.';
		
		}

		echo '<p />Initial Tree <br /> ' . $parseTree;
		$parseTree->optimize();
		echo '<hr />Optimized <br /> ' . $parseTree;
		
		$parseTree->getRootNode()->buildLookAheadClosure();
		echo '<p />With Lookahead <br /> ' . $parseTree;
		
		
		return $parseTree;
		
	} // buildParseTree
	
	
	private function parseRule( ) {
		
		$matchedASTSymbol = null;
		
		$success = $this->_ebnfLexer->consumeToken( '$$ident', $matchedString );
		if ( $this->_ebnfLexer->isNextToken( '$$doubleDots' ) ) {
			$success = $this->_ebnfLexer->consumeToken( '$$doubleDots', $noUseString );
			$success = $this->_ebnfLexer->consumeToken( '$$ident', $matchedASTSymbol );
		}
		
		$subTree = new CParseTree();
		$subTree->setType( CParseTree::RULE );
		$subTree->setToken( $matchedString  );
		$subTree->setEnclosingRule( $subTree );
		if ( null !== $matchedASTSymbol  ) {
			$subTree->setASTSymbol( $matchedASTSymbol  );		
		}
		
		$this->incDebug( '#RULE ' . $matchedString );

		$success = $this->_ebnfLexer->consumeToken( "assign", $matchedString );
		
		$subTree->appendChild( $this->parseExpression( $subTree ) );
		
		$this->_ebnfLexer->consumeToken( '$$opEnd', $matchedString );

		$this->decDebug( '#RULE '  );
		echo '<p />';
		
		return $subTree;
		
	}
	
	private function parseExpression( CParseTree $enclosingRule ) {

		$this->incDebug( '#Expression ' );

		$rhsTree = new CParseTree();
		$rhsTree->setType( CParseTree::ALTERNATE );
		$rhsTree->setEnclosingRule( $enclosingRule );
		
		try {

			$rhsTree->appendChild( $this->parseTerm( $enclosingRule ) );
			
			while( $this->_ebnfLexer->isNextToken( '$$opAlternate' ) ) {

				$this->_ebnfLexer->consumeToken( '$$opAlternate', $matchedString );
				$rhsTree->appendChild( $this->parseTerm( $enclosingRule ) );
			}
			
		} catch( CELexerNotMatched $ex  ) {
			
			echo '<hr />Exception: ' . $this->_ebnfLexer->getShortenedSourceString(  )  ;
			
		}

		$this->decDebug( '#Expression '  );
		
		return $rhsTree;
		
	}

	
	private function parseTerm( CParseTree $enclosingRule ) {

		
		if ( $this->_ebnfLexer->isNextToken( '$$opEnd' ) ) {
			return null;
		}

		$this->incDebug( '#Term ' );
		
		
		$subTree = new CParseTree();
		$subTree->setType( CParseTree::SEQUENCE );
		$subTree->setEnclosingRule( $enclosingRule );

		$subTree->appendChild( $this->parseFactor( $enclosingRule ) ); 

		while(  ( $this->_ebnfLexer->isNextToken( '$$ident' ) )
		     || ( $this->_ebnfLexer->isNextToken( '$$tokenClass' ) )
		     || ( $this->_ebnfLexer->isNextToken( '$$stringLiteral' ) )
		     || ( $this->_ebnfLexer->isNextToken( '$$opManyOpen' ) )
		     || ( $this->_ebnfLexer->isNextToken( '$$opOptionOpen' ) )
		     || ( $this->_ebnfLexer->isNextToken( '$$opParOpen' ) )
		     || ( $this->_ebnfLexer->isNextToken( '$$opOneOrMany' ) )
		     || ( $this->_ebnfLexer->isNextToken( '$$inlineCode' ) )
		     ) {

			$subTree->appendChild( $this->parseFactor( $enclosingRule ) ); 

		}
		

		$this->decDebug( '#Term ' );
		
		return $subTree;
		
	}
	
	private function parseFactor( CParseTree $enclosingRule ) {
		
		$oneTree = null;
		$noUseString = null;
		
		if ( $this->_ebnfLexer->isNextToken( '$$ident' ) ) {
			
			$success = $this->_ebnfLexer->consumeToken( '$$ident', $matchedString );
			$matchedASTSymbol = null;
			if ( $this->_ebnfLexer->isNextToken( '$$doubleDots' ) ) {
				$success = $this->_ebnfLexer->consumeToken( '$$doubleDots', $noUseString );
				$success = $this->_ebnfLexer->consumeToken( '$$ident', $matchedASTSymbol );
			}
			$this->incDebug( '#Factor:NonTerminal <b>' . $matchedString . '</b>'  );
			$oneTree = new CParseTree();
			$oneTree->setType( CParseTree::CALL );
			$oneTree->setToken( $matchedString );
			$oneTree->setEnclosingRule( $enclosingRule );
			if ( null != $matchedASTSymbol ) {
				$oneTree->setASTSymbol( $matchedASTSymbol );
			}
			$this->decDebug( '#Factor:NonTerminal ' );
			
		} else if ( $this->_ebnfLexer->isNextToken( '$$tokenClass' ) )  {

			$success = $this->_ebnfLexer->consumeToken( '$$tokenClass', $matchedString );
			$matchedASTSymbol = null;
			if ( $this->_ebnfLexer->isNextToken( '$$doubleDots' ) ) {
				$success = $this->_ebnfLexer->consumeToken( '$$doubleDots', $noUseString );
				$success = $this->_ebnfLexer->consumeToken( '$$ident', $matchedASTSymbol );
			}
			$this->incDebug( '#Factor:Terminal <b>' . $matchedString . '::' . $matchedASTSymbol . '</b>' );
			$oneTree = new CParseTree();
			$oneTree->setType( CParseTree::TERMINAL );
			$oneTree->setToken( $matchedString );
			$oneTree->setEnclosingRule( $enclosingRule );
			if ( null != $matchedASTSymbol ) {
				$oneTree->setASTSymbol( $matchedASTSymbol );
			}
			$this->decDebug( '#Factor:Terminal ' );

		} else if ( $this->_ebnfLexer->isNextToken( '$$stringLiteral' ) )  {

			$success = $this->_ebnfLexer->consumeToken( '$$stringLiteral', $matchedString );
			$this->incDebug( '#Factor:Terminal <b>' . $matchedString . '</b>' );
			$oneTree = new CParseTree();
			$oneTree->setType( CParseTree::TERMINAL );
			$oneTree->setToken( $matchedString );
			$oneTree->setEnclosingRule( $enclosingRule );
			$this->decDebug( '#Factor:Terminal ' );

		}  else if ( $this->_ebnfLexer->isNextToken( '$$opParOpen' ) )  {

			$success = $this->_ebnfLexer->consumeToken( '$$opParOpen', $matchedString );

			$this->incDebug( '#Term:opParOpen ' );
			$oneTree = $this->parseExpression( $enclosingRule );

			$success = $this->_ebnfLexer->consumeToken( '$$opParClose', $matchedString );
			$this->decDebug( '#Term:opParOpen ' );

		}  else if ( $this->_ebnfLexer->isNextToken( '$$opManyOpen' ) )  {

			$success = $this->_ebnfLexer->consumeToken( '$$opManyOpen', $matchedString );
			
			$this->incDebug( '#Term:NONE_OR_MANY ' );
			$oneTree = new CParseTree();
			$oneTree->setType( CParseTree::NONE_OR_MANY );
			$oneTree->setEnclosingRule( $enclosingRule );
			$oneTree->appendChild( $this->parseExpression( $enclosingRule ) );
			
			$success = $this->_ebnfLexer->consumeToken( '$$opManyClose', $matchedString );
			$this->decDebug( '#Term:NONE_OR_MANY ' );
			

		}  else if ( $this->_ebnfLexer->isNextToken( '$$opOptionOpen' ) )  {

			$success = $this->_ebnfLexer->consumeToken( '$$opOptionOpen', $matchedString );

			$this->incDebug( '#Term:NONE_OR_ONE ' );
			$oneTree = new CParseTree();
			$oneTree->setType( CParseTree::NONE_OR_ONE );
			$oneTree->setEnclosingRule( $enclosingRule );
			$oneTree->appendChild( $this->parseExpression( $enclosingRule ) );

			$success = $this->_ebnfLexer->consumeToken( '$$opOptionClose', $matchedString );
			$this->decDebug( '#Term:NONE_OR_ONE ' );

		}  else if ( $this->_ebnfLexer->isNextToken( '$$opOneOrMany' ) )  {

			$success = $this->_ebnfLexer->consumeToken( '$$opOneOrMany', $matchedString );

			$this->incDebug( '#Term:ONE_OR_MANY ' );
			$oneTree = new CParseTree();
			$oneTree->setType( CParseTree::ONE_OR_MANY );
			$oneTree->setEnclosingRule( $enclosingRule );
			$oneTree->appendChild( $this->parseExpression( $enclosingRule ) );

			$this->decDebug( '#Term:ONE_OR_MANY ' );

		}  else if ( $this->_ebnfLexer->isNextToken( '$$inlineCode' ) )  {

			$success = $this->_ebnfLexer->consumeToken( '$$inlineCode', $matchedString );

			$matchedString = substr( $matchedString, 2, strlen($matchedString)-4 );
			
			$this->incDebug( '#Term:CODE ' );
			$oneTree = new CParseTree();
			$oneTree->setType( CParseTree::CODE );
			$oneTree->setToken( $matchedString );
			$oneTree->setEnclosingRule( $enclosingRule );
			$this->decDebug( '#Term:CODE ' );
			
//			echo '<p />CODE ' . htmlspecialchars( $oneTree->getToken() );

		} else {
			
			throw new CELexerNotMatched( 'Factor' );
		}
		
		return $oneTree;
		
	}
	
	
	public function isNextTokenInLookAheadSet( CParseTree $aParseTree ) {
		
		foreach( $aParseTree->getLookAheadSet() as $noUseKey => $lookaheadSymbol ) {
			
			if (  ( "'" === substr( $lookaheadSymbol, 0, 1 ) ) 
			   || ( '"' === substr( $lookaheadSymbol, 0, 1 ) ) 
			   ) {
//				$tokenClass =  substr( $lookaheadSymbol, 1, strlen( $lookaheadSymbol )-2 );
				$tokenClass =  $lookaheadSymbol;
			} else {
				$tokenClass =  substr( $lookaheadSymbol, 1, strlen( $lookaheadSymbol )-1 );
			}
			
			if ( $this->_ebnfLexer->isNextToken( $tokenClass ) ) {
				
				return TRUE;
				
			} // if
			
		} // for
		
		return FALSE;
	}
	
	
	
	public function parseUsingParseTree( CParseTree $theTree  ) {
		
		echo '<hr />' . $theTree ;
		
		$this->_parseTree = $theTree;
		
		// find rhs of first rule
		$firstRule = $theTree->getChildNode( 0 )->getChildNode( 0 );
		
		try {
			
			$this->_bindingStack = new CBindingStack();
			$this->_bindingStack->newFrame();
			
			$this->parsePT( $firstRule );
			
		} catch( Exception $ex ) {

			$remainingSource = substr( trim( $this->_ebnfLexer->getSourceString() ), 0, 70 ) . '...';
			echo   '<p/><b>Failed to parse Source...</b>'
			     . '<br />...Reason: ' . $ex->getMessage()
		         . '<br />...Remaining: ' . $remainingSource;
		
//			echo '<pre>' . $ex . '</pre>';
			return;
			
		}
	
		$lhsVariableName = $theTree->getChildNode( 0 )->getLHSVariable(); // LHS name of first rule
		if (  ( null != $lhsVariableName )
		   && ( null != $this->_bindingStack->getValueForName( $lhsVariableName ) )
		   ) {

			$returnedValue = $this->_bindingStack->getValueForName( $lhsVariableName );
			echo '<p />AST ' . htmlentities( $returnedValue );
			
		} else {

			echo '<p />Empty AST';
			
		}

		$this->_bindingStack->popFrame();
	
		
	}



	private function parsePT( CParseTree $theTree ) {
		
		
		$oneTree = null;
		$matchedString = '';
		
		$allVariableNames =  $theTree->getAllVariables();
		foreach( $allVariableNames as $aVarName => $noUseValue ) {
			$$aVarName = $this->_bindingStack->getValueForName( $aVarName );
		} 
		
		
		switch( $theTree->getType() ) {
			
			case CParseTree::TERMINAL:
				if (  ( "'" === substr( $theTree->getToken(), 0, 1 ) )
				   || ( '"' === substr( $theTree->getToken(), 0, 1 ) )
				   ) {
					$tokenClass =  $theTree->getToken();
				} else {
					$tokenClass =  substr( $theTree->getToken(), 1, strlen( $theTree->getToken() )-1 );
				}
				
				if (  ( $this->_ebnfLexer->isNextToken( $tokenClass ) ) ) {
					$success = $this->_ebnfLexer->consumeToken( $tokenClass, $matchedString );
				} else {
					throw new CFailedParseException( 'Terminal of class "' . $tokenClass . '" not found [regEx :: ' .  $this->_ebnfLexer->getPatternForToken($tokenClass) . '].');
				}
				
				$oneTree = new CParseTree();
				$oneTree->setType( CParseTree::TERMINAL );
				$oneTree->setToken( $matchedString );
				if ( null !== $theTree->getASTSymbol() ) {
					$this->_bindingStack->bindNameToValue( $theTree->getASTSymbol(), $matchedString  );
				}
				
//				echo '<p />CONSUMED ' . $this->_ebnfLexer->getSourceString();
				break;

			case CParseTree::CALL:
				
				$tokenClass =  $theTree->getToken();
				$rule = $this->_parseTree->getRuleForSymbol( $tokenClass );
				
				if ( null !== $rule ) {

					$this->_bindingStack->newFrame();
					$this->parsePT( $rule->getChildNode(0) ); // parse right side of rule
					
					$lhsVariableName = $rule->getLHSVariable();
					if (  ( null != $lhsVariableName )
					   && ( null != $this->_bindingStack->getValueForName( $lhsVariableName ) )
					   && ( null != $theTree->getASTSymbol() )
					   ) {
						$returnedValue = $this->_bindingStack->getValueForName( $lhsVariableName );
						$this->_bindingStack->bindNameToValueUpperFrame( $theTree->getASTSymbol(), $returnedValue  );
					} 
					$this->_bindingStack->popFrame();
				} else {
					throw new CFailedParseException( 'Nonterminal ' . $tokenClass . ' non defined.' );
				}

				break;

			case CParseTree::CODE:
//				echo '<p />CODE "' . htmlspecialchars( $theTree->getToken() ) . '"';
				$lhsVariableName = $theTree->getLHSVariable();
				if (  ( null !== $lhsVariableName )
				   ) {
					$$lhsVariableName = $this->_bindingStack->getValueForName( $lhsVariableName );
				}

				try {
					
					eval( $theTree->getToken() );
					
				} catch( Exception $ex ) {
					
					echo '<br />EXC ' . $ex->message() ;
					echo '<br />CODE ' . $theTree->getToken() ;
					
				}

				// In case the LHS has been bound by the CParseTree::CODE, we need to
				// update the bind stack, so that the caller can take the value

				$lhsVariableName = $theTree->getLHSVariable();
				if (  ( null !== $lhsVariableName )
				   && ( isset( $$lhsVariableName ) )
				   ) {
					// echo '<br />BIND ' . $lhsVariableName . ' :: '.  $$lhsVariableName ;
					$this->_bindingStack->bindNameToValue( $lhsVariableName, $$lhsVariableName );
				}

				$ast = null;
				break;

			case CParseTree::ALTERNATE:
				$alternateMatched = FALSE;
				foreach( $theTree->getChildNodes() as $noUseKey => $son ) {
					if (  ( ! $alternateMatched )
					   && ( $this->isNextTokenInLookAheadSet( $son ) )
					   ) {
						$this->parsePT( $son );
						$alternateMatched = TRUE;
					}
				}
				
				if ( ! $alternateMatched ) {
					throw new CFailedParseException( 'Alternate not found: ' . $theTree );
				}
				break;

			case CParseTree::NONE_OR_MANY:
				while (  $this->isNextTokenInLookAheadSet( $theTree->getChildNode(0) ) ) {
					$this->parsePT( $theTree->getChildNode(0) );
				}
				break;

			case CParseTree::ONE_OR_MANY:
				$this->parsePT( $theTree->getChildNode(0) );
				while (  $this->isNextTokenInLookAheadSet( $theTree->getChildNode(0) ) ) {
					$this->parsePT( $theTree->getChildNode(0) );
				}
				break;

			case CParseTree::NONE_OR_ONE:
				if (  $this->isNextTokenInLookAheadSet( $theTree->getChildNode(0) ) ) {
					$this->parsePT( $theTree->getChildNode(0) );
				}
				break;

			case CParseTree::SEQUENCE:
				foreach( $theTree->getChildNodes() as $key => $son ) {
					$this->parsePT( $son );
				}
				break;
				
			default:
				throw new CFailedParseException( 'Internal inconsistency: Undefined Parse tree type ' . $theTree->getType() );
				break;
			
		}
		
		
	}

	
	
}


?>