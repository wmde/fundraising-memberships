<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\TestDoubles;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationAnonymizedException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;

class FakeApplicationRepository implements ApplicationRepository {

	private int $calls = 0;
	/**
	 * @var array<int, MembershipApplication>
	 */
	private array $applications = [];
	private bool $throwOnRead = false;
	private bool $throwOnWrite = false;
	private bool $throwAnonymizedOnRead = false;

	public function __construct( MembershipApplication ...$applications ) {
		foreach ( $applications as $application ) {
			$this->storeApplication( $application );
		}
	}

	public function throwOnRead(): void {
		$this->throwOnRead = true;
	}

	public function throwOnWrite(): void {
		$this->throwOnWrite = true;
	}

	public function throwAnonymizedOnRead(): void {
		$this->throwAnonymizedOnRead = true;
	}

	public function storeApplication( MembershipApplication $application ): void {
		if ( $this->throwOnWrite ) {
			throw new StoreMembershipApplicationException();
		}

		$this->calls++;
		$this->applications[$application->getId()] = clone $application;
	}

	/**
	 * @param int $id
	 *
	 * @return MembershipApplication|null
	 */
	public function getUnexportedMembershipApplicationById( int $id ): ?MembershipApplication {
		if ( $this->throwAnonymizedOnRead ) {
			throw new ApplicationAnonymizedException();
		}

		if ( $this->throwOnRead ) {
			throw new GetMembershipApplicationException();
		}

		if ( array_key_exists( $id, $this->applications ) ) {
			return clone $this->applications[$id];
		}

		return null;
	}

}
