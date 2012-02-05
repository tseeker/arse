<?php


final class Form
{

	private $buttonTitle;
	private $name;
	private $title;

	private $action;
	private $method;

	private $cancelURL;
	private $successURL;

	private $fields = array( );

	private $controllers = array( );


	public function __construct( $buttonTitle , $name , $title = null )
	{
		$this->buttonTitle = $buttonTitle;
		$this->title = $title;
		$this->name = is_null( $name ) ? 'the-form' : $name;
		$this->action = '?';
		$this->method = 'POST';
	}


	public function buttonTitle( )
	{
		return $this->buttonTitle;
	}

	public function name( )
	{
		return $this->name;
	}

	public function title( )
	{
		return $this->title;
	}

	public function action( )
	{
		return $this->action;
	}

	public function method( )
	{
		return $this->method;
	}

	public function cancelURL( )
	{
		return $this->cancelURL;
	}

	public function successURL( )
	{
		return $this->successURL;
	}

	public function fields( )
	{
		return array_values( $this->fields );
	}

	public function field( $name )
	{
		assert( array_key_exists( $name , $this->fields ) );
		return $this->fields[ $name ];
	}

	public function controllers( )
	{
		return $this->controllers;
	}


	public function setAction( $action )
	{
		$this->action = $action;
		return $this;
	}

	public function setMethod( $method )
	{
		$this->method = $method;
		return $this;
	}

	public function setURL( $url )
	{
		$this->cancelURL = $this->successURL = $url;
		return $this;
	}

	public function setCancelURL( $url )
	{
		$this->cancelURL = $url;
		return $this;
	}

	public function setSuccessURL( $url )
	{
		$this->successURL = $url;
		return $this;
	}


	public function addField( Field $field )
	{
		assert( ! array_key_exists(  $field->name( ) , $this->fields ) );
		$this->fields[ $field->name( ) ] = $field;
		return $this;
	}


	public function addSeparator( )
	{
		$i = 0;
		while ( array_key_exists( "sep$i" , $this->fields ) ) {
			$i ++;
		}
		$this->fields[ "sep$i" ] = null;
		return $this;
	}


	public function addController( Controller $controller )
	{
		if ( is_a( $controller , 'FormAware' ) ) {
			$controller->setForm( $this );
		}
		array_push( $this->controllers , $controller );
		return $this;
	}


	public function controller( )
	{
		return Loader::Ctrl( 'form' , $this );
	}


	public function view( )
	{
		$box = Loader::View( 'box' , $this->title , Loader::View( 'form' , $this ) )
			->setClass( 'form' );

		if ( $this->cancelURL !== null ) {
			$box->addButton( BoxButton::create( Loader::Text( 'Cancel' ) , $this->cancelURL )
				->setClass( 'form-cancel' ) );
		}

		return $box;
	}
}


interface FormAware
{
	public function setForm( Form $form );
}
