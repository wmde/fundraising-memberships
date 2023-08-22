<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Authorization;

/**
 * @deprecated The tokens should be generated outside the bounded context
 */
interface MembershipTokenGenerator {

	public function generateToken(): string;

	public function generateTokenExpiry(): \DateTime;

}
