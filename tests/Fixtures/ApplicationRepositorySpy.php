<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Fixtures;

use WMDE\Fundraising\MembershipContext\Domain\Model\Application;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class ApplicationRepositorySpy extends FakeApplicationRepository {

	private $storeApplicationCalls = [];
	private $getApplicationCalls = [];

	public function __construct( Application ...$applications ) {
		parent::__construct( ...$applications );
		$this->storeApplicationCalls = []; // remove calls coming from initialization
	}

	public function storeApplication( Application $application ): void {
		$this->storeApplicationCalls[] = clone $application; // protect against the application being changed later
		parent::storeApplication( $application );
	}

	/**
	 * @return Application[]
	 */
	public function getStoreApplicationCalls(): array {
		return $this->storeApplicationCalls;
	}

	/**
	 * @param int $id
	 *
	 * @return Application|null
	 * @throws GetMembershipApplicationException
	 */
	public function getApplicationById( int $id ): ?Application {
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
