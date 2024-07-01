<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain;

/**
 * Anonymize personal data of memberships
 */
interface MembershipAnonymizer {

	/**
	 * Anonymize memberships with the matching timestamp. This is for nightly batch updates.
	 *
	 * This is for nightly batch updates we use external script (that exports data sets and then updates all donations
	 * with a timestamp that acts as the identifier of the donation batch that should be anonymized).
	 * The timestamp is NOT the export date!
	 *
	 * @param \DateTimeImmutable $timestamp
	 * @return int number of anonymized memberships
	 * @throws AnonymizationException
	 */
	public function anonymizeAt( \DateTimeImmutable $timestamp ): int;

	/**
	 * Anonymize an individual memberships by providing their IDs.
	 *
	 * @throws AnonymizationException
	 */
	public function anonymizeWithIds( int ...$membershipIds ): void;
}
