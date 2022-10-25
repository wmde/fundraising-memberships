<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\TestDoubles;

use WMDE\Fundraising\MembershipContext\DataAccess\IncentiveFinder;
use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;

class FailingIncentiveFinder implements IncentiveFinder {

	public function findIncentiveByName( string $name ): ?Incentive {
		return null;
	}
}
