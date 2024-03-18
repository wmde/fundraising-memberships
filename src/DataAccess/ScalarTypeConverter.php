<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use InvalidArgumentException;

/**
 * This class converts "mixed" values from libraries like Doctrine or Symfony into scalar values, without tripping up
 * PHPStan, which disallows calling "strval" and "intval" on variables with "mixed" type, because calling them
 * with objects or arrays will generate a warning.
 *
 * DO NOT USE THIS IN LOOPS! (E.g. iterating over database results).
 * The constant type checking will slow down the application, use PHPStan-specific comments to ignore this error instead:
 * https://phpstan.org/user-guide/ignoring-errors
 *
 * Hopefully, in the future libraries will return fewer "mixed" types. Please check from time to time if this class is still needed.
 * Check the usage of the class methods to detect libraries that return mixed.
 */
class ScalarTypeConverter {
	public static function toInt( mixed $value ): int {
		return intval( self::assertScalarType( $value ) );
	}

	public static function toString( mixed $value ): string {
		return strval( self::assertScalarType( $value ) );
	}

	private static function assertScalarType( mixed $value ): int|string|bool|float {
		if ( is_scalar( $value ) ) {
			return $value;
		}
		throw new InvalidArgumentException( "Given value is not a scalar type" );
	}
}
