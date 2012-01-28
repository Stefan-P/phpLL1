<?php


class AST {
	
	private 
		$mChildNodes;
	private
		$mToken;
	private
		$mTokenClass;
		
	const
		CONC 		= '.',
		BIG_STR 	= '________________________________________';
		
	function __construct( $aTokenClass = '-', $aToken = '-' ) {
		
		$this->mToken 		= 'NIL';
	 	$this->mChildNodes  = array();

		$this->mTokenClass 	= $aTokenClass;
		$this->mToken 		= $aToken;

		// add child-nodes
		$numargs = func_num_args();
		for( $n=2; $n<$numargs; $n++ ) {
			if ( null != func_get_arg( $n )  ) {
				$this->appendChildNode( func_get_arg( $n ) );
			}
		}

		echo '<br />AST ' . $this;
	}
	
	public function getSelfOrUniqueSon() {
		if ( 1 == count( $this->mChildNodes ) ) {
			return $this->mChildNodes[0];
		} else {
			return $this;
		}
	}
	
	function setTokenClass( $aTokenClass ) {
		$this->mTokenClass = $aTokenClass;
	}

	function setToken( $aToken ) {
		$this->mToken = $aToken;
	}

	function appendChildNode( AST $aNewChild  ) {
		
		if ( null !== $aNewChild  ) {
			$this->mChildNodes[] = $aNewChild;
		}
	}
	
	function __toString() {
		return  $this->getString( 0 ) ;
	}
	

	function getString( $indentLevel ) {
		
		
		if ( '\'' === substr( $this->mTokenClass, 0, 1 ) ) {
			$head = $this->mTokenClass ;
		} else if ( '' != $this->mToken ) {
			$head = $this->mTokenClass . '(' . $this->mToken . ')';
		} else if ( '.' != $this->mTokenClass ) {
				$head = $this->mTokenClass ;
		} else {
			$head = '' ;
		}
		
		$subValues = array();
		foreach ( $this->mChildNodes as $key => $value ) {
			
			if ( null != $value ) {
				$subValues[] =  $value->getString( $indentLevel+1 );
			}
		}
		
		
		if ( 1 <= count( $subValues ) ) {
			$subValues = '[' . implode( '  ', $subValues ) . "] ";
		} else {
			$subValues = '';
		}
		
		
		if ( '' != $subValues ) {
			if ( '' !== $head ) {
				$rValue = " " . $head . $subValues;
			} else {
				$rValue = " " . $subValues;
			}
		} else {
			$rValue = ' ' . $head . ' ';
		}

		return $rValue;
		
	}
	
	
	function indentStringOfSize( $size ) {
		$str = '';
		for( $n=0; $n<$size; $n++ ) {
			$str .= '&nbsp;';
		}
		
		return $str;
	}
	
} // CAbstractSyntaxTree

?>