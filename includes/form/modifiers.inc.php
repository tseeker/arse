<?php

class Modifier_TrimString
	implements FieldModifier
{
	private $removeDuplicateSpaces;


	public function __construct( $removeDuplicateSpaces = true )
	{
		$this->removeDuplicateSpaces = $removeDuplicateSpaces;
	}


	public function replace( $value )
	{
		if ( $value === null ) {
			return '';
		}
		$value = trim( $value );
		if ( $this->removeDuplicateSpaces ) {
			$value = preg_replace( '/\s\s+/' , ' ' , $value );
		}
		return $value;
	}
}
