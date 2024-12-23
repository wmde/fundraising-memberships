<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tracking;

/**
 * Services implementing this interface store tracking data associated with a membership application.
 */
interface MembershipTrackingRepository {

	/**
	 * @throws MembershipTrackingException
	 */
	public function storeTracking( int $membershipId, string $trackingString ): void;

	/**
	 * @throws MembershipTrackingException
	 */
	public function getTracking( int $membershipId ): string;
}
