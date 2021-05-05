<?php

namespace WMDE\Fundraising\MembershipContext\Infrastructure;

interface MembershipApplicationEventLogger {
	public function log( int $membershipApplicationId, string $message ): void;
}
