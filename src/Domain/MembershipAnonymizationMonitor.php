<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain;

/**
 * This class contains methods to monitor the amount of older membership applications in the database which still
 * contain private data.
 * We use them to check whether our private data scrubbing processes work correctly.
 */
interface MembershipAnonymizationMonitor {

	/**
	 * @return int amount of old membership applications that are still marked
	 * as moderated in the database and need to get their status resolved. This is needed to scrub them
	 */
	public function countOldAbandonedModeratedMembershipApplications(): int;

}
