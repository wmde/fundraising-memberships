<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Authorization;

interface MembershipTokenGenerator {

	public function generateToken(): string;

	public function generateTokenExpiry(): \DateTime;

}
