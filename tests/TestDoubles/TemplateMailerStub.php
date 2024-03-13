<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\Tests\TestDoubles;

use LogicException;
use WMDE\EmailAddress\EmailAddress;
use WMDE\Fundraising\MembershipContext\Infrastructure\TemplateMailerInterface;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Notification\ApplyForMembershipTemplateArguments;

class TemplateMailerStub implements TemplateMailerInterface {
	public function sendMail( EmailAddress $recipient, ApplyForMembershipTemplateArguments $templateArguments ): void {
		throw new LogicException( "This mailer is not supposed to send mails!" );
	}

}
