<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Notification;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;

interface MembershipNotifier {

	public function sendConfirmationFor( MembershipApplication $application ): void;

	public function sendModerationNotificationToAdmin( MembershipApplication $application ): void;
}
