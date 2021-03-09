<?php


namespace PixelgradeLT\Records;


class JsonCleaner {
	private static $_objects;
	private static $_depth;

	/**
	 * Cleans a variable for JSON encoding.
	 *
	 * Does the same thing as _wp_json_sanity_check(), but it does a really important thing extra: removes circular references.
	 *
	 * @param mixed $var       variable to be clean
	 * @param int   $depth     maximum depth that the dumper should go into the variable. Defaults to 10.
	 *
	 * @return mixed
	 */
	public static function clean( $var, $depth = 10 ) {
		self::$_objects = [];
		self::$_depth   = $depth;

		return self::cleanInternal( $var, 0 );
	}

	private static function cleanInternal( $var, $level ) {
		switch ( gettype( $var ) ) {
			case 'string':
				return _wp_json_convert_string( $var );
			case 'resource':
				return '{resource}';
			case 'unknown type':
				return '{unknown}';
			case 'array':
				if ( self::$_depth <= $level ) {
					return 'array(...)';
				}

				if ( empty( $var ) ) {
					return [];
				}

				$output = [];
				foreach ( $var as $key => $value ) {
					// Don't forget to sanitize the $key!
					if ( is_string( $key ) ) {
						$clean_key = _wp_json_convert_string( $key );
					} else {
						$clean_key = $key;
					}

					// Check the element type, so that we're only recursing if we really have to.
					if ( is_array( $value ) || is_object( $value ) ) {
						$output[ $clean_key ] = self::cleanInternal( $value, $level + 1 );
					} elseif ( is_string( $value ) ) {
						$output[ $clean_key ] = _wp_json_convert_string( $value );
					} else {
						$output[ $clean_key ] = $value;
					}
				}
				return $output;
			case 'object':
				if ( ( $id = array_search( $var, self::$_objects, true ) ) !== false ) {
					return get_class( $var ) . '#' . ( $id + 1 ) . '(...)';
				}

				if ( self::$_depth <= $level ) {
					return get_class( $var ) . '(...)';
				}

				$output = new \stdClass();
				$output->__original_class_name = get_class( $var );
				array_push( self::$_objects, $var );
				$members       = (array) $var;
				foreach ( $members as $key => $value ) {
					if ( is_string( $key ) ) {
						// Since the array cast will prepend an * guarded by null bytes, we need to clean.
						if ( false !== strpos( $key,"\0*\0") ) {
							$key = trim( str_replace( "\0*\0", '', $key ) );
						}
						if ( false !== strpos( $key,"\0") ) {
							$key = trim( str_replace( "\0", '*', $key ) );
						}
						$clean_key = _wp_json_convert_string( $key );
					} else {
						$clean_key = $key;
					}

					if ( is_array( $value ) || is_object( $value ) ) {
						$output->$clean_key = self::cleanInternal( $value, $level + 1 );
					} elseif ( is_string( $value ) ) {
						$output->$clean_key = _wp_json_convert_string( $value );
					} else {
						$output->$clean_key = $value;
					}
				}
				return $output;
			default:
				return $var;
		}
	}
}

