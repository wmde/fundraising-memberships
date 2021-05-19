<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\LegacyConverters;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication as DoctrineApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\LegacyConverters\DomainToLegacyConverter;
use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalData;

/**
 * @covers \WMDE\Fundraising\MembershipContext\DataAccess\LegacyConverters\DomainToLegacyConverter
 */
class DomainToLegacyConverterTest extends TestCase {

	public function testWhenPersistingApplicationWithModerationFlag_doctrineApplicationHasFlag(): void {
		$doctrineApplication = new DoctrineApplication();
		$application = ValidMembershipApplication::newDomainEntity();
		$application->markForModeration();

		$converter = new DomainToLegacyConverter();
		$converter->convert( $doctrineApplication, $application );

		$this->assertTrue( $doctrineApplication->needsModeration() );
		$this->assertFalse( $doctrineApplication->isCancelled() );
		$this->assertFalse( $doctrineApplication->isConfirmed() );
	}

	public function testWhenPersistingApplicationWithCancelledFlag_doctrineApplicationHasFlag(): void {
		$doctrineApplication = new DoctrineApplication();
		$application = ValidMembershipApplication::newDomainEntity();
		$application->cancel();

		$converter = new DomainToLegacyConverter();
		$converter->convert( $doctrineApplication, $application );

		$this->assertTrue( $doctrineApplication->isCancelled() );
		$this->assertFalse( $doctrineApplication->needsModeration() );
		$this->assertFalse( $doctrineApplication->isConfirmed() );
	}

	public function testWhenPersistingApplicationWithConfirmedFlag_doctrineApplicationHasFlag(): void {
		$doctrineApplication = new DoctrineApplication();
		// Direct debit payments are auto-confirmed
		$application = ValidMembershipApplication::newDomainEntity();

		$converter = new DomainToLegacyConverter();
		$converter->convert( $doctrineApplication, $application );

		$this->assertTrue( $doctrineApplication->isConfirmed() );
		$this->assertFalse( $doctrineApplication->isCancelled() );
		$this->assertFalse( $doctrineApplication->needsModeration() );
	}

	public function testWhenPersistingCancelledModerationApplication_doctrineApplicationHasFlags(): void {
		$doctrineApplication = new DoctrineApplication();
		$application = ValidMembershipApplication::newDomainEntity();
		$application->markForModeration();
		$application->cancel();

		$converter = new DomainToLegacyConverter();
		$converter->convert( $doctrineApplication, $application );

		$this->assertTrue( $doctrineApplication->needsModeration() );
		$this->assertTrue( $doctrineApplication->isCancelled() );
	}

