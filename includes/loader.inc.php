<?php


final class LoaderException extends Exception { }


final class ConfigGetter
{
	private $config;
	private $package;

	public function __construct( Package $package , $config )
	{
		$this->package = $package;
		$this->config = $config;
	}


	public function get( $path = '' , $default = null , $fail = false )
	{
		if ( $path == '' ) {
			return $this->config;
		}

		$aPath = explode( '/' , $path );
		$config = &$this->config;
		foreach ( $aPath as $name ) {
			if ( !( is_array( $config ) && array_key_exists( $name , $config ) ) ) {
				if ( $fail ) {
					throw new LoaderException( "configuration key '$path' not found for package '"
						. $this->package->name() . "'" );
				}
				return $default;
			}
			$config = &$config[ $name ];
		}
		return $config;
	}
}


final class Package
{
	private $name;
	private $source;

	private $files;
	private $requires;

	private $ctrls;
	private $daos;
	private $extras;
	private $hooks;
	private $pages;
	private $singletons;
	private $views;

	private $loaded = false;
	private $config;

	public function __construct( $name , $description , $config , $source )
	{
		$this->name = $name;
		$this->source = $source;

		$fields = array( 'files' , 'requires' , 'daos' , 'views' , 'ctrls' , 'extras' , 'singletons' , 'pages' , 'hooks' );
		foreach ( $fields as $field ) {
			$this->getField( $description , $field );
		}
		if ( empty( $this->files ) ) {
			throw new LoaderException( "package '{$this->name}': no files" );
		}

		if ( ! is_array( $config ) ) {
			$config = array( );
		}
		$this->config = new ConfigGetter( $this , $config );
	}


	private function getField( $description , $field )
	{
		if ( ! array_key_exists( $field , $description ) ) {
			$this->$field = array( );
			return;
		}

		$value = $description[ $field ];
		if ( !is_array( $value ) ) {
			throw new LoaderException( "package '{$this->name}': '$field' must be an array" );
		}

		foreach ( $value as $item ) {
			if ( !is_string( $item ) ) {
				throw new LoaderException( "package '{$this->name}': '$field' contains non-string items" );
			}
		}

		$this->$field = $value;
	}


	public function name( )
	{
		return $this->name;
	}

	public function source( )
	{
		return $this->source;
	}

	public function files( )
	{
		return $this->files;
	}

	public function requires( )
	{
		return $this->requires;
	}

	public function daos( )
	{
		return $this->daos;
	}

	public function views( )
	{
		return $this->views;
	}

	public function ctrls( )
	{
		return $this->ctrls;
	}

	public function singletons( )
	{
		return $this->singletons;
	}

	public function extras( )
	{
		return $this->extras;
	}

	public function pages( )
	{
		return $this->pages;
	}


	public function loaded( )
	{
		return $this->loaded;
	}

	public function hooks( )
	{
		return $this->hooks;
	}

	public function setLoaded( )
	{
		$this->loaded = true;
	}


	public function config( $path = '' , $default = null , $fail = false )
	{
		return $this->config->get( $path , $default , $fail );
	}

	public function getConfigAccess( )
	{
		return $this->config;
	}
}


interface PackageAware
{
	public function setPackage( Package $package );
}


interface TextSource
{
	public function get( $what );
}


final class Loader
{
	private static $loader = null;

	private static $paths = array();
	private $config;
	private $packages = array( );
	private $items = array(
			'ctrls'		=> array( ) ,
			'daos'		=> array( ) ,
			'extras'	=> array( ) ,
			'singletons'	=> array( ) ,
			'views'		=> array( ) ,
			'pages'		=> array( ) ,
		);
	private $loading = array( );
	private $singletons = array( );
	private $daos = array( );
	private $textSource;

	private function __construct( )
	{
		if ( empty( Loader::$paths ) ) {
			array_push( Loader::$paths , dirname( __FILE__ ) );
		}
		$this->loadConfig( );
		$this->loadPackageDescriptions( );
	}

	private function loadConfig( )
	{
		$mergedConfig = array( );
		foreach ( Loader::$paths as $directory ) {
			if ( ! file_exists( $directory . '/config.inc.php' ) ) {
				continue;
			}

			$config = array( );
			@include( $directory . '/config.inc.php' );
			$mergedConfig = array_merge_recursive( $mergedConfig , $config );
		}
		$this->config = $mergedConfig;
	}

	private function loadPackageDescriptions( )
	{
		foreach ( Loader::$paths as $source ) {
			$this->loadPackageDescriptionsFrom( $source );
		}
	}

	private function loadPackageDescriptionsFrom( $source )
	{
		if ( !( $dh = opendir( $source ) ) ) {
			throw new LoaderException( "unable to access directory" );
		}

		while ( ( $entry = readdir( $dh ) ) !== false ) {
			if ( $entry === '.' || $entry === '..' ) {
				continue;
			}

			$path = "$source/$entry";
			if ( is_dir( $path ) && is_file( "$path/package.inc.php" ) ) {
				$this->loadDescription( $entry , $source );
			}
		}

		closedir( $dh );
	}


