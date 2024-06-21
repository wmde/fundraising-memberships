<?php

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\ApplyForMembership;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\EmailAddress\EmailAddress;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationIdentifier;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationReason;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\TemplateBasedMailerSpy;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\TemplateMailerStub;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Notification\ApplyForMembershipTemplateArguments;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Notification\MailMembershipApplicationNotifier;
use WMDE\Fundraising\PaymentContext\UseCases\GetPayment\GetPaymentUseCase;

#[CoversClass( MailMembershipApplicationNotifier::class )]
class MailMembershipApplicationNotifierTest extends TestCase {

	private const MEMBERSHIP_APPLICATION_ID = 23;

	public function testBuildValuesForMembership(): void {
		$confirmationSpy = new TemplateBasedMailerSpy( $this );
		$notifier = new MailMembershipApplicationNotifier(
			$confirmationSpy,
			new TemplateMailerStub(),
			$this->makePaymentRetriever(),
			'admin@blabla.de'
		);
		$notifier->sendConfirmationFor( ValidMembershipApplication::newDomainEntity( self::MEMBERSHIP_APPLICATION_ID ) );
		$arguments = new ApplyForMembershipTemplateArguments(
			id:self::MEMBERSHIP_APPLICATION_ID,
			membershipType: 'sustaining',
			membershipFee: '10.00',
			membershipFeeInCents: 1000,
			paymentIntervalInMonths: ValidMembershipApplication::PAYMENT_PERIOD_IN_MONTHS->value,
			paymentType: ValidMembershipApplication::PAYMENT_TYPE_DIRECT_DEBIT->value,
			salutation: ValidMembershipApplication::APPLICANT_SALUTATION,
			title: ValidMembershipApplication::APPLICANT_TITLE,
			lastName: ValidMembershipApplication::APPLICANT_LAST_NAME,
			firstName: ValidMembershipApplication::APPLICANT_FIRST_NAME,
			hasReceiptEnabled: ValidMembershipApplication::OPTS_INTO_DONATION_RECEIPT,
			incentives: [],
			moderationFlags: []
		);

		$confirmationSpy->assertCalledOnceWith( new EmailAddress( ValidMembershipApplication::APPLICANT_EMAIL_ADDRESS ), $arguments );
	}

	public function testRendersMailWithIncentives(): void {
		$confirmationSpy = new TemplateBasedMailerSpy( $this );
		$notifier = new MailMembershipApplicationNotifier(
			$confirmationSpy,
			new TemplateMailerStub(),
			$this->makePaymentRetriever(),
			'admin@blabla.de'
		);
		$application = ValidMembershipApplication::newCompanyApplication();
		$incentive = ValidMembershipApplication::newIncentive();
		$application->addIncentive( $incentive );

		$notifier->sendConfirmationFor( $application );

		$templateArgsFromFirstCall = $confirmationSpy->getTemplateArgumentsFromFirstCall();
		$this->assertEquals( [ ValidMembershipApplication::INCENTIVE_NAME ], $templateArgsFromFirstCall->incentives );
	}

	public function testRendersMailWithModerationFlags(): void {
		$confirmationSpy = new TemplateBasedMailerSpy( $this );
		$notifier = new MailMembershipApplicationNotifier(
			$confirmationSpy,
			new TemplateMailerStub(),
			$this->makePaymentRetriever(),
			'admin@blabla.de'
		);
		$application = ValidMembershipApplication::newCompanyApplication();
		$moderationReasons = [
			new ModerationReason( ModerationIdentifier::MANUALLY_FLAGGED_BY_ADMIN, 'field that does not matter here' ),
			new ModerationReason( ModerationIdentifier::MANUALLY_FLAGGED_BY_ADMIN, 'random other field' ),
			new ModerationReason( ModerationIdentifier::MEMBERSHIP_FEE_TOO_HIGH )
		];
		$application->markForModeration( ...$moderationReasons );

		$notifier->sendConfirmationFor( $application );

		$templateArgsFromFirstCall = $confirmationSpy->getTemplateArgumentsFromFirstCall();
		$this->assertEquals(
			[ 'MANUALLY_FLAGGED_BY_ADMIN' => true, 'MEMBERSHIP_FEE_TOO_HIGH' => true ],
			$templateArgsFromFirstCall->moderationFlags
		);
	}

	private function makePaymentRetriever(): GetPaymentUseCase {
		$mock = $this->createMock( GetPaymentUseCase::class );
		$mock->expects( $this->once() )
			->method( 'getPaymentDataArray' )
			->with( ValidMembershipApplication::PAYMENT_ID )
			->willReturn( [
				'amount' => 1000,
				'interval' => ValidMembershipApplication::PAYMENT_PERIOD_IN_MONTHS->value,
				'paymentType' => ValidMembershipApplication::PAYMENT_TYPE_DIRECT_DEBIT->value
			] );
		return $mock;
	}

}