	public function testWhenGivenPaypalDoctrineApplication_setsPaypalFieldsInMembershipApplication() {
		$paypalData = ( new PayPalData() )
			->setPayerId( '42' )
			->setSubscriberId( '43' )
			->setPayerStatus( 'decent' )
			->setAddressStatus( 'also_decent' )
			->setAmount( Euro::newFromString( '1000' ) )
			->setCurrencyCode( 'EUR' )
			->setFee( Euro::newFromString( '22' ) )
			->setSettleAmount( Euro::newFromString( '11' ) )
			->setFirstName( 'Joe' )
			->setLastName( 'Strummer' )
			->setAddressName( 'Joe Strummer' )
			->setPaymentId( '4242' )
			->setPaymentType( 'Payment Type' )
			->setPaymentStatus( 'all good' )
			->setPaymentTimestamp( '1619438201' )
			->setFirstPaymentDate( '1587902201' )
			->freeze()->assertNoNullFields();

		$doctrineApplication = new DoctrineApplication();
		$application = ValidMembershipApplication::newDomainEntityUsingPayPal( $paypalData );

		$converter = new DomainToLegacyConverter();
		$converter->convert( $doctrineApplication, $application );
		$paypalInfo = $doctrineApplication->getDecodedData();

		$this->assertEquals( $paypalData->getPayerId(), $paypalInfo[ 'paypal_payer_id' ] );
		$this->assertEquals( $paypalData->getSubscriberId(), $paypalInfo[ 'paypal_subscr_id' ] );
		$this->assertEquals( $paypalData->getPayerStatus(), $paypalInfo[ 'paypal_payer_status' ] );
		$this->assertEquals( $paypalData->getAddressStatus(), $paypalInfo[ 'paypal_address_status' ] );
		$this->assertEquals( $paypalData->getAmount(), $paypalInfo[ 'paypal_mc_gross' ] );
		$this->assertEquals( $paypalData->getCurrencyCode(), $paypalInfo[ 'paypal_mc_currency' ] );
		$this->assertEquals( $paypalData->getFee(), $paypalInfo[ 'paypal_mc_fee' ] );
		$this->assertEquals( $paypalData->getSettleAmount(), $paypalInfo[ 'paypal_settle_amount' ] );
		$this->assertEquals( $paypalData->getFirstName(), $paypalInfo[ 'paypal_first_name' ] );
		$this->assertEquals( $paypalData->getLastName(), $paypalInfo[ 'paypal_last_name' ] );
		$this->assertEquals( $paypalData->getAddressName(), $paypalInfo[ 'paypal_address_name' ] );
		$this->assertEquals( $paypalData->getPaymentId(), $paypalInfo[ 'ext_payment_id' ] );
		$this->assertEquals( $paypalData->getPaymentStatus(), $paypalInfo[ 'ext_payment_status' ] );
		$this->assertEquals( $paypalData->getPaymentTimestamp(), $paypalInfo[ 'ext_payment_timestamp' ] );
		$this->assertEquals( $paypalData->getFirstPaymentDate(), $paypalInfo[ 'first_payment_date' ] );
	}

	public function testWhenGivenDirectDebitDoctrineApplication_setsDirectDebitFieldsInMembershipApplication() {
		$doctrineApplication = new DoctrineApplication();
		$application = ValidMembershipApplication::newDomainEntity();

		$converter = new DomainToLegacyConverter();
		$converter->convert( $doctrineApplication, $application );

		/** @var \WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment $payment */
		$payment = $application->getPayment()->getPaymentMethod();
		$bankData = $payment->getBankData();

		$this->assertEquals( $bankData->getAccount(), $doctrineApplication->getPaymentBankAccount() );
		$this->assertEquals( $bankData->getBankCode(), $doctrineApplication->getPaymentBankCode() );
		$this->assertEquals( $bankData->getBankName(), $doctrineApplication->getPaymentBankName() );
		$this->assertEquals( $bankData->getBic(), $doctrineApplication->getPaymentBic() );
		$this->assertEquals( $bankData->getIban(), new Iban( $doctrineApplication->getPaymentIban() ) );
	}

	public function testWhenGivenNonModeratedNonCancelledApplicationWithUncompletedExternalPayment_setsNeutralStatus() {
		$doctrineApplication = new DoctrineApplication();
		$application = ValidMembershipApplication::newDomainEntityUsingPayPal( new PayPalData() );

		$converter = new DomainToLegacyConverter();
		$converter->convert( $doctrineApplication, $application );

		$this->assertEquals( DoctrineApplication::STATUS_NEUTRAL, $doctrineApplication->getStatus() );
	}

	public function testGivenApplicationWithIncentives_addsThemToDomainApplication() {
		$incentive = new Incentive( 'PS5 and 3080 GPU and Blue Hearts album on vinyl and Analogue Pocket' );
		$doctrineApplication = new DoctrineApplication();
		$application = ValidMembershipApplication::newDomainEntity();
		$application->addIncentive( $incentive );

		$converter = new DomainToLegacyConverter();
		$converter->convert( $doctrineApplication, $application );

		$doctrineIncentives = $doctrineApplication->getIncentives();

		$this->assertCount( 1, $doctrineIncentives );
		$this->assertEquals( $incentive, $doctrineIncentives->get( 0 ) );
	}
}
