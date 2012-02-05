<?php


final class URLMapperException extends Exception {}


final class URLMapper
	implements PackageAware
{
	private $package;
	private $prefix;
	private $configBase;

	public function __construct( $prefix = null )
	{
		$this->prefix = $prefix;
		$this->configBase = ( $prefix == null ) ? 'urls' : $prefix;
	}

	public function setPackage( Package $package )
	{
		if ( $this->package !== null ) {
			throw new Exception( 'trying to call setPackage() twice' );
		}
		$this->package = $package;
	}


	public function fromPathInfo( )
	{
		if ( array_key_exists( 'PATH_INFO' , $_SERVER ) ) {
			$path = $_SERVER[ 'PATH_INFO' ];
		} else {
			$path = '/' . $this->package->config( $this->configBase . '/default' , 'home' );
		}

		$this->fromPath( $path );
	}

	public function fromPath( $path )
	{
		if ( ! preg_match( '/^(\/[a-z0-9]+)+$/' , $path ) ) {
			$this->showPageNotFound( );
			return;
		} 

		if ( $this->prefix == null ) {
			$path = substr( $path , 1 );
		} else {
			$path = $this->prefix . $path;
		}
		try {
			$this->showPageFor( $path );
		} catch ( URLMapperException $e ) {
			$this->showPageNotFound( $path );
		}
	}


	private function showPageNotFound( $requestPath = '' )
	{
		$path = $this->package->config( $this->configBase . '/errors/404', 'errors/404' );
		$this->showPageFor( $path . '/' . $requestPath , false );
	}


	private function showPageFor( $path )
	{
		$split = split( '/' , $path );
		$extras = array( );
		while ( !empty( $split ) ) {
			$name = join( '_' , $split );
			try {
				$page = Loader::Page( $name );
				break;
			} catch ( LoaderException $e ) {
				array_unshift( $extras , array_pop( $split ) );
			}
		}

		if ( empty( $split ) ) {
			throw new URLMapperException( $path );
		}

		$this->handlePage( $path , $page , join( '/' , $extras ) );
	}


	private function handlePage( $requestPath , Page $page , $extraPath = '' , $pathFailure = true )
	{
		if ( $page instanceof PathAware ) {
			$success = $page->setExtraPath( $extraPath );
		} else {
			$success = ( $extraPath === '' );
		}

		if ( $pathFailure && !$success ) {
			$this->showPageNotFound( $requestPath );
		} else {
			$page->handle( );
		}
	}
}


class View_BasicErrorDisplay
	implements View
{
	private $error;
	private $site;
	private $path;

	public function __construct( $extraPath )
	{
		$this->error = array_shift( $extraPath );
		if ( ! empty( $extraPath ) ) {
			$this->site = array_shift( $extraPath );
			$this->path = join( '/' , $extraPath );
		}
	}

	public function render( )
	{
		$text = HTML::make( 'p' )
			->setAttribute( 'class' , 'error' )
			->appendText( 'An error occurred while trying to access this page.' )
			->appendElement( HTML::make( 'br' ) )
			->appendText( 'Error code ' )
			->appendElement( HTML::make( 'strong' )->appendText( $this->error ) )
			->appendText( ' was encountered' );
		if ( $this->site !== null ) {
			$text->appendText( ' while trying to access ' )
				->appendElement( HTML::make( 'strong' )->appendText( '/' . $this->path ) )
				->appendText( ' on site ' )
				->appendElement( HTML::make( 'strong' )->appendText( $this->site ) );
		}
		return $text->appendText( '.' );
	}

}


class Page_Errors
	extends Page_Basic
	implements PathAware
{
	private $httpError;

	public function setExtraPath( $extraPath )
	{
		$extraPath = split( '/' , $extraPath );
		if ( (int)$extraPath[ 0 ] != 0 ) {
			$this->httpError = (int) $extraPath[ 0 ];
		}
		$this->setTitle( 'Error ' . $extraPath[ 0 ] );
		$this->addView( Loader::View( 'basic_error_display' , $extraPath ) );

		return true;
	}

	public function render( )
	{
		if ( $this->httpError !== null ) {
			header( 'HTTP/1.0 ' . $this->httpError );
		}
		return parent::render( );
	}
}
