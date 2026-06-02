<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Data;

class ValidatorPatterns {
	public const COUNTRY_POSTCODE = [
		'DE' => '/^[0-9]{5}$/',
		'AT' => '/^[0-9]{4}$/',
		'CH' => '/^[0-9]{4}$/',
		'BE' => '/^[0-9]{4}$/',
		'IT' => '/^[0-9]{5}$/',
		'LI' => '/^[0-9]{4}$/',
		'LU' => '/^[0-9]{4}$/',
	];

	public const ADDRESS_PATTERNS = [
		'firstName' => "/^[A-Za-z\x{00C0}-\x{00D6}\x{00D8}-\x{00f6}\x{00f8}-\x{00ff}\\s\\-\\.\\']+$/",
		'lastName' => "/^[A-Za-z\x{00C0}-\x{00D6}\x{00D8}-\x{00f6}\x{00f8}-\x{00ff}\\s\\-\\.\\']+$/",
		'postcode' => '/^.+$/',
	];
}
