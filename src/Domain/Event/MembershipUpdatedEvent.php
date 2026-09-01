<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Event;

use WMDE\Fundraising\MembershipContext\Domain\Event;
use WMDE\Fundraising\MembershipContext\Domain\Model\Applicant;

class MembershipUpdatedEvent implements Event {

	public function __construct(
		private readonly int $membershipId,
		private readonly Applicant $previousApplicant,
		private readonly Applicant $newApplicant
	) {
	}

	public function getMembershipId(): int {
		return $this->membershipId;
	}

	public function getPreviousApplicant(): Applicant {
		return $this->previousApplicant;
	}

	public function getNewApplicant(): Applicant {
		return $this->newApplicant;
	}
}
