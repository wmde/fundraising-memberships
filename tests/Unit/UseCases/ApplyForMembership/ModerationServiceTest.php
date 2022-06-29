<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\ApplyForMembership;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationIdentifier;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationReason;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicationValidationResult;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Moderation\ModerationService;
use WMDE\FunValidators\Validators\TextPolicyValidator;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Moderation\ModerationService
 */
class ModerationServiceTest extends TestCase {

	public function testGivenQuarterlyAmountTooHigh_MembershipApplicationNeedsModeration(): void {
		$tooHighFeeApplication = ValidMembershipApplication::newApplicationWithTooHighQuarterlyAmount();
		$textPolicyValidator = $this->newSucceedingTextPolicyValidator();
		$policyValidator = new ModerationService( $textPolicyValidator );

		$moderationResult = $policyValidator->moderateMembershipApplicationRequest( $tooHighFeeApplication );

		$this->assertEquals(
			new ModerationReason( ModerationIdentifier::MEMBERSHIP_FEE_TOO_HIGH, ApplicationValidationResult::SOURCE_PAYMENT_AMOUNT ),
			$moderationResult->getViolations()[0]
		);
	}

	private function newSucceedingTextPolicyValidator(): TextPolicyValidator {
		$textPolicyValidator = $this->createMock( TextPolicyValidator::class );
		$textPolicyValidator->method( 'textIsHarmless' )->willReturn( true );
		return $textPolicyValidator;
	}

	public function testGivenYearlyAmountTooHigh_MembershipApplicationNeedsModeration(): void {
		$tooHighFeeApplication = ValidMembershipApplication::newApplicationWithTooHighYearlyAmount();
		$textPolicyValidator = $this->newSucceedingTextPolicyValidator();
		$policyValidator = new ModerationService( $textPolicyValidator );

		$moderationResult = $policyValidator->moderateMembershipApplicationRequest( $tooHighFeeApplication );

		$this->assertEquals(
			new ModerationReason( ModerationIdentifier::MEMBERSHIP_FEE_TOO_HIGH, ApplicationValidationResult::SOURCE_PAYMENT_AMOUNT ),
			$moderationResult->getViolations()[0]
		);
	}

	public function testFailingTextPolicyValidation_MembershipApplicationNeedsModeration(): void {
		$textPolicyValidator = $this->createMock( TextPolicyValidator::class );
		$textPolicyValidator->method( 'textIsHarmless' )->willReturn( false );
		$policyValidator = new ModerationService( $textPolicyValidator );

		$moderationResult = $policyValidator->moderateMembershipApplicationRequest( ValidMembershipApplication::newDomainEntity() );

		$this->assertEquals(
			new ModerationReason( ModerationIdentifier::ADDRESS_CONTENT_VIOLATION, ApplicationValidationResult::SOURCE_APPLICANT_FIRST_NAME ),
			$moderationResult->getViolations()[0]
		);
	}

	/** @dataProvider blacklistedEmailAddressProvider */
	public function testWhenEmailAddressIsBlacklisted_isAutoDeletedReturnsTrue( string $emailAddress ): void {
		$policyValidator = $this->newPolicyValidatorWithEmailBlacklist();
		$this->assertTrue(
			$policyValidator->isAutoDeleted(
				ValidMembershipApplication::newDomainEntityWithEmailAddress( $emailAddress )
			)
		);
	}

	public function blacklistedEmailAddressProvider(): array {
		return [
			[ 'foo@bar.baz' ],
			[ 'test@example.com' ],
			[ 'Test@EXAMPLE.com' ]
		];
	}

	/** @dataProvider allowedEmailAddressProvider */
	public function testWhenEmailAddressIsNotBlacklisted_isAutoDeletedReturnsFalse( string $emailAddress ): void {
		$policyValidator = $this->newPolicyValidatorWithEmailBlacklist();
		$this->assertFalse(
			$policyValidator->isAutoDeleted(
				ValidMembershipApplication::newDomainEntityWithEmailAddress( $emailAddress )
			)
		);
	}

	public function allowedEmailAddressProvider(): array {
		return [
			[ 'other.person@bar.baz' ],
			[ 'test@example.computer.says.no' ],
			[ 'some.person@gmail.com' ]
		];
	}

	private function newPolicyValidatorWithEmailBlacklist(): ModerationService {
		$textPolicyValidator = $this->newSucceedingTextPolicyValidator();
		$policyValidator = new ModerationService(
			$textPolicyValidator,
			[ '/^foo@bar\.baz$/', '/@example.com$/i' ]
		);

		return $policyValidator;
	}

}
