<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Model;

class PhoneNumber {

	public function __construct( private readonly string $phoneNumber ) {
	}

	public function __toString(): string {
		return $this->phoneNumber;
	}

}
