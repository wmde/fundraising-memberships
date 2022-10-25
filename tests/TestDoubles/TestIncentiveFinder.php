<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\TestDoubles;

use WMDE\Fundraising\MembershipContext\DataAccess\IncentiveFinder;
use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;

class TestIncentiveFinder implements IncentiveFinder {

	/**
	 * @var Incentive[]
	 */
	private array $incentives;

	/**
	 * @param Incentive[] $incentives
	 */
	public function __construct( array $incentives ) {
		$this->incentives = $incentives;
	}

	public function findIncentiveByName( string $name ): ?Incentive {
		$incentives = array_filter( $this->incentives, fn( $incentive ) => $incentive->getName() === $name );

		if ( count( $incentives ) === 0 ) {
			return null;
		}

		return reset( $incentives );
	}
}
