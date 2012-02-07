<?php

interface FieldView
{

	public function getFieldType( );

	public function render( Field $field , $prefix );

}


class FieldView_Label
	implements FieldView
{

	public function getFieldType( )
	{
		return 'label';
	}

	public function render( Field $field , $prefix )
	{
		return HTML::make( 'span' )
			->appendText( $field->value( ) )
			->setAttribute( 'id' , $prefix . 'field' );
	}
}


class FieldView_Text
	implements FieldView
{

	public function getFieldType( )
	{
		return 'text';
	}

	public function render( Field $field , $prefix )
	{
		return HTML::make( 'input' )
			->setAttribute( 'type' , 'text' )
			->setAttribute( 'name' , $field->name( ) )
			->setAttribute( 'id' , $prefix . 'field' )
			->setAttribute( 'class' , 'form-text-field' )
			->setAttribute( 'value' , HTML::from( $field->value( ) ) );
	}
}


class FieldView_Password
	implements FieldView
{

	public function getFieldType( )
	{
		return 'password';
	}

	public function render( Field $field , $prefix )
	{
		return HTML::make( 'input' )
			->setAttribute( 'type' , 'password' )
			->setAttribute( 'name' , $field->name( ) )
			->setAttribute( 'id' , $prefix . 'field' )
			->setAttribute( 'class' , 'form-text-field' );
	}

}


class FieldView_TextArea
	implements FieldView
{

	public function getFieldType( )
	{
		return 'textarea';
	}

	public function render( Field $field , $prefix )
	{
		return HTML::make( 'textarea' )
			->setAttribute( 'name' , $field->name( ) )
			->setAttribute( 'id' , $prefix . 'field' )
			->setAttribute( 'class' , 'form-text-field' )
			->appendText( (string) $field->value( ) );
	}

}


class FieldView_Select
	implements FieldView
{

	public function getFieldType( )
	{
		return 'select';
	}

	public function render( Field $field , $prefix )
	{
		$select = HTML::make( 'select' )
			->setAttribute( 'name' , $field->name( ) )
			->setAttribute( 'id' , $prefix . 'field' )
			->setAttribute( 'class' , 'form-select' );

		$selected = $field->value( );
		foreach ( $field->options( ) as $value => $obj ) {
			$option = HTML::make( 'option' )
				->setAttribute( 'value' , $value )
				->setAttribute( 'disabled' , $obj->disabled ? 'disabled' : null )
				->appendText( $obj->text );
			if ( "$value" === "$selected" ) {
				$option->setAttribute( 'selected' , 'selected' );
			}
			$select->appendElement( $option );
		}
		return $select;
	}

}
