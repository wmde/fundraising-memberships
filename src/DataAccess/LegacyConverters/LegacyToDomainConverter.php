<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\LegacyConverters;

use WMDE\EmailAddress\EmailAddress;
use WMDE\Euro\Euro;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication as DoctrineApplication;
use WMDE\Fundraising\MembershipContext\Domain\Model\Applicant;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantAddress;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantName;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Model\Payment;
use WMDE\Fundraising\MembershipContext\Domain\Model\PhoneNumber;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentMethod;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalData;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;

class LegacyToDomainConverter {
	public function createFromLegacyObject( DoctrineApplication $doctrineApplication ): MembershipApplication {
		$application = new MembershipApplication(
			$doctrineApplication->getId(),
			$doctrineApplication->getMembershipType(),
			new Applicant(
				$this->newPersonName( $doctrineApplication ),
				$this->newAddress( $doctrineApplication ),
				new EmailAddress( $doctrineApplication->getApplicantEmailAddress() ),
				new PhoneNumber( $doctrineApplication->getApplicantPhoneNumber() ),
				$doctrineApplication->getApplicantDateOfBirth()
			),
			new Payment(
				$doctrineApplication->getPaymentIntervalInMonths(),
				Euro::newFromFloat( $doctrineApplication->getPaymentAmount() ),
				$this->newPaymentMethod( $doctrineApplication )
			),
			$doctrineApplication->getDonationReceipt()
		);

		if ( !$doctrineApplication->getModerationReasons()->isEmpty() ) {
			$application->markForModeration( ...$doctrineApplication->getModerationReasons()->toArray() );
		}

		/*if ( $doctrineApplication->needsModeration() ) {
			$application->markForModeration();
		}*/

		if ( $doctrineApplication->isCancelled() ) {
			$application->cancel();
		}

		if ( $doctrineApplication->getExport() != null ) {
			$application->setExported();
		}

		foreach ( $doctrineApplication->getIncentives() as $incentive ) {
			$application->addIncentive( $incentive );
		}
		return $application;
	}

	private function newPersonName( DoctrineApplication $application ): ApplicantName {
		if ( empty( $application->getCompany() ) ) {
			$personName = ApplicantName::newPrivatePersonName();
			$personName->setFirstName( $application->getApplicantFirstName() );
			$personName->setLastName( $application->getApplicantLastName() );
			$personName->setSalutation( $application->getApplicantSalutation() );
			$personName->setTitle( $application->getApplicantTitle() );
		} else {
			$personName = ApplicantName::newCompanyName();
			$personName->setCompanyName( $application->getCompany() );
			$personName->setSalutation( $application->getApplicantSalutation() );
		}

		return $personName->freeze()->assertNoNullFields();
	}

	private function newAddress( DoctrineApplication $application ): ApplicantAddress {
		$address = new ApplicantAddress();

		$address->setCity( $application->getCity() );
		$address->setCountryCode( $application->getCountry() );
		$address->setPostalCode( $application->getPostcode() );
		$address->setStreetAddress( $application->getAddress() );

		return $address->freeze()->assertNoNullFields();
	}

	private function newPaymentMethod( DoctrineApplication $application ): PaymentMethod {
		if ( $application->getPaymentType() === PaymentMethod::DIRECT_DEBIT ) {
			return new DirectDebitPayment( $this->newBankData( $application ) );
		}

		if ( $application->getPaymentType() === PaymentMethod::PAYPAL ) {
			return new PayPalPayment( $this->newPayPalData( $application ) );
		}

		throw new \InvalidArgumentException( 'Unsupported payment type' );
	}

	private function newBankData( DoctrineApplication $application ): BankData {
		$bankData = new BankData();

		$bankData->setAccount( $application->getPaymentBankAccount() );
		$bankData->setBankCode( $application->getPaymentBankCode() );
		$bankData->setBankName( $application->getPaymentBankName() );
		$bankData->setBic( $application->getPaymentBic() );
		$bankData->setIban( new Iban( $application->getPaymentIban() ) );

		return $bankData->freeze()->assertNoNullFields();
	}

	private function newPayPalData( DoctrineApplication $application ): PayPalData {
		$data = $application->getDecodedData();

		return ( new PayPalData() )
			->setPayerId( $data['paypal_payer_id'] ?? '' )
			->setSubscriberId( $data['paypal_subscr_id'] ?? '' )
			->setPayerStatus( $data['paypal_payer_status'] ?? '' )
			->setAddressStatus( $data['paypal_address_status'] ?? '' )
			->setAmount( Euro::newFromString( $data['paypal_mc_gross'] ?? '0' ) )
			->setCurrencyCode( $data['paypal_mc_currency'] ?? '' )
			->setFee( Euro::newFromString( $data['paypal_mc_fee'] ?? '0' ) )
			->setSettleAmount( Euro::newFromString( $data['paypal_settle_amount'] ?? '0' ) )
			->setFirstName( $data['paypal_first_name'] ?? '' )
			->setLastName( $data['paypal_last_name'] ?? '' )
			->setAddressName( $data['paypal_address_name'] ?? '' )
			->setPaymentId( $data['ext_payment_id'] ?? '' )
			->setPaymentType( $data['ext_payment_type'] ?? '' )
			->setPaymentStatus( $data['ext_payment_status'] ?? '' )
			->setPaymentTimestamp( $data['ext_payment_timestamp'] ?? '' )
			->setFirstPaymentDate( $data['first_payment_date'] ?? '' )
			->freeze()->assertNoNullFields();
	}
}
