<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Repositories;

/**
 * @license GPL-2.0-or-later
 */
class ApplicationAnonymizedException extends GetMembershipApplicationException {

	public function __construct() {
		parent::__construct( 'Tried to access an anonymized Application' );
	}

}
