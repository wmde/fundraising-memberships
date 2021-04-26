<?php

namespace WMDE\Fundraising\MembershipContext\DataAccess\LegacyConverters;

use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;

interface IncentiveFinder {
	public function findIncentiveByName( string $name ): ?Incentive;
}
