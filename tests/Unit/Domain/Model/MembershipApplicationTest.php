<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationIdentifier;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationReason;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;

/**
 * @covers \WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication
 *
 * @license GPL-2.0-or-later
 */
class MembershipApplicationTest extends TestCase {

	public function testIdIsNullWhenNotAssigned(): void {
		$this->assertNull( ValidMembershipApplication::newDomainEntity()->getId() );
	}

	public function testCanAssignIdToNewDonation(): void {
		$donation = ValidMembershipApplication::newDomainEntity();

		$donation->assignId( 42 );
		$this->assertSame( 42, $donation->getId() );
	}

	public function testCannotAssignIdToDonationWithIdentity(): void {
		$donation = ValidMembershipApplication::newDomainEntity();
		$donation->assignId( 42 );

		$this->expectException( RuntimeException::class );
		$donation->assignId( 43 );
	}

	public function testNewApplicationHasExpectedDefaults(): void {
		$application = ValidMembershipApplication::newDomainEntity();

		$this->assertNull( $application->getId() );
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

		$this->expectException( \LogicException::class );
		$application->cancel();
	}

	public function testGivenExportedApplication_cannotBeCancelled(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->setExported();

		$this->expectException( \LogicException::class );
		$application->cancel();
	}

	public function testMembershipsWithNonBookablePaymentsAreAutomaticallyConfirmed(): void {
		$application = ValidMembershipApplication::newDomainEntity();

		$this->assertTrue( $application->isConfirmed() );
	}

	public function testMembershipsWithUnBookedPaymentsAreNotConfirmed(): void {
		$application = ValidMembershipApplication::newDomainEntityUsingPayPal();

		$this->assertFalse( $application->isConfirmed() );
	}

	public function testMembershipsWithBookedPaymentsAreConfirmed(): void {
		$this->markTestIncomplete( 'This will work again when we update the use case and domain' );
		$application = ValidMembershipApplication::newDomainEntityUsingPayPal( ValidMembershipApplication::newBookedPayPalData() );

		$this->assertTrue( $application->isConfirmed() );
	}

	public function testMembershipsThatAreAlreadyBookedCannotBeBookedAgain(): void {
		$this->markTestIncomplete( 'This will work again when we update the use case and domain' );
		$application = ValidMembershipApplication::newDomainEntityUsingPayPal( ValidMembershipApplication::newBookedPayPalData() );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessageMatches( '/Only unconfirmed membership applications/' );

		$application->confirmSubscriptionCreated( new PayPalData() );
	}

	public function testMembershipsWithNonBookablePaymentsCannotBeBooked(): void {
		$this->markTestIncomplete( 'This will work again when we update the use case and domain' );
		$application = ValidMembershipApplication::newDomainEntity();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessageMatches( '/Only bookable payments can be confirmed as booked/' );

		$application->confirmSubscriptionCreated( new PayPalData() );
	}

	public function testModeratedMembershipsCannotBeBooked(): void {
		$this->markTestIncomplete( 'This will work again when we update the use case and domain' );
		$application = ValidMembershipApplication::newDomainEntityUsingPayPal( ValidMembershipApplication::newPayPalData() );
		$application->markForModeration( $this->makeGenericModerationReason() );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessageMatches( '/Only unconfirmed membership applications/' );

		$application->confirmSubscriptionCreated( ValidMembershipApplication::newBookedPayPalData() );
	}

	private function makeGenericModerationReason(): ModerationReason {
		return new ModerationReason( ModerationIdentifier::MANUALLY_FLAGGED_BY_ADMIN );
	}

	public function testMarkForModerationNeedsAtLeastOneModerationReason(): void {
		$donation = ValidMembershipApplication::newCompanyApplication();
		$this->expectException( \LogicException::class );
		$donation->markForModeration();
	}

}
