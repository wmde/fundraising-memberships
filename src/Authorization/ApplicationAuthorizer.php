<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Authorization;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface ApplicationAuthorizer {

	/**
	 * Should return false on infrastructure failure.
	 *
	 * @param int $applicationId
	 *
	 * @return bool
	 */
	public function canModifyApplication( int $applicationId ): bool;

	/**
	 * Should return false on infrastructure failure.
	 *
	 * @param int $applicationId
	 *
	 * @return bool
	 */
	public function canAccessApplication( int $applicationId ): bool;

}
