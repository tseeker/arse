<?php


class Validator_StringLength
	implements FieldValidator
{
	protected $errorPrefix;
	protected $minLength;
	protected $maxLength;

	public function __construct( $errorPrefix , $minLength = 0 , $maxLength = null )
	{
		assert( $maxLength === null || $maxLength >= $minLength );
		$this->errorPrefix = $errorPrefix;
		$this->minLength = $minLength;
		$this->maxLength = $maxLength;
	}


	public function validate( $value )
	{
		$len = strlen( $value );
		if ( $len < $this->minLength ) {
			$template = Loader::Text( '%1$s is too short (min. %2$d characters)' );
			return array( sprintf( $template , $this->errorPrefix , $this->minLength ) );
		}
		if ( $this->maxLength !== null && $len > $this->maxLength ) {
			$template = Loader::Text( '%1$s is too long (max. %2$d characters)' );
			return array( sprintf( $template , $this->errorPrefix , $this->maxLength ) );
		}
		return null;
	}
}


class Validator_InArray
	implements FieldValidator
{
	private $values;
	private $errorText;

	public function __construct( array $values , $errorText )
	{
		$this->values = $values;
		$this->errorText = $errorText;
	}

	public function validate( $value )
	{
		if ( ! ( empty( $this->values ) || in_array( $value , $this->values ) ) ) {
			return array( $this->errorText );
		}
		return null;
	}
}


class Validator_IntValue
	implements FieldValidator
{

	private $invalidText;
	private $minValue;
	private $minError;
	private $maxValue;
	private $maxError;

	public function __construct( $invalidText )
	{
		$this->invalidText = $invalidText;
	}

	public function setMinValue( $minValue , $minError = null )
	{
		assert( $this->maxValue === null || $minValue <= $this->maxValue );
		$this->minValue = $minValue;
		$this->minError = ( $minError === null ) ? $this->invalidText : $minError;
		return $this;
	}

	public function setMaxValue( $maxValue , $maxError = null )
	{
		assert( $this->minValue === null || $maxValue >= $this->maxValue );
		$this->maxValue = $maxValue;
		$this->maxError = ( $maxError === null ) ? $this->invalidText : $maxError;
		return $this;
	}

	public function validate( $value )
	{
		if ( !is_scalar( $value ) || (int) $value != $value ) {
			return array( $this->invalidText );
		} else if ( $this->minValue !== null && $value < $this->minValue ) {
			return array( $this->minError );
		} else if ( $this->maxValue !== null && $value > $this->maxValue ) {
			return array( $this->maxError );
		}
	}
}
