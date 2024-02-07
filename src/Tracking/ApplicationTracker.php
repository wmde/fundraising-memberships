<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tracking;

/**
 * @license GPL-2.0-or-later
 */
interface ApplicationTracker {

	/**
	 * @param int $applicationId
	 * @param MembershipApplicationTrackingInfo $trackingInfo
	 * @throws ApplicationTrackingException
	 */
	public function trackApplication( int $applicationId, MembershipApplicationTrackingInfo $trackingInfo ): void;

}
