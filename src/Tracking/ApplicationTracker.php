<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tracking;

interface ApplicationTracker {

	/**
	 * @param int $applicationId
	 * @param MembershipApplicationTrackingInfo $trackingInfo
	 * @throws ApplicationTrackingException
	 */
	public function trackApplication( int $applicationId, MembershipApplicationTrackingInfo $trackingInfo ): void;

}