	private function loadDescription( $name , $source )
	{
		$package = array( );
		require( $source . '/' . $name . '/package.inc.php' );
		if ( empty( $package ) ) {
			throw new LoaderException( "package '$name': no information" );
		}

		if ( ! array_key_exists( $name , $this->config ) ) {
			$this->config[ $name ] = array( );
		}

		$package = new Package( $name , $package , $this->config[ $name ] , $source );
		$this->packages[ $name ] = $package;
		$this->config[ $name ] = null;

		foreach ( array_keys( $this->items ) as $type ) {
			$items = $package->$type( );
			foreach ( $items as $item ) {
				if ( array_key_exists( $item , $this->items[ $type ] ) ) {
					$oName = $this->items[ $type ][ $item ];
					$type = substr( $type , 0 , strlen( $type ) - 1 );
					throw new LoaderException( "package '$name': conflict with '$oName' on $type '$item'" );
				}
				$this->items[ $type ][ $item ] = $name;
			}
		}
	}


	private function loadPackage( $name )
	{
		if ( ! array_key_exists( $name , $this->packages ) ) {
			throw new LoaderException( "Package '$name' not found" );
		}

		$package = $this->packages[ $name ];
		if ( $package->loaded( ) ) {
			return;
		}

		if ( array_key_exists( $name , $this->loading ) ) {
			throw new LoaderException( "Package '$name': recursive dependencies detected" );
		}
		$this->loading[ $name ] = 1;

		foreach ( $package->requires( ) as $dependency ) {
			$this->loadPackage( $dependency );
		}

		$dir = $package->source() . '/' . $name;
		foreach ( $package->files( ) as $file ) {
			require_once( "$dir/$file.inc.php" );
		}

		unset( $this->loading[ $name ] );
		$package->setLoaded( );

		$hooks = $package->hooks( );
		if ( is_array( $hooks ) ) {
			foreach ( $hooks as $hook ) {
				$hook( $this , $package );
			}
		}
	}


	private function findItem( $name , $type )
	{
		$rType = $type . 's';
		if ( ! array_key_exists( $rType , $this->items ) ) {
			throw new LoaderException( "Invalid type '$type'" );
		}

		if ( ! array_key_exists( $name , $this->items[ $rType ] ) ) {
			throw new LoaderException( "Item '$name' of type $type not found" );
		}

		$package = $this->items[ $rType ][ $name ];
		$this->loadPackage( $package );
		return $this->packages[ $package ];
	}


	private function createInstance( $package , $cName , $args )
	{
		if ( empty( $args ) ) {
			$instance = new $cName();
		} else {
			$reflection = new ReflectionClass( $cName );
			$instance = $reflection->newInstanceArgs( $args );
		}

		if ( is_a( $instance , 'PackageAware' ) ) {
			$instance->setPackage( $package );
		}
		return $instance;
	}


	private function loadAndCreate( $type , $name , $cName , $args )
	{
		$package = $this->findItem( $name , $type );
		return $this->createInstance( $package , $cName , $args ); 
	}


	private function getSingleton( $name )
	{
		if ( ! array_key_exists( $name , $this->singletons ) ) {
			$this->singletons[ $name ] = $this->loadAndCreate( 'singleton' , $name , $name , array( ) );
		}
		return $this->singletons[ $name ];
	}


	private function getDao( $name )
	{
		if ( array_key_exists( $name , $this->daos ) ) {
			return $this->daos[ $name ];
		}
		$cName = Loader::convertName( 'DAO' , $name );
		$instance = $this->loadAndCreate( 'dao' , $name , $cName , array( ) );
		$instance->setDatabase( Loader::Singleton( 'Database' ) );
		$this->daos[ $name ] = $instance;
		return $instance;
	}


	private static function get( )
	{
		if ( Loader::$loader === null ) {
			Loader::$loader = new Loader( );
		}
		return Loader::$loader;
	}

	private static function convertName( $type , $name )
	{
		$cName = ucfirst( $type ) . '_';
		foreach ( explode( '_' , $name ) as $part ) {
			$cName .= ucfirst( $part );
		}
		return $cName;
	}

	private static function creator( $type , $convert , $args )
	{
		$name = array_shift( $args );
		$cName = $convert ? Loader::convertName( $type , $name ) : $name;
		return Loader::get( )->loadAndCreate( $type , $name , $cName , $args );
	}


	public static function AddPath( $path )
	{
		if ( empty( Loader::$paths ) ) {
			array_push( Loader::$paths , dirname( __FILE__ ) );
		}
		if ( is_dir( $path ) ) {
			array_push( Loader::$paths , $path );
		}
	}


	public static function PackageConfig( $name )
	{
		$loader = Loader::get( );
		$loader->loadPackage( $name );
		return $loader->packages[ $name ]->getConfigAccess( );
	}


	public static function TextSource( TextSource $source = null )
	{
		if ( $source !== null ) {
			Loader::get( )->textSource = $source;
		} else {
			return Loader::get( )->textSource;
		}
	}


	public static function Text( $what )
	{
		$source = Loader::get( )->textSource;
		return $source ? $source->get( $what ) : $what;
	}


	public static function Load( $name , $type = 'extra' )
	{
		Loader::get( )->findItem( $name , $type );
	}


	public static function Singleton( $name )
	{
		return Loader::get( )->getSingleton( $name );
	}

	public static function Create( )
	{
		return Loader::creator( 'extra' , false , func_get_args( ) );
	}

	public static function View( )
	{
		return Loader::creator( 'view' , true , func_get_args( ) );
	}

	public static function Ctrl( )
	{
		return Loader::creator( 'ctrl' , true , func_get_args( ) );
	}

	public static function Page( )
	{
		return Loader::creator( 'page' , true , func_get_args( ) );
	}

	public static function DAO( $name )
	{
		return Loader::get( )->getDao( $name );
	}
}
