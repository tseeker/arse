<?php

abstract class HubPage
	extends HTMLPage
	implements PathAware
{
	private static $pages;

	public function __construct( $pages )
	{
		parent::__construct( );
		$this->pages = $pages;
	}


	public function setExtraPath( $extraPath )
	{
		if ( !is_array( $this->pages ) || ! array_key_exists( $extraPath , $this->pages ) ) {
			return false;
		}

		$handler = $this->pages[ $extraPath ];
		if ( ! is_array( $handler ) ) {
			$handler = array( $handler );
		}

		try {
			$toAdd = Loader::DirectCreate( 'ctrl' , true , $handler );
			$this->addController( $toAdd );
		} catch ( LoaderException $e ) {
			try {
				$toAdd = Loader::DirectCreate( 'view' , true , $handler );
				$this->addView( $toAdd );
			} catch ( LoaderException $e ) {
				return false;
			}
		}

		if ( $toAdd instanceof TitleProvider ) {
			$this->setTitle( $toAdd->getTitle( ) );
		}

		return true;
	}
}
