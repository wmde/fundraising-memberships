<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Infrastructure;

use WMDE\EmailAddress\EmailAddress;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Notification\ApplyForMembershipTemplateArguments;

interface TemplateMailerInterface {

	/**
	 * @param EmailAddress $recipient The recipient of the email to send
	 * @param ApplyForMembershipTemplateArguments $templateArguments Context parameters to use while rendering the template
	 */
	public function sendMail( EmailAddress $recipient, ApplyForMembershipTemplateArguments $templateArguments ): void;
}
