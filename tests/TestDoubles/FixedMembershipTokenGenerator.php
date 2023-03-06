<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\TestDoubles;

use WMDE\Fundraising\MembershipContext\Authorization\MembershipTokenGenerator;

class FixedMembershipTokenGenerator implements MembershipTokenGenerator {
	private \DateTime $expiry;

	public function __construct( private readonly string $token, \DateTime $expiry = null ) {
		$this->expiry = $expiry === null ? new \DateTime() : $expiry;
	}

	public function generateToken(): string {
		return $this->token;
	}

	public function generateTokenExpiry(): \DateTime {
		return $this->expiry;
	}

}
