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
	 * Anonymize memberships with the matching timestamp. This is for nightly batch updates.
	 *
	 * This is for nightly batch updates we use external script (that exports data sets and then updates all donations
	 * with a timestamp that acts as the identifier of the donation batch that should be anonymized).
	 * The timestamp is NOT the export date!
	 *
	 * @param \DateTimeImmutable $timestamp
	 * @return int number of anonymized memberships
	 * @throws AnonymizationException
	 * @deprecated Use {@see self::anonymizeAll()} instead
	 */
	public function anonymizeAt( \DateTimeImmutable $timestamp ): int;

	/**
	 * Anonymize an individual memberships by providing their IDs.
	 *
	 * @throws AnonymizationException
	 */
	public function anonymizeWithIds( int ...$membershipIds ): void;
}
