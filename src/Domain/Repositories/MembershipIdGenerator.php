<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Repositories;

interface MembershipIdGenerator {
	public function generateNewMembershipId(): int;
}
