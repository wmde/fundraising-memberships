<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\LegacyConverters;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication as DoctrineApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\LegacyConverters\LegacyToDomainConverter;
use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentMethod;

/**
 * @covers \WMDE\Fundraising\MembershipContext\DataAccess\LegacyConverters\LegacyToDomainConverter
 */
class LegacyToDomainConverterTest extends TestCase {
	public function testGivenDoctrineApplicationWithModerationAndCancelled_domainEntityHasFlags(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setStatus( DoctrineApplication::STATUS_CANCELED + DoctrineApplication::STATUS_MODERATION );

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertTrue( $application->needsModeration() );
		$this->assertTrue( $application->isCancelled() );
	}

	public function testGivenDoctrineApplicationWithModerationFlag_domainEntityHasFlag(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setStatus( DoctrineApplication::STATUS_MODERATION );

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertTrue( $application->needsModeration() );
		$this->assertFalse( $application->isCancelled() );
	}

	public function testGivenDoctrineApplicationWithCancelledFlag_domainEntityHasFlag(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setStatus( DoctrineApplication::STATUS_CANCELED );

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertFalse( $application->needsModeration() );
		$this->assertTrue( $application->isCancelled() );
	}

	public function testGivenDoctrineApplicationWithPaypalPayment_domainEntityHasCorrectPaymentData(): void {
		$paypalInfo = [
			'paypal_payer_id' => '42',
			'paypal_subscr_id' => '43',
			'paypal_payer_status' => 'decent',
			'paypal_address_status' => 'also_decent',
			'paypal_mc_gross' => '1000',
			'paypal_mc_currency' => 'EUR',
			'paypal_mc_fee' => '22',
			'paypal_settle_amount' => '11',
			'paypal_first_name' => 'Joe',
			'paypal_last_name' => 'Strummer',
			'paypal_address_name' => 'Joe Strummer',
			'ext_payment_id' => '4242',
			'ext_payment_status' => 'all good',
			'ext_payment_timestamp' => '1619438201',
			'first_payment_date' => '1587902201',
		];

		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setPaymentType( PaymentMethod::PAYPAL );
		$doctrineApplication->encodeAndSetData( $paypalInfo );

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		/** @var \WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment $payment */
		$payment = $application->getPayment()->getPaymentMethod();
		$paypalData = $payment->getPayPalData();

		$this->assertEquals( $paypalInfo[ 'paypal_payer_id' ], $paypalData->getPayerId() );
		$this->assertEquals( $paypalInfo[ 'paypal_subscr_id' ], $paypalData->getSubscriberId() );
		$this->assertEquals( $paypalInfo[ 'paypal_payer_status' ], $paypalData->getPayerStatus() );
		$this->assertEquals( $paypalInfo[ 'paypal_address_status' ], $paypalData->getAddressStatus() );
		$this->assertEquals( $paypalInfo[ 'paypal_mc_gross' ], $paypalData->getAmount() );
		$this->assertEquals( $paypalInfo[ 'paypal_mc_currency' ], $paypalData->getCurrencyCode() );
		$this->assertEquals( $paypalInfo[ 'paypal_mc_fee' ], $paypalData->getFee() );
		$this->assertEquals( $paypalInfo[ 'paypal_settle_amount' ], $paypalData->getSettleAmount() );
		$this->assertEquals( $paypalInfo[ 'paypal_first_name' ], $paypalData->getFirstName() );
		$this->assertEquals( $paypalInfo[ 'paypal_last_name' ], $paypalData->getLastName() );
		$this->assertEquals( $paypalInfo[ 'paypal_address_name' ], $paypalData->getAddressName() );
		$this->assertEquals( $paypalInfo[ 'ext_payment_id' ], $paypalData->getPaymentId() );
		$this->assertEquals( $paypalInfo[ 'ext_payment_status' ], $paypalData->getPaymentStatus() );
		$this->assertEquals( $paypalInfo[ 'ext_payment_timestamp' ], $paypalData->getPaymentTimestamp() );
		$this->assertEquals( $paypalInfo[ 'first_payment_date' ], $paypalData->getFirstPaymentDate() );
	}

	public function testGivenDoctrineApplicationWithDirectDebitPayment_domainEntityHasCorrectPaymentData(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setPaymentType( PaymentMethod::DIRECT_DEBIT );

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		/** @var \WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment $payment */
		$payment = $application->getPayment()->getPaymentMethod();
		$bankData = $payment->getBankData();

		$this->assertEquals( $doctrineApplication->getPaymentBankAccount(), $bankData->getAccount() );
		$this->assertEquals( $doctrineApplication->getPaymentBankCode(), $bankData->getBankCode() );
		$this->assertEquals( $doctrineApplication->getPaymentBankName(), $bankData->getBankName() );
		$this->assertEquals( $doctrineApplication->getPaymentBic(), $bankData->getBic() );
		$this->assertEquals( new Iban( $doctrineApplication->getPaymentIban() ), $bankData->getIban() );
	}

	public function testGivenUnsupportedPaymentType_throwsException(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setPaymentType( PaymentMethod::SOFORT );

		$this->expectException( \InvalidArgumentException::class );

		$converter = new LegacyToDomainConverter();
		$converter->createFromLegacyObject( $doctrineApplication );
	}

	public function testGivenCompanyDoctrineApplication_setsCompanyFieldsInDomain(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineCompanyEntity();

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertTrue( $application->getApplicant()->isCompany() );
		$this->assertEquals( $doctrineApplication->getCompany(), $application->getApplicant()->getName()->getCompanyName() );
		$this->assertEquals( $doctrineApplication->getApplicantSalutation(), $application->getApplicant()->getName()->getSalutation() );
	}

	public function testGivenPersonDoctrineApplication_setsPersonFieldsInDomain(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertTrue( $application->getApplicant()->isPrivatePerson() );
		$this->assertEquals( $doctrineApplication->getApplicantFirstName(), $application->getApplicant()->getName()->getFirstName() );
		$this->assertEquals( $doctrineApplication->getApplicantLastName(), $application->getApplicant()->getName()->getLastName() );
		$this->assertEquals( $doctrineApplication->getApplicantSalutation(), $application->getApplicant()->getName()->getSalutation() );
		$this->assertEquals( $doctrineApplication->getApplicantTitle(), $application->getApplicant()->getName()->getTitle() );
	}

	public function testGivenDoctrineApplicationThatNeedsModeration_setsNeedsModerationInDomain(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setStatus( DoctrineApplication::STATUS_MODERATION );

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertTrue( $application->needsModeration() );
	}

	public function testGivenConfirmedDoctrineApplication_setsConfirmedInDomain(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setStatus( DoctrineApplication::STATUS_CONFIRMED );

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertTrue( $application->isConfirmed() );
	}

	public function testGivenCancelledDoctrineApplication_setsCancelledInDomain(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setStatus( DoctrineApplication::STATUS_CANCELED );

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertTrue( $application->isCancelled() );
	}

	public function testGivenExportedDoctrineApplication_setsExportedInDomain(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setExport( new \DateTime() );

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertTrue( $application->isExported() );
	}

	public function testDoctrineApplicationWithIncentives_setsIncentivesInDomain(): void {
		$incentives = [ new Incentive( 'gold' ) ];

		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setIncentives( new ArrayCollection( $incentives ) );

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertEquals( $incentives, iterator_to_array( $application->getIncentives() ) );
	}
}
