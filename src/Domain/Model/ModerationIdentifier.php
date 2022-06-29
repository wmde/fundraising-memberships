<?php
// phpcs:ignoreFile -- Until phpcs has 8.1 enum support, see https://github.com/squizlabs/PHP_CodeSniffer/issues/3479
declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Model;

enum ModerationIdentifier {
	case MEMBERSHIP_FEE_TOO_HIGH;
	case ADDRESS_CONTENT_VIOLATION;
	case MANUALLY_FLAGGED_BY_ADMIN;
}
