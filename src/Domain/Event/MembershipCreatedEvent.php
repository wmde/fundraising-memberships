<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Event;

use WMDE\Fundraising\MembershipContext\Domain\Event;
use WMDE\Fundraising\MembershipContext\Domain\Model\Applicant;

class MembershipCreatedEvent implements Event {

	private int $membershipId;
	private Applicant $applicant;

	public function __construct( int $membershipId, Applicant $applicant ) {
		$this->membershipId = $membershipId;
		$this->applicant = $applicant;
	}

	public function getMembershipId(): int {
		return $this->membershipId;
	}
	public function getApplicant(): Applicant {
		return $this->applicant;
	}
}
