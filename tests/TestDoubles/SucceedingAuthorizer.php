<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\TestDoubles;

use WMDE\Fundraising\MembershipContext\Authorization\ApplicationAuthorizer;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SucceedingAuthorizer implements ApplicationAuthorizer {

	public function canModifyApplication( int $applicationId ): bool {
		return true;
	}

	public function canAccessApplication( int $applicationId ): bool {
		return true;
	}

}
