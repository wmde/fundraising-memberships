<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\TestDoubles;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;

class ApplicationRepositorySpy extends FakeMembershipRepository {

	/**
	 * @var MembershipApplication[]
	 */
	private array $storeApplicationCalls = [];

	/**
	 * Membership IDs
	 * @var int[]
	 */
	private array $getApplicationCalls = [];

	public function __construct( MembershipApplication ...$applications ) {
		parent::__construct( ...$applications );
		$this->storeApplicationCalls = [];
	}

	public function storeApplication( MembershipApplication $application ): void {
		// protect against the application being changed later
		$this->storeApplicationCalls[] = clone $application;
		parent::storeApplication( $application );
	}

	/**
	 * @return MembershipApplication[]
	 */
	public function getStoreApplicationCalls(): array {
		return $this->storeApplicationCalls;
	}

	/**
	 * @param int $id
	 *
	 * @return MembershipApplication|null
	 * @throws GetMembershipApplicationException
	 */
	public function getUnexportedMembershipApplicationById( int $id ): ?MembershipApplication {
		$this->getApplicationCalls[] = $id;
		return parent::getUnexportedMembershipApplicationById( $id );
	}

	/**
	 * @return int[]
	 */
	public function getGetApplicationCalls(): array {
		return $this->getApplicationCalls;
	}

}
