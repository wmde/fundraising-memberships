<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Infrastructure;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;

interface MembershipNotifier {

	public function sendMailFor( MembershipApplication $application ): void;

}
