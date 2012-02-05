<?php


abstract class Page
{
	private $baseURL;
	private $controllers = array( );
	protected $views = array( );


	public function __construct( )
	{
		$this->baseURL = dirname( $_SERVER[ 'SCRIPT_NAME' ] );
	}

	public final function addController( Controller $controller )
	{
		array_push( $this->controllers , $controller );
		return $this;
	}


	public final function addView( View $view )
	{
		array_push( $this->controllers , new Ctrl_Simple( $view ) );
		return $this;
	}

	public final function getBaseURL( )
	{
		return $this->baseURL;
	}


	protected abstract function render( );

	protected function handleControllerValue( $rc )
	{
		$rv = false;
		if ( is_a( $rc , 'View' ) ) {
			array_push( $this->views , $rc );
		} elseif ( is_a( $rc , 'Controller' ) ) {
			$rv = $this->executeController( $rc );
		} elseif ( is_array( $rc ) ) {
			foreach ( $rc as $rcItem ) {
				if ( $this->handleControllerValue( $rcItem ) ) {
					$rv = true;
					break;
				}
			}
		} elseif ( ! is_null( $rc ) ) {
			header( "Location: $rc" );
			$rv = true;
		}
		return $rv;
	}

	protected function executeController( Controller $controller )
	{
		return $this->handleControllerValue( $controller->handle( $this ) );
	}

	public final function handle( )
	{
		$mustDraw = true;
		foreach ( $this->controllers as $controller ) {
			if ( $this->executeController( $controller ) ) {
				$mustDraw = false;
				break;
			}
		}
		if ( $mustDraw ) {
			$this->render( );
		}
		Loader::Singleton( 'Database' )->commit( );
	}
}


interface PathAware
{

	public function setExtraPath( $path );

}


abstract class HTMLPage
	extends Page
	implements PackageAware
{
	protected $title;
	protected $package;

	public function __construct( )
	{
		parent::__construct( );
	}

	public function setPackage( Package $package )
	{
		if ( $this->package !== null ) {
			throw new Exception( 'trying to call setPackage() twice' );
		}
		$this->package = $package;
	}

	protected abstract function getMenu( );


	private function renderMenu( )
	{
		$menu = $this->getMenu( );
		if ( empty( $menu ) ) {
			return null;
		}

		$html = HTML::make( 'ul' )
			->setAttribute( 'class' , 'page-menu' );

		foreach ( $menu as $link => $title ) {
			$html->appendElement( HTML::make( 'li' )
				->appendElement( HTML::make( 'a' )
					->setAttribute( 'href' , $link )
					->setAttribute( 'title' , HTML::from( $title ) )
					->appendText( $title ) ) );
		}

		return $html;
	}


	public function setTitle( $title )
	{
		$this->title = $title;
		return $this;
	}


	protected function getHead( $title )
	{
		return HTML::make( 'head' )
			->appendElement( HTML::make( 'title' )
				->appendText( $title ) )
			->appendElement( HTML::make( 'meta' )
				->setAttribute( 'http-equiv' , 'content-type' )
				->setAttribute( 'content' , 'text/html;charset=UTF-8' ) )
			->appendElement( HTML::make( 'style' )
				->setAttribute( 'id' , 'main-style' )
				->setAttribute( 'class' , 'css-style' )
				->appendText( "\n@import url('" . $this->getBaseURL( )
					. "/style.css?1');\n" ) );
	}


	protected function getBody( $title )
	{
		$menu = $this->renderMenu( );
		$container = HTML::make( 'div' )
			->setAttribute( 'class' , 'page-container' );

		$t = HTML::make( 'h1' )->appendText( $title );
		if ( is_null( $menu ) ) {
			$t->setAttribute( 'class' , 'no-menu' );
		}
		$container->appendElement( $t );

		if ( !is_null( $menu ) ) {
			$container->append( $menu );
		}

		foreach ( $this->views as $view ) {
			$container->append( $view->render( ) );
		}

		return HTML::make( 'body' )->appendElement( $container );
	}


	public function render( )
	{
		$baseTitle = $this->package->config( 'pages/baseTitle' , null , false );
		if ( $baseTitle === null ) {
			$baseTitle = Loader::PackageConfig( 'core' )->get( 'pages/baseTitle' , '' , true );
		}
		$title = is_null( $this->title ) ? '' : ( ' - ' . $this->title );
		$pTitle = is_null( $this->title ) ? $baseTitle : $this->title;
		$title = $baseTitle . $title;

		header( 'Content-type: text/html; charset=utf-8' );
		echo HTML::make( 'html' )
			->appendElement( $this->getHead( $title ) )
			->appendElement( $this->getBody( $pTitle ) )
			->getCode( );
	}
}


class Page_Basic
	extends HTMLPage
{

	protected function getMenu( )
	{
		return array( );
	}

}
