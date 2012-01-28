<?


class CBindingStack {
	
	private
		$_frames;
		
	function __construct() {
		
		$this->_frames = array();
		
	}	
	
	function bindNameToValue( $aName, $aValue ) {
	
		$this->_frames[ count($this->_frames)-1 ][ $aName ] = $aValue;
		
	}
	
	function bindNameToValueUpperFrame( $aName, $aValue ) {
	
		if ( 2 > count($this->_frames) ) {
			throw new Exception( 'Only one frame on binding stack. ' );
		}
		
		$this->_frames[ count($this->_frames)-2 ][ $aName ] = $aValue;
		
	}
	
	function getValueForName( $aName ) {

		if ( 0 === count($this->_frames) ) {
			throw new Exception( 'Binding stack empty. ' );
		}
		
		if ( isset( $this->_frames[ count($this->_frames)-1 ][ $aName ] ) )  {
			return $this->_frames[ count($this->_frames)-1 ][ $aName ];
		} else {
			return null;
		}
		
	}
	
	
	function newFrame() {
		
		$this->_frames[] = array();
		
	}

	function popFrame() {
		
		array_pop( $this->_frames );
		
	}
	
	
	function __toString() {
		return $this->getNiceString( FALSE );
	}

	function getNiceString() {
		
		$str = '';
		
		foreach( $this->_frames as $keys => $frame ) {
			
			$bindings = array();
			foreach( $frame as $name => $value ) {
				$bindings[] = '' . $name . ' :: ' . $value;
			}
			$bindings = implode( ' , ', $bindings );
			
			$str .= '[ ' . $bindings . ' ]';
			
		}
		
		return $str;
		
	}
	
	
	
}

?>