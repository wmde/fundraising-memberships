<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Model;

use WMDE\EmailAddress\EmailAddress;

class AnonymousEmailAddress extends EmailAddress {
	public function __construct() {
		// Don't call the parent constructor on purpose so the validation doesn't get triggered
	}

	public function getUserName(): string {
		return '';
	}

	public function getDomain(): string {
		return '';
	}

	public function getNormalizedDomain(): string {
		return '';
	}

	public function getFullAddress(): string {
		return '';
	}

	public function getNormalizedAddress(): string {
		return '';
	}

	public function __toString(): string {
		return '';
	}
}
