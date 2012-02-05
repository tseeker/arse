<?php

class Ctrl_Form
	extends Controller
{
	protected $form;

	public function __construct( Form $form )
	{
		$this->form = $form;
	}


	protected function getValues( )
	{
		$success = true;

		foreach ( $this->form->fields( ) as $field ) {
			if ( $field === null ) {
				continue;
			}

			try {
				$value = $this->getParameter( $field->name( ) , $this->form->method( ) );
			} catch ( ParameterException $e ) {
				$value = null;
			}
			$field->setFormValue( $value );
			$vResult = $field->validate( );
			$success = $success && $vResult;
		}

		return $success;

	}


	protected function applyFormControllers( $page )
	{
		foreach ( $this->form->controllers( ) as $controller ) {
			$result = $controller->handle( $page );
			if ( $result === null ) {
				continue;
			}
			return $result;
		}
		return null;
	}


	public function handle( Page $page )
	{
		try {
			$this->getParameter( $this->form->name( ) . '-submit' );
		} catch ( ParameterException $e ) {
			return $this->form->view( );
		}

		if ( ! $this->getValues( ) ) {
			return $this->form->view( );
		}

		$cResult = $this->applyFormControllers( $page );
		if ( $cResult === null ) {
			return $this->form->view( );
		}
		if ( $cResult ) {
			$url = $this->form->successURL( );
		} else {
			$url = $this->form->cancelURL( );
		}

		if ( $url{0} != '/' ) {
			$url = "/$url";
		}
		return $page->getBaseURL( ) . $url;
	}

}
