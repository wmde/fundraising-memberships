<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\TestDoubles;

use WMDE\Fundraising\MembershipContext\Authorization\MembershipAuthorizationChecker;

class SucceedingAuthorizationChecker implements MembershipAuthorizationChecker {

	public function canModifyMembership( int $membershipId ): bool {
		return true;
	}

	public function canAccessMembership( int $membershipId ): bool {
		return true;
	}

}
