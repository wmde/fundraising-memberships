<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\Authorization;

use WMDE\Fundraising\PaymentContext\Services\URLAuthenticator;

interface MembershipAuthorizer {
	/**
	 * Authorize read and write access (for the "owner", i.e. the new member) to a membership application.
	 */
	public function authorizeMembershipAccess( int $membershipId ): URLAuthenticator;
}
