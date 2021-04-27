<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Fixtures;

use WMDE\Fundraising\MembershipContext\DataAccess\IncentiveFinder;
use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;

class TestIncentiveFinder implements IncentiveFinder {

	public function findIncentiveByName( string $name ): ?Incentive {
		return new Incentive( $name );
	}
}
