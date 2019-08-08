<?php

namespace Andt;

/**
 * Class FilterInput
 * @package Andt
 *
 * This class will help you manage inputs inside the PHP super globals in a cleaner and safer way.
 */
class FilterInput {

	/**
	 * @var int
	 */
	protected $type;

	/**
	 * @var string
	 */
	protected $variable_name;

	/**
	 * @var int
	 */
	protected $filter = FILTER_DEFAULT;

	/**
	 * @var int
	 */
	protected $flags;

	/**
	 * FilterInput constructor
	 *
	 * @param int $type
	 * @param string $variable_name
	 */
	public function __construct( int $type, string $variable_name ) {
		$this->type          = $type;
		$this->variable_name = $variable_name;

	}

	/**
	 * @param int $filter
	 *
	 * @return FilterInput
	 */
	public function set_filter( int $filter ): self {
		$this->filter = $filter;

		return $this;
	}

	/**
	 * @param int $flags
	 *
	 * @return FilterInput
	 */
	public function set_flags( int $flags ): self {
		$this->flags = $flags;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function has_var(): bool {
		/* INPUT_REQUEST is still not implemented */
		if ( INPUT_REQUEST === $this->type ) {
			return isset( $_REQUEST[ $this->variable_name ] );
		}

		return filter_has_var( $this->type, $this->variable_name );
	}

	/**
	 * @param mixed $default
	 *
	 * @return array|null
	 */
	public function get_options( $default = null ) {
		$options = [];
		if ( ! is_null( $default ) ) {
			$options['options'] = [ 'default' => $default ];
		}

		if ( ! empty( $this->flags ) ) {
			$options['flags'] = $this->flags;
		}

		return ! empty( $options ) ? $options : null;
	}

	/**
	 * @param null $default
	 *
	 * @return mixed
	 */
	public function get( $default = null ) {
		if ( $this->has_var() ) {
			$options = $this->get_options( $default );
			/* INPUT_REQUEST is still not implemented */
			if ( INPUT_REQUEST === $this->type ) {
				return filter_var( $_REQUEST[ $this->variable_name ], $this->filter, $options );
			}

			return filter_input( $this->type, $this->variable_name, $this->filter, $options );
		}

		return $default;
	}

	/**
	 * @param array $config
	 *
	 * @return array
	 */
	public function get_arr( $config = [] ): array {
		if ( $this->has_var() ) {
			switch ( $this->type ) {
				case INPUT_REQUEST:
					$arr = $_REQUEST[ $this->variable_name ];
					break;
				case INPUT_POST:
					$arr = $_POST[ $this->variable_name ];
					break;
				case INPUT_GET:
					$arr = $_GET[ $this->variable_name ];
					break;
			}

			$args = [];
			foreach ( array_keys( $arr ) as $key ) {
				$filter = $config[ $key ]['filter'] ?? $this->filter;
				$flags  = $config[ $key ]['flags'] ?? $this->flags;

				$args[ $key ] = [ 'filter' => $filter ];
				if ( ! empty( $flags ) ) {
					$args[ $key ]['flags'] = $flags;
				}
			}

			return filter_var_array( $arr, $args );
		}

		return [];
	}

	/**
	 * @param mixed $value
	 *
	 * @return bool
	 *
	 * @codeCoverageIgnore
	 */
	public function equals( $value ): bool {
		return $value == $this->get();
	}

	/**
	 * @return mixed
	 */
	public static function get_request_uri() {
		return ( new static( INPUT_SERVER, 'REQUEST_URI' ) )->get();
	}

}
