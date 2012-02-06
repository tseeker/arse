<?php

class DatabaseError extends RuntimeException { }

final class Database
	implements PackageAware
{

	private $connection;
	private $package;
	private $queries = array( );

	public function setPackage( Package $package )
	{
		if ( $this->package !== null ) {
			throw new Exception( 'trying to call setPackage() twice' );
		}
		$this->package = $package;
	}


	public function query( $query , $prepare = false )
	{
		if ( ! $this->connection ) {
			$this->connect( );
		}
		if ( ! array_key_exists( $query , $this->queries )
				|| ( $prepare && ! $this->queries[ $query ]->prepared( ) ) ) {
			$this->queries[ $query ] = new DBQuery( $this->connection , $query , $prepare );
		}
		return $this->queries[ $query ];
	}

	public function commit( )
	{
		if ( ! $this->connection ) {
			return;
		}
		if ( ! @pg_query( 'COMMIT' ) ) {
			throw new DatabaseError( 'COMMIT: ' . pg_last_error( ) );
		}
		exit( 0 );
	}

	public function connect( )
	{
		$host = $this->package->config( 'db/host' , 'localhost' );
		$port = $this->package->config( 'db/port' );
		$name = $this->package->config( 'db/name' , null , true );
		$user = $this->package->config( 'db/user' );
		$pass = $this->package->config( 'db/password' );

		$cString = array( );
		$cString[] = "host=$host";
		if ( $port !== null ) {
			$cString[] = "port=$port";
		}
		$cString[] = "dbname=$name";
		if ( $user !== null ) {
			$cString[] = "user=$user";
		}
		if ( $pass !== null ) {
			$cString[] = "password=$pass";
		}

		$this->connection = pg_connect( join( ' ' , $cString ) );
		if ( ! $this->connection ) {
			throw new DatabaseError( 'connection failed' );
		}

		if ( ! @pg_query( $this->connection , 'BEGIN TRANSACTION' ) ) {
			throw new DatabaseError( 'BEGIN TRANSACTION: ' . pg_last_error( ) );
		}
	}
}


final class DBQuery
{
	private static $lastStatementID = 0;

	private $connection;
	private $query;
	private $statement;


	public function __construct( $connection , $query , $prepare = false )
	{
		$this->connection = $connection;
		$this->query = $query;
		if ( $prepare ) {
			$this->statement = 'prep_stmt_' . ( ++ DBQuery::$lastStatementID );
			if ( ! pg_prepare( $connection , $this->statement , $query ) ) {
				throw new Exception( "unable to prepare statement '$query': " . pg_last_error( ) );
			}
		} else {
			$this->statement = null;
		}
	}

	public function __destruct( )
	{
		if ( $this->statement !== null ) {
			if ( ! pg_query( 'DEALLOCATE ' . $this->statement ) ) {
				throw new Exception( "unable to deallocate statement: " . pg_last_error( ) );
			}
		}
	}


	public function execute( )
	{
		$arguments = func_get_args( );
		$result = array( );

		if ( $this->statement !== null ) {
			$pgResult = pg_execute( $this->connection , $this->statement , $arguments );
		} else {
			$pgResult = pg_query_params( $this->connection , $this->query , $arguments );
		}

		if ( ! $pgResult ) {
			throw new Exception( "query \"{$this->query}\" failed: " . pg_last_error( ) );
		}

		while ( $row = pg_fetch_object( $pgResult ) ) {
			array_push( $result , $row );
		}
		pg_free_result( $pgResult );

		return $result;
	}


	public function prepared( )
	{
		return ( $this->statement !== null );
	}
}
