<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain;

/**
 * Anonymize personal data of memberships
 */
interface MembershipAnonymizer {

	/**
	 * Anonymize all memberships eligible for anonymization. This is for nightly batch updates.
	 *
	 * The following memberships are eligible:
	 * - Exported memberships
	 * - Deleted memberships
	 *
	 * All other memberships should be left alone.
	 * For optimization purposes, implementations may track which memberships have already been anonymized.
	 *
	 * @return int number of anonymized memberships
	 * @throws AnonymizationException
	 */
	public function anonymizeAll(): int;

	/**
	 * Anonymize an individual memberships by providing their IDs.
	 *
	 * @throws AnonymizationException
	 */
	public function anonymizeWithIds( int ...$membershipIds ): void;
}
