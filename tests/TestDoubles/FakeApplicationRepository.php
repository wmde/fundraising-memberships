<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\TestDoubles;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationAnonymizedException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class FakeApplicationRepository implements ApplicationRepository {

	private $calls = 0;
	private $applications = [];
	private $throwOnRead = false;
	private $throwOnWrite = false;
	private $throwAnonymizedOnRead = false;

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

		if ( $application->getId() === null ) {
			$application->assignId( ++$this->calls );
		}
		$this->applications[$application->getId()] = unserialize( serialize( $application ) );
	}

	public function getApplicationById( int $id ): ?MembershipApplication {
		if ( $this->throwAnonymizedOnRead ) {
			throw new ApplicationAnonymizedException();
		}

		if ( $this->throwOnRead ) {
			throw new GetMembershipApplicationException();
		}

		if ( array_key_exists( $id, $this->applications ) ) {
			return unserialize( serialize( $this->applications[$id] ) );
		}

		return null;
	}

}
