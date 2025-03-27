<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Model;

/**
 * This class and its Doctrine mapping are only used in the test environment to quickly create and
 * tear down the last_generated_membership_id table. The production environment uses a migration to set up the table.
 *
 * When setting up a test environment that needs to generate membership IDs in the database,
 * you must insert one MembershipId into the table. The easiest way to accomplish this is to run
 *
 * ```php
 * $entityManager->persist( new MembershipId() );
 * $entityManager->flush();
 * ```
 *
 * @codeCoverageIgnore
 */
class MembershipId {

	/**
	 * used for doctrine mapping only
	 * @phpstan-ignore-next-line
	 */
	private ?int $id = null;

	public function __construct( private readonly int $membershipId = 0 ) {
	}

	public function getId(): ?int {
		return $this->id;
	}

	public function getMembershipId(): int {
		return $this->membershipId;
	}
}
