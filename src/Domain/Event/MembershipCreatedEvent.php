<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Event;

use WMDE\Fundraising\MembershipContext\Domain\Event;
use WMDE\Fundraising\MembershipContext\Domain\Model\Applicant;

class MembershipCreatedEvent implements Event {

	public function __construct(
		private readonly int $membershipId,
		private readonly Applicant $applicant
	) {
	}

	public function getMembershipId(): int {
		return $this->membershipId;
	}

	public function getApplicant(): Applicant {
		return $this->applicant;
	}
}
