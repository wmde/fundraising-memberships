<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Model;

enum ModerationIdentifier {
	case MEMBERSHIP_FEE_TOO_HIGH;
	case ADDRESS_CONTENT_VIOLATION;
	case MANUALLY_FLAGGED_BY_ADMIN;
}
