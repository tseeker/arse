<?php

class View_Form
	extends BaseURLAwareView
{
	protected $form;

	public function __construct( Form $form )
	{
		$this->form = $form;
	}


	protected function renderHiddenFields( $prefix )
	{
		$result = array( );
		foreach ( $this->form->fields( ) as $field ) {
			if ( $field === null || $field->type( ) !== 'hidden' ) {
				continue;
			}

			array_push( $result , HTML::make( 'input' )
				->setAttribute( 'type' , 'hidden' )
				->setAttribute( 'name' , $field->name( ) )
				->setAttribute( 'value' , HTML::from( $field->value( ) ) )
				->setAttribute( 'id' , $prefix . $field->name( ) ) );
		}
		return $result;
	}


	protected function renderPasswordField( $field , $prefix )
	{
		return HTML::make( 'input' )
			->setAttribute( 'type' , 'password' )
			->setAttribute( 'name' , $field->name( ) )
			->setAttribute( 'id' , $prefix . 'field' )
			->setAttribute( 'class' , 'form-text-field' );
	}


	protected function renderTextField( $field , $prefix )
	{
		return HTML::make( 'input' )
			->setAttribute( 'type' , 'text' )
			->setAttribute( 'name' , $field->name( ) )
			->setAttribute( 'id' , $prefix . 'field' )
			->setAttribute( 'class' , 'form-text-field' )
			->setAttribute( 'value' , HTML::from( $field->value( ) ) );
	}


	protected function renderTextArea( $field , $prefix )
	{
		return HTML::make( 'textarea' )
			->setAttribute( 'name' , $field->name( ) )
			->setAttribute( 'id' , $prefix . 'field' )
			->setAttribute( 'class' , 'form-text-field' )
			->appendText( (string) $field->value( ) );
	}


	protected function renderSelectField( $field , $prefix )
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


	protected function renderField( $field , $prefix )
	{
		switch ( $field->type( ) ) {

		case 'password':
			$result = $this->renderPasswordField( $field , $prefix );
			break;

		case 'text':
			$result = $this->renderTextField( $field , $prefix );
			break;

		case 'textarea':
			$result = $this->renderTextArea( $field , $prefix );
			break;

		case 'select':
			$result = $this->renderSelectField( $field , $prefix );
			break;

		default:
			throw new Exception( "field " . $field->name() . " has unknown type " . $field->type() );

		}
		return $result;
	}


	protected function renderVisibleFields( $target , $prefix )
	{
		foreach ( $this->form->fields( ) as $field ) {
			if ( $field === null ) {
				$target->appendElement( HTML::make( 'hr' ) );
				continue;
			}
			if ( $field->type( ) === 'hidden' ) {
				continue;
			}

			$fPrefix = $prefix . $field->name( ) . '-';
			if ( $field->type( ) === 'html' ) {
				$target->appendElement( HTML::make( 'dd' )
					->setAttribute( 'id' , $fPrefix )
					->setAttribute( 'class' , 'html-section' )
					->append( $field->value( ) ) );
				continue;
			}

			$fClass = 'field' . ( $field->mandatory( ) ? ' mandatory' : '' );
			$target->appendElement( HTML::make( 'dt' )
				->setAttribute( 'class' , $fClass )
				->setAttribute( 'id' , $fPrefix . 'label' )
				->appendElement( HTML::make( 'label' )
					->setAttribute( 'for' , $fPrefix . 'field' )
					->appendText( $field->description( ) ) ) );

			$errors = $field->errors( );
			if ( !empty( $errors ) ) {
				foreach ( $errors as $error ) {
					$target->appendElement( HTML::make( 'dd' )
						->setAttribute( 'class' , 'form-error' )
						->appendText( $error ) );
				}
				$fClass .= ' erroneous';
			}

			$target->appendElement( HTML::make( 'dd' )
				->setAttribute( 'id' , $fPrefix . 'container' )
				->setAttribute( 'class' , $fClass )
				->append( $this->renderField( $field , $fPrefix ) ) );
		}
	}


	public function render( )
	{
		$name = $this->form->name();
		$prefix = $name . '-';

		$action = $this->form->action( );
		if ( $action{0} != '?' ) {
			if ( $action{0} != '/' ) {
				$action = "/$action";
			}
			$action = $this->base . $action;
		}

		$form = HTML::make( 'form' )
			->setAttribute( 'name' , $name )
			->setAttribute( 'id' , $prefix . 'form' )
			->setAttribute( 'action' , $action )
			->setAttribute( 'method' , $this->form->method( ) )
			->append( $this->renderHiddenFields( $prefix ) )
			->append( $visibleArea = HTML::make( 'dl' ) );

		$this->renderVisibleFields( $visibleArea , $prefix );
		$visibleArea->appendElement( HTML::make( 'dt' )
			->setAttribute( 'class' , 'submit-button' )
			->setAttribute( 'id' , $prefix . 'submit-container' )
			->appendElement( HTML::make( 'input' )
				->setAttribute( 'type' , 'submit' )
				->setAttribute( 'name' , $prefix . 'submit' )
				->setAttribute( 'id' , $prefix . 'submit' )
				->setAttribute( 'value' , $this->form->buttonTitle( ) ) ) );

		return $form;
	}
}
