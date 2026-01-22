<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\TestDoubles;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\MembershipRepository;

class InMemoryMembershipRepository implements MembershipRepository {

	/**
	 * @var MembershipApplication[]
	 */
	private array $applications = [];

	public function storeApplication( MembershipApplication $application ): void {
		$this->applications[$application->getId()] = $application;
	}

	public function getMembershipApplicationById( int $id ): ?MembershipApplication {
		return array_key_exists( $id, $this->applications ) ? $this->applications[$id] : null;
	}

	public function getUnexportedAndUnscrubbedMembershipApplicationById( int $id ): ?MembershipApplication {
		return $this->getMembershipApplicationById( $id );
	}
}
