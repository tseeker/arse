<?php

class ParameterException extends Exception { }

abstract class Controller
{

	protected final function getParameter( $name , $method = null )
	{
		if ( $method === null ) {
			try {
				return $this->getParameter( $name , 'POST' );
			} catch ( ParameterException $e ) {
				return $this->getParameter( $name , 'GET' );
			}
		}

		$from = '_' . $method;
		global $$from;
		if ( ! array_key_exists( $name , $$from ) ) {
			throw new ParameterException( "$name/$method" );
		}
		return ${$from}[ $name ];
	}

	public abstract function handle( Page $page );
}


final class Ctrl_Simple
	extends Controller
{
	private $view;

	public function __construct( View $view )
	{
		$this->view = $view;
	}

	public function handle( Page $page )
	{
		return $this->view;
	}
}
