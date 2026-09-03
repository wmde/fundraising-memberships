<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain;

/**
 * Anonymize personal data in fee changes
 */
interface FeeChangeAnonymizer {

	/**
	 * Run this after exporting the filled fee changes and turning off the system
	 *
	 * @return int
	 * @throws AnonymizationException
	 */
	public function anonymizeAll(): int;

	/**
	 * Anonymize fee changes by providing their UUIDs.
	 *
	 * @throws AnonymizationException
	 */
	public function anonymizeWithIds( string ...$feeChangeUuids ): void;
}
