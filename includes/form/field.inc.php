<?php

interface FieldValidator
{
	public function validate( $value );
}


interface FieldModifier
{
	public function replace( $value );
}


final class Field
{
	const MissingError = 'Mandatory';

	private $name;

	private $type;
	private $options = array( );

	private $description;
	private $mandatory = true;

	private $valueDefault;
	private $valueForm;

	private $validator;
	private $modifier;

	private $errors = array( );


	public function __construct( $name , $type )
	{
		$this->type = $type;
		$this->name = $name;
	}


	public function addOption( $value , $text , $disabled = false )
	{
		assert( $this->type === 'select' );
		assert( ! array_key_exists( $value, $this->options ) );
		$obj = new stdClass( );
		$obj->text = $text;
		$obj->disabled = $disabled;
		$this->options[ $value ] = $obj;
		return $this;
	}

	public function options( )
	{
		return $this->options;
	}


	public function name( )
	{
		return $this->name;
	}

	public function type( )
	{
		return $this->type;
	}


	public function setDescription( $description )
	{
		$this->description = $description;
		return $this;
	}

	public function description( )
	{
		return $this->description;
	}


	public function setMandatory( $mandatory )
	{
		$this->mandatory = $mandatory;
		return $this;
	}

	public function mandatory( )
	{
		return $this->mandatory;
	}


	public function setModifier( FieldModifier $modifier )
	{
		$this->modifier = $modifier;
		return $this;
	}


	public function setValidator( FieldValidator $validator )
	{
		$this->validator = $validator;
		return $this;
	}


	public function setDefaultValue( $default )
	{
		$this->valueDefault = $default;
		return $this;
	}

	public function setFormValue( $form )
	{
		if ( $this->modifier !== null ) {
			$form = $this->modifier->replace( $form );
		}
		$this->valueForm = $form;
		return $this;
	}

	public function value( )
	{
		return is_null( $this->valueForm )
			? $this->valueDefault
			: $this->valueForm;
	}


	public function putError( $error )
	{
		$this->errors[ $error ] = 1;
		return $this;
	}

	public function errors( )
	{
		return array_keys( $this->errors );
	}


	public function validate( )
	{
		$value = $this->value( );
		if ( $this->mandatory && ( $value === null || $value == '' ) ) {
			$this->putError( Loader::Text( Field::MissingError ) );
			return false;
		}

		if ( $this->validator !== null ) {
			$errors = $this->validator->validate( $value );
			if ( is_array( $errors ) ) {
				foreach ( $errors as $error ) {
					$this->putError( $error );
				}
				return false;
			}
		}

		return true;
	}
}
