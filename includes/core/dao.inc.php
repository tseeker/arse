<?php

abstract class DAO
{
	private $database;

	public final function setDatabase( Database $database )
	{
		if ( $this->database !== null ) {
			throw new Exception( "trying to change DAO database" );
		}
		$this->database = $database;
	}

	protected final function query( $query , $prepare = false )
	{
		return $this->database->query( $query , $prepare );
	}
}

