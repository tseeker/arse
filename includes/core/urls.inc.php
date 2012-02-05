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


	private function showPageNotFound( $requestPath )
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
