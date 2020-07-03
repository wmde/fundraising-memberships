<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\ORM\EntityManager;
use Psr\Log\NullLogger;
use WMDE\EmailAddress\EmailAddress;
use WMDE\Euro\Euro;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication as DoctrineApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\Internal\DoctrineApplicationTable;
use WMDE\Fundraising\MembershipContext\Domain\Model\Applicant;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantAddress;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantName;
use WMDE\Fundraising\MembershipContext\Domain\Model\Application;
use WMDE\Fundraising\MembershipContext\Domain\Model\Payment;
use WMDE\Fundraising\MembershipContext\Domain\Model\PhoneNumber;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationAnonymizedException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentMethod;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalData;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DoctrineApplicationRepository implements ApplicationRepository {

	private $table;

	public function __construct( EntityManager $entityManager ) {
		$this->table = new DoctrineApplicationTable( $entityManager, new NullLogger() );
	}

	/**
	 * @throws StoreMembershipApplicationException
	 */
	public function storeApplication( Application $application ): void {
		if ( $application->hasId() ) {
			$this->updateApplication( $application );
		} else {
			$this->insertApplication( $application );
		}
	}

	private function insertApplication( Application $application ): void {
		$doctrineApplication = new DoctrineApplication();
		$this->updateDoctrineApplication( $doctrineApplication, $application );
		$this->table->persistApplication( $doctrineApplication );

		$application->assignId( $doctrineApplication->getId() );
	}

	private function updateApplication( Application $application ): void {
		try {
			$this->table->modifyApplication(
				$application->getId(),
				function ( DoctrineApplication $doctrineApplication ) use ( $application ) {
					$this->updateDoctrineApplication( $doctrineApplication, $application );
				}
			);
		}
		catch ( GetMembershipApplicationException | StoreMembershipApplicationException $ex ) {
			throw new StoreMembershipApplicationException( null, $ex );
		}
	}

	private function updateDoctrineApplication( DoctrineApplication $doctrineApplication, Application $application ): void {
		$doctrineApplication->setId( $application->getId() );
		$doctrineApplication->setMembershipType( $application->getType() );

		$this->setApplicantFields( $doctrineApplication, $application->getApplicant() );
		$this->setPaymentFields( $doctrineApplication, $application->getPayment() );
		$doctrineApplication->setDonationReceipt( $application->getDonationReceipt() );

		$doctrineStatus = $this->getDoctrineStatus( $application );
		$this->preserveDoctrineStatus( $doctrineApplication, $doctrineStatus );
		$doctrineApplication->setStatus( $doctrineStatus );
	}

	private function setApplicantFields( DoctrineApplication $application, Applicant $applicant ): void {
		$application->setApplicantFirstName( $applicant->getName()->getFirstName() );
		$application->setApplicantLastName( $applicant->getName()->getLastName() );
		$application->setApplicantSalutation( $applicant->getName()->getSalutation() );
		$application->setApplicantTitle( $applicant->getName()->getTitle() );
		$application->setCompany( $applicant->getName()->getCompanyName() );

		$application->setApplicantDateOfBirth( $applicant->getDateOfBirth() );

		$application->setApplicantEmailAddress( $applicant->getEmailAddress()->getFullAddress() );
		$application->setApplicantPhoneNumber( $applicant->getPhoneNumber()->__toString() );

		$address = $applicant->getPhysicalAddress();

		$application->setCity( $address->getCity() );
		$application->setCountry( $address->getCountryCode() );
		$application->setPostcode( $address->getPostalCode() );
		$application->setAddress( $address->getStreetAddress() );
	}

	private function setPaymentFields( DoctrineApplication $application, Payment $payment ): void {
		$application->setPaymentIntervalInMonths( $payment->getIntervalInMonths() );
		$application->setPaymentAmount( (int)$payment->getAmount()->getEuroFloat() );
		$paymentMethod = $payment->getPaymentMethod();

		$application->setPaymentType( $paymentMethod->getId() );
		if ( $paymentMethod instanceof DirectDebitPayment ) {
			$this->setBankDataFields( $application, $paymentMethod->getBankData() );
		} elseif ( $paymentMethod instanceof PayPalPayment && $paymentMethod->getPayPalData() != new PayPalData() ) {
			$this->setPayPalDataFields( $application, $paymentMethod->getPayPalData() );
		}
	}

	private function setBankDataFields( DoctrineApplication $application, BankData $bankData ): void {
		$application->setPaymentBankAccount( $bankData->getAccount() );
		$application->setPaymentBankCode( $bankData->getBankCode() );
		$application->setPaymentBankName( $bankData->getBankName() );
		$application->setPaymentBic( $bankData->getBic() );
		$application->setPaymentIban( $bankData->getIban()->toString() );
	}

	private function setPayPalDataFields( DoctrineApplication $application, PayPalData $payPalData ): void {
		$application->encodeAndSetData( array_merge(
			$application->getDecodedData(),
			[
				'paypal_payer_id' => $payPalData->getPayerId(),
				'paypal_subscr_id' => $payPalData->getSubscriberId(),
				'paypal_payer_status' => $payPalData->getPayerStatus(),
				'paypal_address_status' => $payPalData->getAddressStatus(),
				'paypal_mc_gross' => $payPalData->getAmount()->getEuroString(),
				'paypal_mc_currency' => $payPalData->getCurrencyCode(),
				'paypal_mc_fee' => $payPalData->getFee()->getEuroString(),
				'paypal_settle_amount' => $payPalData->getSettleAmount()->getEuroString(),
				'paypal_first_name' => $payPalData->getFirstName(),
				'paypal_last_name' => $payPalData->getLastName(),
				'paypal_address_name' => $payPalData->getAddressName(),
				'ext_payment_id' => $payPalData->getPaymentId(),
				'ext_subscr_id' => $payPalData->getSubscriberId(),
				'ext_payment_type' => $payPalData->getPaymentType(),
				'ext_payment_status' => $payPalData->getPaymentStatus(),
				'ext_payment_account' => $payPalData->getPayerId(),
				'ext_payment_timestamp' => $payPalData->getPaymentTimestamp(),
				'first_payment_date' => $payPalData->getFirstPaymentDate()
			]
		) );
	}

	private function getDoctrineStatus( Application $application ): int {
		$status = DoctrineApplication::STATUS_NEUTRAL;

		if ( $application->needsModeration() ) {
			$status += DoctrineApplication::STATUS_MODERATION;
		}

		if ( $application->isCancelled() ) {
			$status += DoctrineApplication::STATUS_CANCELED;
		}

		if ( $application->isDeleted() ) {
			$status += DoctrineApplication::STATUS_DELETED;
		}

		if ( $application->isConfirmed() || $this->isAutoConfirmed( $status, $application ) ) {
			$status += DoctrineApplication::STATUS_CONFIRMED;
		}

		return $status;
	}

	private function isAutoConfirmed( int $status, Application $application ): bool {
		return $status === DoctrineApplication::STATUS_NEUTRAL && $this->isDirectDebitPayment( $application );
	}

	private function isDirectDebitPayment( Application $application ): bool {
		return $application->getPayment()->getPaymentMethod()->getId() === PaymentMethod::DIRECT_DEBIT;
	}

	private function preserveDoctrineStatus( DoctrineApplication $doctrineApplication, int $doctrineStatus ): void {
		if ( $doctrineStatus < DoctrineApplication::STATUS_CONFIRMED ) {
			$doctrineApplication->modifyDataObject( function ( MembershipApplicationData $data ): void {
				$data->setPreservedStatus( DoctrineApplication::STATUS_CONFIRMED );
			} );
		}
	}

	/**
	 * @param int $id
	 *
	 * @return Application|null
	 * @throws GetMembershipApplicationException
	 */
	public function getApplicationById( int $id ): ?Application {
		$application = $this->table->getApplicationOrNullById( $id );

		if ( $application === null ) {
			return null;
		}

		if ( $application->getBackup() === null ) {
			return $this->newApplicationDomainEntity( $application );
		}

		throw new ApplicationAnonymizedException();
	}

	private function newApplicationDomainEntity( DoctrineApplication $application ): Application {
		return new Application(
			$application->getId(),
			$application->getMembershipType(),
			new Applicant(
				$this->newPersonName( $application ),
				$this->newAddress( $application ),
				new EmailAddress( $application->getApplicantEmailAddress() ),
				new PhoneNumber( $application->getApplicantPhoneNumber() ),
				$application->getApplicantDateOfBirth()
			),
			new Payment(
				$application->getPaymentIntervalInMonths(),
				Euro::newFromFloat( $application->getPaymentAmount() ),
				$this->newPaymentMethod( $application )
			),
			$application->needsModeration(),
			$application->isCancelled(),
			!$application->isUnconfirmed(),
			$application->isDeleted(),
			$application->getDonationReceipt()
		);
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

		throw new \RuntimeException( 'Unsupported payment type' );
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
