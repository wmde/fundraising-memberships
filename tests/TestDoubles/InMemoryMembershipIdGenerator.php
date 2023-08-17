<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\Tests\TestDoubles;

use WMDE\Fundraising\MembershipContext\Domain\Repositories\MembershipIdGenerator;

class InMemoryMembershipIdGenerator implements MembershipIdGenerator {

	private int $lastId;

	public function __construct( int $nextId = 1 ) {
		$this->lastId = $nextId - 1;
	}

	public function generateNewMembershipId(): int {
		return ++$this->lastId;
	}
}
