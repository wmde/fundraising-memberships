<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\Domain\Model;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationIdentifier;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationReason;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ValidMembershipApplication;

#[CoversClass( MembershipApplication::class )]
class MembershipApplicationTest extends TestCase {

	public function testNewApplicationHasExpectedDefaults(): void {
		$application = ValidMembershipApplication::newDomainEntity();

		$this->assertFalse( $application->isCancelled() );
		$this->assertFalse( $application->isMarkedForModeration() );
	}

	public function testCancellationResultsInCancelledApplication(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->cancel();

		$this->assertTrue( $application->isCancelled() );
	}

	public function testMarkForModerationResultsInApplicationThatNeedsModeration(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->markForModeration( $this->makeGenericModerationReason() );

		$this->assertTrue( $application->isMarkedForModeration() );
	}

	public function testConfirmingTheApplicationSetsItAsConfirmed(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$this->assertFalse( $application->isConfirmed() );

		$application->confirm();

		$this->assertTrue( $application->isConfirmed() );
	}

	public function testDonationReceiptIsSetFromConstructor(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$this->assertTrue( $application->getDonationReceipt() );
	}

	public function testNewApplicationHasNoIncentives(): void {
		$this->assertCount( 0, ValidMembershipApplication::newDomainEntity()->getIncentives() );
	}

	public function testIncentivesCanBeAdded(): void {
		$firstIncentive = new Incentive( 'eternal_gratitude' );
		$secondIncentive = new Incentive( 'good_karma' );
		$thirdIncentive = new Incentive( 'santas_nice_list' );
		$application = ValidMembershipApplication::newDomainEntity();

		$application->addIncentive( $firstIncentive );
		$application->addIncentive( $secondIncentive );
		$application->addIncentive( $thirdIncentive );
		$incentives = iterator_to_array( $application->getIncentives() );

		$this->assertSame( $firstIncentive, $incentives[0] );
		$this->assertSame( $secondIncentive, $incentives[1] );
		$this->assertSame( $thirdIncentive, $incentives[2] );
	}

	public function testGivenCancelledApplication_cannotBeCancelledAgain(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->cancel();

		$this->expectException( LogicException::class );
		$application->cancel();
	}

	public function testGivenExportedApplication_cannotBeCancelled(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->setExported();

		$this->expectException( LogicException::class );
		$application->cancel();
	}

	public function testMarkForModerationNeedsAtLeastOneModerationReason(): void {
		$application = ValidMembershipApplication::newCompanyApplication();
		$this->expectException( LogicException::class );
		$application->markForModeration();
	}

	public function testGivenUnconfirmedApplication_shouldSendConfirmationMailReturnsFalse(): void {
		$application = ValidMembershipApplication::newApplication();

		$this->assertFalse( $application->shouldSendConfirmationMail() );
	}

	public function testGivenConfirmedApplicationWithEmailModeration_shouldSendConfirmationMailReturnsFalse(): void {
		$application = ValidMembershipApplication::newApplication();
		$application->confirm();
		$application->markForModeration( new ModerationReason( ModerationIdentifier::EMAIL_BLOCKED ) );

		$this->assertFalse( $application->shouldSendConfirmationMail() );
	}

	public function testGivenConfirmedApplicationWithOtherModeration_shouldSendConfirmationMailReturnsTrue(): void {
		$application = ValidMembershipApplication::newApplication();
		$application->confirm();
		$application->markForModeration( new ModerationReason( ModerationIdentifier::MANUALLY_FLAGGED_BY_ADMIN ) );

		$this->assertTrue( $application->shouldSendConfirmationMail() );
	}

	private function makeGenericModerationReason(): ModerationReason {
		return new ModerationReason( ModerationIdentifier::MANUALLY_FLAGGED_BY_ADMIN );
	}
}
