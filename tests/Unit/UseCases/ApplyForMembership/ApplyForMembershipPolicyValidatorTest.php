<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\ApplyForMembership;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipPolicyValidator;
use WMDE\FunValidators\Validators\TextPolicyValidator;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipPolicyValidator
 */
class ApplyForMembershipPolicyValidatorTest extends TestCase {

	public function testGivenQuarterlyAmountTooHigh_MembershipApplicationNeedsModeration(): void {
		$this->markTestIncomplete( 'This should work when we changed the amount field in request to int and removed the error' );
		$textPolicyValidator = $this->newSucceedingTextPolicyValidator();
		$policyValidator = new ApplyForMembershipPolicyValidator( $textPolicyValidator );
		$this->assertTrue( $policyValidator->needsModeration(
			ValidMembershipApplication::newApplicationWithTooHighQuarterlyAmount()
		) );
	}

	private function newSucceedingTextPolicyValidator(): TextPolicyValidator {
		$this->markTestIncomplete( 'This should work when we changed the amount field in request to int and removed the error' );
		$textPolicyValidator = $this->createMock( TextPolicyValidator::class );
		$textPolicyValidator->method( 'textIsHarmless' )->willReturn( true );
		return $textPolicyValidator;
	}

	public function testGivenYearlyAmountTooHigh_MembershipApplicationNeedsModeration(): void {
		$textPolicyValidator = $this->newSucceedingTextPolicyValidator();
		$policyValidator = new ApplyForMembershipPolicyValidator( $textPolicyValidator );
		$this->assertTrue( $policyValidator->needsModeration(
			ValidMembershipApplication::newApplicationWithTooHighYearlyAmount()
		) );
	}

	public function testFailingTextPolicyValidation_MembershipApplicationNeedsModeration(): void {
		$this->markTestIncomplete( 'This should work when we changed the amount field in request to int and removed the error' );
		$textPolicyValidator = $this->createMock( TextPolicyValidator::class );
		$textPolicyValidator->method( 'textIsHarmless' )->willReturn( false );
		$policyValidator = new ApplyForMembershipPolicyValidator( $textPolicyValidator );
		$this->assertTrue( $policyValidator->needsModeration(
			ValidMembershipApplication::newDomainEntity()
		) );
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

	private function newPolicyValidatorWithEmailBlacklist(): ApplyForMembershipPolicyValidator {
		$textPolicyValidator = $this->newSucceedingTextPolicyValidator();
		$policyValidator = new ApplyForMembershipPolicyValidator(
			$textPolicyValidator,
			[ '/^foo@bar\.baz$/', '/@example.com$/i' ]
		);

		return $policyValidator;
	}

}
