<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Authorization;

class RandomMembershipTokenGenerator implements MembershipTokenGenerator {

	public function __construct( private readonly int $tokenLength, private readonly \DateInterval $validityTimeSpan ) {
	}

	public function generateToken(): string {
		return bin2hex( random_bytes( $this->tokenLength ) );
	}

	public function generateTokenExpiry(): \DateTime {
		return ( new \DateTime() )->add( $this->validityTimeSpan );
	}

}
