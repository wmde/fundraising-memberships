<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Model;

enum FeeChangeState: string {
	case NEW = 'NEW';
	case FILLED = 'FILLED';
	case EXPORTED = 'EXPORTED';
}
