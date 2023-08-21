<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Authorization;

interface MembershipAuthorizationChecker {

	/**
	 * Should return false on infrastructure failure.
	 *
	 * @param int $membershipId
	 *
	 * @return bool
	 */
	public function canModifyMembership( int $membershipId ): bool;

	/**
	 * Should return false on infrastructure failure.
	 *
	 * @param int $membershipId
	 *
	 * @return bool
	 */
	public function canAccessMembership( int $membershipId ): bool;

}
