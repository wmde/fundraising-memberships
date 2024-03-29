<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tracking;

/**
 * Services implementing this interface store the Piwik tracking data associated with a membership application.
 */
interface ApplicationPiwikTracker {

	/**
	 * @param int $applicationId
	 * @param string $trackingString
	 *
	 * @throws ApplicationPiwikTrackingException
	 */
	public function trackApplication( int $applicationId, string $trackingString ): void;

}
