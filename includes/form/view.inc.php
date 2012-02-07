<?php

class View_Form
	extends BaseURLAwareView
{
	protected $form;
	protected $fieldTypes;

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

	protected function renderField( $field , $prefix )
	{
		$type = $field->type( );
		if ( array_key_exists( $type , $this->fieldTypes ) ) {
			return $this->fieldTypes[ $type ]->render( $field , $prefix );
		}
		throw new Exception( "field " . $field->name() . " has unknown type " . $field->type() );
	}

	private function loadFieldTypes( )
	{
		$types = Loader::Find( 'FieldView' );
		$loaded = array( );

		foreach ( $types as $type ) {
			$instance = Loader::Create( $type );
			$loaded[ $instance->getFieldType( ) ] = $instance;
		}

		$this->fieldTypes = $loaded;
	}


	protected function renderVisibleFields( $target , $prefix )
	{
		$this->loadFieldTypes( );
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
