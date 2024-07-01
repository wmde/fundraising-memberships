<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\TestDoubles;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\MembershipRepository;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;

class InMemoryMembershipRepository implements MembershipRepository {

	/**
	 * @var MembershipApplication[]
	 */
	private array $applications = [];

	/**
	 * @param MembershipApplication $application
	 *
	 * @throws StoreMembershipApplicationException
	 */
	public function storeApplication( MembershipApplication $application ): void {
		$this->applications[$application->getId()] = $application;
	}

	/**
	 * @param int $id
	 *
	 * @return MembershipApplication|null
	 * @throws GetMembershipApplicationException
	 */
	public function getUnexportedMembershipApplicationById( int $id ): ?MembershipApplication {
		return array_key_exists( $id, $this->applications ) ? $this->applications[$id] : null;
	}

}
