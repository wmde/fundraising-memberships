<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\Tests\TestDoubles;

use WMDE\EmailAddress\EmailAddress;
use WMDE\Fundraising\MembershipContext\Infrastructure\TemplateMailerInterface;

class TemplateMailerStub implements TemplateMailerInterface {
	public function sendMail( EmailAddress $recipient, array $templateArguments = [] ): void {
		throw new \LogicException( "This mailer is not supposed to send mails!" );
	}

}
