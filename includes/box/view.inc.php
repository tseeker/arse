<?php

class BoxButton
{
	protected $title;
	protected $URL;
	protected $id;
	protected $class;
	protected $style;

	protected function __construct( $title , $URL )
	{
		$this->title = $title;
		$this->URL = $URL;
	}

	public function setID( $id )
	{
		$this->id = $id;
		return $this;
	}

	public function setClass( $class )
	{
		$this->class = $class;
		return $this;
	}

	public function setStyle( $style )
	{
		$this->style = $style;
		return $this;
	}

	public function render( $baseURL )
	{
		$url = $this->URL;
		if ( $url{0} != ':' ) {
			if ( $url{0} != '/' ) {
				$url = "/$url";
			}
			$url = $baseURL . $url;
		}
		return HTML::make( 'a' )
			->setAttribute( 'title' , HTML::from( $this->title ) )
			->setAttribute( 'href' , $url )
			->setAttribute( 'class' ,
				'box-button' . ( ( $this->class === null )
					? '' : ( ' ' . $this->class ) ) )
			->setAttribute( 'style' , $this->style )
			->setAttribute( 'id' , $this->id )
			->appendElement( HTML::make( 'span' )
				->appendText( $this->title ) );
	}


	public static function create( $title , $URL )
	{
		return new BoxButton( $title , $URL );
	}
}


class View_Box
	extends BaseURLAwareView
{
	protected $title;
	protected $class;
	protected $id;

	protected $buttons = array( );
	protected $contents;


	public function __construct( $title , View $contents )
	{
		$this->title = $title;
		$this->contents = $contents;
	}


	public function setBaseURL( $baseURL )
	{
		parent::setBaseURL( $baseURL );
		if ( $this->contents instanceof BaseURLAware ) {
			$this->contents->setBaseURL( $baseURL );
		}
	}


	public function setClass( $class )
	{
		$this->class = $class;
		return $this;
	}


	public function setID( $id )
	{
		$this->id = $id;
		return $this;
	}


	public function addButton( BoxButton $button )
	{
		array_push( $this->buttons , $button );
		return $this;
	}


	public function render( )
	{
		$box = HTML::make( 'div' )
			->setAttribute( 'class' , 'box' . ( is_null( $this->class ) ? '' : " {$this->class}" ) )
			->setAttribute( 'id' , $this->id )
			->appendText( "\n" );

		if ( ! is_null( $this->title ) ) {
			$box->appendElement( HTML::make( 'h2' )
					->setAttribute( 'class' , 'box-title' )
					->appendText( $this->title ) );
		}

		if ( ! empty( $this->buttons )) {
			$buttons = HTML::make( 'div' )
				->setAttribute( 'class' , 'box-buttons' );
			foreach ( $this->buttons as $button ) {
				$buttons->appendElement( $button->render( $this->base ) );
			}
			$box->appendElement( $buttons );
		}

		$box->appendElement( HTML::make( 'div' )
			->setAttribute( 'class' , 'box-contents' )
			->append( $this->contents->render( ) ) );

		return $box;
	}
}
