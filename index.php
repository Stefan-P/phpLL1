<?php

require_once( 'includes/CLexer.inc.php' );
require_once( 'includes/CParser.inc.php' );



 $aParser = new CParser();

 // First, create a parse Tree for the snytax
// $aParser->createLexer( dirname(__FILE__) . '/includes/ebnf.lex' );
// $aParser->loadSourceFromFile( dirname(__FILE__) . '/myl.def' );

 $aParser->createLexer( dirname(__FILE__) . '/includes/ebnf.lex' );
 $aParser->loadSourceFromFile( dirname(__FILE__) . '/php.syn' );
 $aTree = $aParser->buildParseTree();

  // Now load a target program
  $aParser->loadPatternsFromFile( dirname(__FILE__) . '/php.lex' );
  $aParser->loadSourceFromFile( dirname(__FILE__) . '/sampleSource.php' );

  // and parse it using the parse tree
  $aParser->parseUsingParseTree( $aTree );
exit;

$aLexer = new CLexer();
$aLexer->loadSourceFromFile( dirname(__FILE__) . '/sample.myl' );
$aLexer->loadPatternsFromFile( dirname(__FILE__) . '/myl.def' );


while( CLExer::LEXER_MATCH === $aLexer->consumeAnyToken( $aTokenClass, $matchedString ) ) {

	echo '<br />' . $aTokenClass . ' - ' . $matchedString;
	
}




?>