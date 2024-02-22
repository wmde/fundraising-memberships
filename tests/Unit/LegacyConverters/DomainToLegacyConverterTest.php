<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\LegacyConverters;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication as DoctrineApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\LegacyConverters\DomainToLegacyConverter;
use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationIdentifier;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationReason;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ValidMembershipApplication;
use WMDE\Fundraising\PaymentContext\Domain\Model\LegacyPaymentData;
use WMDE\Fundraising\PaymentContext\Domain\Model\LegacyPaymentStatus;

/**
 * @covers \WMDE\Fundraising\MembershipContext\DataAccess\LegacyConverters\DomainToLegacyConverter
 */
class DomainToLegacyConverterTest extends TestCase {

	public function testWhenPersistingApplicationWithModerationFlag_doctrineApplicationHasFlag(): void {
		$doctrineApplication = new DoctrineApplication();
		$moderationReasons = [
			$this->makeGenericModerationReason(),
			new ModerationReason( ModerationIdentifier::MEMBERSHIP_FEE_TOO_HIGH )
		];
		$application = ValidMembershipApplication::newDomainEntity();
		$application->markForModeration( ...$moderationReasons );

		$converter = new DomainToLegacyConverter();
		$converter->convert(
			$doctrineApplication,
			$application,
			new LegacyPaymentData( 1, 1, 'UEB', [], LegacyPaymentStatus::EXTERNAL_BOOKED->value ),
			$application->getModerationReasons()
		);

		$this->assertEquals( $moderationReasons, $doctrineApplication->getModerationReasons()->toArray() );
		$this->assertTrue( $doctrineApplication->needsModeration() );
		$this->assertFalse( $doctrineApplication->isCancelled() );
		$this->assertFalse( $doctrineApplication->isConfirmed() );
	}

	private function makeGenericModerationReason(): ModerationReason {
		return new ModerationReason( ModerationIdentifier::MANUALLY_FLAGGED_BY_ADMIN );
	}

	public function testWhenPersistingApplicationWithCancelledFlag_doctrineApplicationHasFlag(): void {
		$doctrineApplication = new DoctrineApplication();
		$application = ValidMembershipApplication::newDomainEntity();
		$application->cancel();

		$converter = new DomainToLegacyConverter();
		$converter->convert(
			$doctrineApplication,
			$application,
			new LegacyPaymentData( 1, 1, 'UEB', [], LegacyPaymentStatus::EXTERNAL_BOOKED->value ),
			[]
		);

		$this->assertTrue( $doctrineApplication->isCancelled() );
		$this->assertFalse( $doctrineApplication->needsModeration() );
		$this->assertFalse( $doctrineApplication->isConfirmed() );
	}

	public function testWhenPersistingApplicationWithConfirmedFlag_doctrineApplicationHasFlag(): void {
		$doctrineApplication = new DoctrineApplication();
		$application = ValidMembershipApplication::newDomainEntity();
		$application->confirm();

		$converter = new DomainToLegacyConverter();
		$converter->convert(
			$doctrineApplication,
			$application,
			new LegacyPaymentData( 1, 1, 'UEB', [], LegacyPaymentStatus::EXTERNAL_BOOKED->value ),
			[]
		);

		$this->assertFalse( $doctrineApplication->isCancelled() );
		$this->assertFalse( $doctrineApplication->needsModeration() );
		$this->assertTrue( $doctrineApplication->isConfirmed() );
	}

	public function testWhenPersistingNonConfirmedOrModeratedOrCancelledApplication_doctrineApplicationHasFlag(): void {
		$doctrineApplication = new DoctrineApplication();
		$application = ValidMembershipApplication::newDomainEntity();

		$converter = new DomainToLegacyConverter();
		$converter->convert(
			$doctrineApplication,
			$application,
			new LegacyPaymentData( 1, 1, 'UEB', [], LegacyPaymentStatus::EXTERNAL_BOOKED->value ),
			[]
		);

		$this->assertFalse( $doctrineApplication->isConfirmed() );
		$this->assertFalse( $doctrineApplication->isCancelled() );
		$this->assertFalse( $doctrineApplication->needsModeration() );
	}

	public function testWhenPersistingCancelledModerationApplication_doctrineApplicationHasFlags(): void {
		$doctrineApplication = new DoctrineApplication();
		$application = ValidMembershipApplication::newDomainEntity();
		$moderationReason = $this->makeGenericModerationReason();
		$application->markForModeration( $moderationReason );
		$application->cancel();

		$converter = new DomainToLegacyConverter();
		$converter->convert(
			$doctrineApplication,
			$application,
			new LegacyPaymentData( 1, 1, 'UEB', [], LegacyPaymentStatus::EXTERNAL_BOOKED->value ),
			[]
		);

		$this->assertEquals( [ $moderationReason ], $doctrineApplication->getModerationReasons()->toArray() );
		$this->assertTrue( $doctrineApplication->needsModeration() );
		$this->assertTrue( $doctrineApplication->isCancelled() );
	}

	public function testWhenGivenPayment_setsInMembershipApplication(): void {
		$doctrineApplication = new DoctrineApplication();
		$application = ValidMembershipApplication::newDomainEntity();

		$paymentData = new LegacyPaymentData( 1, 1, 'UEB', [
			'konto' => 'account',
			'blz' => 'bank code',
			'bankname' => 'bank name',
			'bic' => 'biccybic',
			'iban' => 'ibannabi',
		], LegacyPaymentStatus::EXTERNAL_BOOKED->value );

		$converter = new DomainToLegacyConverter();
		$converter->convert( $doctrineApplication, $application, $paymentData, [] );

		$bankData = $paymentData->paymentSpecificValues;
		$this->assertEquals( $application->getPaymentId(), $doctrineApplication->getPaymentId() );
		$this->assertEquals( $bankData['konto'], $doctrineApplication->getPaymentBankAccount() );
		$this->assertEquals( $bankData['blz'], $doctrineApplication->getPaymentBankCode() );
		$this->assertEquals( $bankData['bankname'], $doctrineApplication->getPaymentBankName() );
		$this->assertEquals( $bankData['bic'], $doctrineApplication->getPaymentBic() );
		$this->assertEquals( $bankData['iban'], $doctrineApplication->getPaymentIban() );
	}

	public function testGivenApplicationWithIncentives_addsThemToDomainApplication(): void {
		$incentive = new Incentive( 'PS5 and 3080 GPU and Blue Hearts album on vinyl and Analogue Pocket' );
		$doctrineApplication = new DoctrineApplication();
		$application = ValidMembershipApplication::newDomainEntity();
		$application->addIncentive( $incentive );

		$converter = new DomainToLegacyConverter();
		$converter->convert(
			$doctrineApplication,
			$application,
			new LegacyPaymentData( 1, 1, 'BEZ', [], LegacyPaymentStatus::EXTERNAL_BOOKED->value ),
			[]
		);

		$doctrineIncentives = $doctrineApplication->getIncentives();

		$this->assertCount( 1, $doctrineIncentives );
		$this->assertEquals( $incentive, $doctrineIncentives->get( 0 ) );
	}
}
