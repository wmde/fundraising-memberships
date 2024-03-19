<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Model;

class ApplicantAddress {
	public function __construct(
			public readonly string $streetAddress = '',
			public readonly string $postalCode = '',
			public readonly string $city = '',
			public readonly string $countryCode = '',
	) {
	}
}
