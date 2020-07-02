<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tracking;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
interface ApplicationTracker {

	/**
	 * @param int $applicationId
	 * @param MembershipApplicationTrackingInfo $trackingInfo
	 * @throws ApplicationTrackingException
	 */
	public function trackApplication( int $applicationId, MembershipApplicationTrackingInfo $trackingInfo ): void;

}
