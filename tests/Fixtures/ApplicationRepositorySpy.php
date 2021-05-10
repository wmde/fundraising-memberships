<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Fixtures;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class ApplicationRepositorySpy extends FakeApplicationRepository {

	private $storeApplicationCalls = [];
	private $getApplicationCalls = [];

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
	public function getApplicationById( int $id ): ?MembershipApplication {
		$this->getApplicationCalls[] = $id;
		return parent::getApplicationById( $id );
	}

	/**
	 * @return int[]
	 */
	public function getGetApplicationCalls(): array {
		return $this->getApplicationCalls;
	}

}
