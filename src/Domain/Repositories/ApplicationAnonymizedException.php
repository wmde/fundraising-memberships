<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Repositories;

class ApplicationAnonymizedException extends GetMembershipApplicationException {

	public function __construct() {
		parent::__construct( 'Tried to access an anonymized Application' );
	}

}
