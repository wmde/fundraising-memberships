<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\ApplyForMembership;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationIdentifier;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationReason;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicationValidationResult;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Moderation\ModerationService;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\FunValidators\Validators\TextPolicyValidator;

#[CoversClass( ModerationService::class )]
class ModerationServiceTest extends TestCase {

	public function testGivenQuarterlyAmountTooHigh_MembershipApplicationNeedsModeration(): void {
		$application = ValidMembershipApplication::newApplication();
		$textPolicyValidator = $this->newSucceedingTextPolicyValidator();
		$policyValidator = new ModerationService( $textPolicyValidator );

		$moderationResult = $policyValidator->moderateMembershipApplicationRequest( $application, 25001, PaymentInterval::Quarterly->value );
		$violations = $moderationResult->getViolations();

		$this->assertCount( 1, $violations );
		$this->assertEquals(
			new ModerationReason( ModerationIdentifier::MEMBERSHIP_FEE_TOO_HIGH, ApplicationValidationResult::SOURCE_PAYMENT_AMOUNT ),
			$violations[0]
		);
	}

	private function newSucceedingTextPolicyValidator(): TextPolicyValidator {
		return $this->createConfiguredStub(
			TextPolicyValidator::class,
			[ 'textIsHarmless' => true ]
		);
	}

	public function testGivenYearlyAmountTooHigh_MembershipApplicationNeedsModeration(): void {
		$application = ValidMembershipApplication::newApplication();
		$textPolicyValidator = $this->newSucceedingTextPolicyValidator();
		$policyValidator = new ModerationService( $textPolicyValidator );

		$moderationResult = $policyValidator->moderateMembershipApplicationRequest( $application, 100010, PaymentInterval::Yearly->value );
		$violations = $moderationResult->getViolations();

		$this->assertCount( 1, $violations );
		$this->assertEquals(
			new ModerationReason( ModerationIdentifier::MEMBERSHIP_FEE_TOO_HIGH, ApplicationValidationResult::SOURCE_PAYMENT_AMOUNT ),
			$violations[0]
		);
	}

	public function testFailingTextPolicyValidation_MembershipApplicationNeedsModeration(): void {
		$textPolicyValidator = $this->createConfiguredStub(
			TextPolicyValidator::class,
			[ 'textIsHarmless' => false ]
		);
		$policyValidator = new ModerationService( $textPolicyValidator );

		$moderationResult = $policyValidator->moderateMembershipApplicationRequest( ValidMembershipApplication::newDomainEntity(), 2500, PaymentInterval::Yearly->value );
		$violations = $moderationResult->getViolations();

		// Validator checks 4 fields: first name, last name, street and city
		$this->assertCount( 4, $violations );
		$this->assertEquals(
			new ModerationReason( ModerationIdentifier::ADDRESS_CONTENT_VIOLATION, ApplicationValidationResult::SOURCE_APPLICANT_FIRST_NAME ),
			$violations[0]
		);
	}

	public function testGivenEmailAddressOnBlockList_MembershipApplicationNeedsModeration(): void {
		$application = ValidMembershipApplication::newApplication();
		$textPolicyValidator = $this->newSucceedingTextPolicyValidator();
		$policyValidator = new ModerationService( $textPolicyValidator, [ ValidMembershipApplication::APPLICANT_EMAIL_ADDRESS, 'test@example.com' ] );

		$moderationResult = $policyValidator->moderateMembershipApplicationRequest( $application, 2500, PaymentInterval::Yearly->value );

		$this->assertEquals(
			new ModerationReason( ModerationIdentifier::EMAIL_BLOCKED, ApplicationValidationResult::SOURCE_APPLICANT_EMAIL ),
			$moderationResult->getViolations()[0]
		);
	}
}
