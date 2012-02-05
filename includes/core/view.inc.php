<?php

interface View
{
	public function render( );
}


final class HTML
{
	protected $tag;
	protected $attributes = array( );
	protected $contents = array( );
	protected $cached;


	public function __construct( $tag )
	{
		$this->tag = $tag;
	}


	public function setAttribute( $attribute , $value )
	{
		if ( $value !== null ) {
			$this->attributes[ $attribute ] = $value;
			$this->cached = null;
		}
		return $this;
	}


	public function appendText( $text )
	{
		assert( is_scalar( $text ) );
		array_push( $this->contents , HTML::from( $text ) );
		$this->cached = null;
		return $this;
	}


	public function appendElement( HTML $element )
	{
		array_push( $this->contents , $element );
		$this->cached = null;
		return $this;
	}


	public function appendRaw( $text )
	{
		assert( is_scalar( $text ) );
		array_push( $this->contents , $text );
		$this->cached = null;
		return $this;
	}


	public function append( $auto )
	{
		if ( is_array( $auto ) ) {
			foreach ( $auto as $element ) {
				$this->append( $element );
			}
		} elseif ( is_scalar( $auto ) ) {
			$this->appendRaw( $auto );
		} else {
			$this->appendElement( $auto );
		}
		return $this;
	}


	public function getCode( )
	{
		if ( $this->cached !== null ) {
			return $this->cached;
		}
		$code = '<' . $this->tag;

		if ( ! empty( $this->attributes ) ) {
			$attrs = array( );
			foreach ( $this->attributes as $name => $value ) {
				array_push( $attrs , $name . '="' . $value . '"' );
			}
			$code .= ' ' . join( ' ' , $attrs );
		}

		if ( empty( $this->contents ) ) {
			$code .= ' />';
		} else {
			$code .= '>';
			foreach ( $this->contents as $item ) {
				if ( is_scalar( $item ) ) {
					$code .= $item;
				} else {
					$code .= $item->getCode( );
				}
			}
			$code .= '</' . $this->tag . '>';
		}

		return ( $this->cached = $code );
	}


	public static function make( $tag )
	{
		return new HTML( $tag );
	}


	public static function from( $text )
	{
		return htmlentities( $text , ENT_COMPAT , 'UTF-8' );
	}
}
