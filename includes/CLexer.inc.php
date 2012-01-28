<?php

require_once( 'CELexerEOF.inc.php' ); 
require_once( 'CLexerNotMatched.inc.php' ); 


class CLexer {
	
	private
		$_patterns,
		$_sourceString,
		$_trace;
		
	const
		LEXER_NO_MATCH 	= 0,
		LEXER_MATCH		= 1;
		
		
	const
		LEXER_SECTION_LEXICAL 	= '@@lexical',
		LEXER_SECTION_PARSER 	= '@@rules';
		
	function __construct() {
		
		$this->_patterns 		= array();
		$this->_sourceString 	= null;

		$this->_trace 			= TRUE;
		
	}
	
	public function getPatternForToken( $aTokenClass ) {

		
		if ( '\'' === substr( $aTokenClass, 0, 1 ) ) {
			
			$strippedToken = substr( $aTokenClass, 1, strlen($aTokenClass)-2 );
			$pattern = '/^(' . CLexer::getRegExEscaped( $strippedToken ) . ')/';
			
		} else {
			if ( ! isset( $this->_patterns[ $aTokenClass ] ) ) {
				$pattern = null;
			} else {
				$pattern = $this->_patterns[ $aTokenClass ];
			}
		}
		
		return $pattern;
	}
	
	
	
	static public function getLexerRegExForString( $aString ) {
		
		return '/^' . CLexer::getRegExEscaped( $aString ) . '/';
		
	}

	static public function getRegExEscaped( $aTokenClass ) {
		
		$replaces = array(
			'\\'	=> '\\\\',
			'('	=> '\\(',
			')'	=> '\\)',
			'{'	=> '\\{',
			'}'	=> '\\}',
			'['	=> '\\[',
			']'	=> '\\]',
			'?'	=> '\\?',
			'<'	=> '\\<',
			'>'	=> '\\>',
			'|'	=> '\\|',
			'/'	=> '\\/',
			'*'	=> '\\*',
			'.'	=> '\\.',
			'+'	=> '\\+'
			);
			
		foreach( $replaces as $key => $replacement ) {
			$aTokenClass = str_replace( $key, $replacement, $aTokenClass );
		}
		
		return $aTokenClass;
	}

	
	public function setSourceString( $aString ) {
		
		$this->_sourceString = $aString;
		
	} // setSourceString

	public function getSourceString(  ) {
		
		return $this->_sourceString;
		
	} // setSourceString

	public function getShortenedSourceString(  ) {
		
		return substr( $this->_sourceString, 0, 70 ) . '...';
		
	} // setSourceString
	
	
	
	public function loadSourceFromFile( $aFileName ) {
		
		$source = file( $aFileName );
		$this->_sourceString = implode( ' ', $source );
		
	} // loadSourceFromFile
	
	
	
	public function addPattern( $aTokenClass, $aPattern ) {
		
		$this->_patterns[ $aTokenClass ] = $aPattern;

	} // addPattern
	
	
	
	public function loadPatternsFromFile( $aFileName ) {
		
		$readingLexRule = FALSE;
		
		$source = file( $aFileName );
		
		
		foreach ( $source as $key => $aLine ) {

			if ( ''    === $aLine ) {
				continue;
			}
			
			if ( self::LEXER_SECTION_LEXICAL === substr( $aLine, 0, strlen(self::LEXER_SECTION_LEXICAL) )) {
				
				$readingLexRule = TRUE;
				
			} else if ( self::LEXER_SECTION_PARSER === substr( $aLine, 0, strlen(self::LEXER_SECTION_PARSER) )) {

					$readingLexRule = FALSE;

			} else if ( TRUE === $readingLexRule ) {

				$oldStr = '';
				while( $oldStr != $aLine ) {
					$oldStr = $aLine;
					$aLine = str_replace( "\t\t", "\t", $aLine );
				}
				
				$parts = explode( "\t", $aLine );
				if ( 2 === count( $parts ) ) {
					
					if ( FALSE === @preg_match( $parts[1], 'sdfs', $matches ) ) {
						
						echo '<br />Bad Pattern: ' . trim($parts[0]) . ' - ' . trim($parts[1]);
						
					} else {

						$this->addPattern( trim($parts[0]), trim($parts[1]) );
						
					}
					
				} 
				
			}
			
		}
		
	} // loadPatternFromFile
	
	
	
	public function consumeToken( $aTokenClass, &$matchedString ) {
		
		if ( 0 === strlen( $this->_sourceString ) ) {
			throw new CELexerEOF();
		}
		if ( null === $this->getPatternForToken( $aTokenClass ) )  {
			throw new CELexerNotMatched( 'No pattern found for tokenClass(' . $aTokenClass . ')' );
		}
		
		$this->_sourceString = trim( $this->_sourceString );
		
		$pat = preg_match( $this->getPatternForToken( $aTokenClass ), $this->_sourceString, $matches );
		
		if ( 0 !== $pat ) {
			
			if ( 1 < count( $matches ) ) {
				$this->_sourceString = trim( substr( $this->_sourceString, strlen( $matches[1] ) ) );
				$matchedString = $matches[1]; 
			} else {
				$this->_sourceString = trim( substr( $this->_sourceString, strlen( $matches[0] ) ) );
				$matchedString = $matches[0];
			}

			if ( 1 ) {
				// echo '<br />MATCH :: ' . htmlentities( $matchedString ) . ' [' . htmlentities( $aTokenClass ) . ']';
			} 

			return self::LEXER_MATCH;
			
		} else {
			
			// echo '<br />NONMATCH :: ' . htmlentities( $matchedString ) . ' [' . htmlentities( $aTokenClass ) . ']';
			return self::LEXER_NO_MATCH;
			
		}
		
	} // consumeToken
	
	
	
	public function consumeAnyToken( &$pTokenClass, &$pMatchedString ) {

		if ( 0 === strlen( $this->_sourceString ) ) {
			throw new CELexerEOF();
		}
		
		foreach ( $this->_patterns as $tokenClass => $tokenPattern ) {
			
			if ( self::LEXER_MATCH === $this->consumeToken( $tokenClass, $matchedString ) ) {
				
				$pMatchedString = $matchedString;
				$pTokenClass = $tokenClass;
				return self::LEXER_MATCH;
			}
		}
		
		throw new CELexerNotMatched();
		return self::LEXER_NO_MATCH;
		
	} // consumeAnyToken
	
	
	
	public function isNextToken( $aTokenClass ) {
		
		
		$patternOfTokenClass = $this->getPatternForToken( $aTokenClass );
		
		if ( null ===  $patternOfTokenClass ) {
			echo '<p />NULL?? ' . $aTokenClass  . ' ## ' . $patternOfTokenClass;
			return FALSE;
		}
		
		$matches = null;

		$this->_sourceString = trim( $this->_sourceString );
		
		$matched = ( 0 !== preg_match( $patternOfTokenClass, $this->_sourceString, $matches ) );
		
//		echo '<p />isNextToken_' .  ( $matched ? 'T' : 'F' ) . '___' . $aTokenClass . '___' .  $patternOfTokenClass . '___' . substr( $this->_sourceString, 0, 70 ) . '...';
		
		return $matched;
		
	} // isNextToken
	
	
	
	
	
} // CLexer


?>