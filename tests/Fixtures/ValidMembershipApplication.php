<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Fixtures;

use DateTime;
use WMDE\EmailAddress\EmailAddress;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication as DoctrineMembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Model\Applicant;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantAddress;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantName;
use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Model\PhoneNumber;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\PaymentType;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentParameters;

/**
 * newDomainEntity and newDoctrineEntity return equivalent objects.
 */
class ValidMembershipApplication {

	public const DEFAULT_ID = 1;

	public const APPLICANT_FIRST_NAME = 'Potato';
	public const APPLICANT_LAST_NAME = 'The Great';
	public const APPLICANT_SALUTATION = 'Herr';
	public const APPLICANT_SALUTATION_COMPANY = ApplicantName::COMPANY_SALUTATION;
	public const APPLICANT_TITLE = '';
	public const APPLICANT_COMPANY_NAME = 'Evilcrop';

	public const APPLICANT_DATE_OF_BIRTH = '1990-01-01';

	public const APPLICANT_CITY = 'Berlin';
	public const APPLICANT_COUNTRY_CODE = 'DE';
	public const APPLICANT_POSTAL_CODE = '1234';
	public const APPLICANT_STREET_ADDRESS = 'Nyan street';

	public const APPLICANT_EMAIL_ADDRESS = 'jeroendedauw@gmail.com';
	public const APPLICANT_PHONE_NUMBER = '';

	public const MEMBERSHIP_TYPE = MembershipApplication::SUSTAINING_MEMBERSHIP;
	public const PAYMENT_TYPE_PAYPAL = PaymentType::Paypal;
	public const PAYMENT_TYPE_DIRECT_DEBIT = PaymentType::DirectDebit;
	public const PAYMENT_PERIOD_IN_MONTHS = PaymentInterval::Quarterly;
	public const PAYMENT_AMOUNT_IN_EURO = 10;
	public const COMPANY_PAYMENT_AMOUNT_IN_EURO = 25;
	public const TOO_HIGH_QUARTERLY_PAYMENT_AMOUNT_IN_EUROCENTS = 25010;
	public const TOO_HIGH_YEARLY_PAYMENT_AMOUNT_IN_EUROCENTS = 100010;

	public const PAYMENT_ID = 1;

	public const PAYMENT_BANK_ACCOUNT = '0648489890';
	public const PAYMENT_BANK_CODE = '50010517';
	public const PAYMENT_BANK_NAME = 'ING-DiBa';
	public const PAYMENT_BIC = 'INGDDEFFXXX';
	public const PAYMENT_IBAN = 'DE12500105170648489890';

	public const PAYPAL_TRANSACTION_ID = '61E67681CH3238416';
	public const PAYPAL_PAYER_ID = 'HE373U84ENFYQ';

	public const TEMPLATE_CAMPAIGN = 'test161012';
	public const TEMPLATE_NAME = 'Some_Membership_Form_Template.twig';

	public const OPTS_INTO_DONATION_RECEIPT = true;
	public const INCENTIVE_NAME = 'eternal_thankfulness';

	public static function newDomainEntity( int $id = self::DEFAULT_ID ): MembershipApplication {
		$self = ( new self() );
		return new MembershipApplication(
			$id,
			self::MEMBERSHIP_TYPE,
			$self->newApplicant( $self->newPersonApplicantName() ),
			self::PAYMENT_ID,
			self::OPTS_INTO_DONATION_RECEIPT
		);
	}

	public static function newCompanyApplication( int $id = self::DEFAULT_ID ): MembershipApplication {
		$self = ( new self() );
		return new MembershipApplication(
			$id,
			self::MEMBERSHIP_TYPE,
			$self->newCompanyApplicant( $self->newCompanyApplicantName() ),
			self::PAYMENT_ID,
			self::OPTS_INTO_DONATION_RECEIPT
		);
	}

	public static function newApplication( int $id = self::DEFAULT_ID ): MembershipApplication {
		$self = ( new self() );
		return new MembershipApplication(
			$id,
			self::MEMBERSHIP_TYPE,
			$self->newApplicant( $self->newPersonApplicantName() ),
			self::PAYMENT_ID,
			self::OPTS_INTO_DONATION_RECEIPT
		);
	}

	private function newApplicant( ApplicantName $name ): Applicant {
		return new Applicant(
			$name,
			$this->newAddress(),
			new EmailAddress( self::APPLICANT_EMAIL_ADDRESS ),
			new PhoneNumber( self::APPLICANT_PHONE_NUMBER ),
			new DateTime( self::APPLICANT_DATE_OF_BIRTH )
		);
	}

	private function newCompanyApplicant( ApplicantName $name ): Applicant {
		return new Applicant(
			$name,
			$this->newAddress(),
			new EmailAddress( self::APPLICANT_EMAIL_ADDRESS ),
			new PhoneNumber( self::APPLICANT_PHONE_NUMBER )
		);
	}

	public static function newDomainEntityWithEmailAddress( string $emailAddress ): MembershipApplication {
		$self = ( new self() );
		return new MembershipApplication(
			self::DEFAULT_ID,
			self::MEMBERSHIP_TYPE,
			$self->newApplicantWithEmailAddress( $self->newPersonApplicantName(), $emailAddress ),
			self::PAYMENT_ID,
			self::OPTS_INTO_DONATION_RECEIPT
		);
	}

	private function newApplicantWithEmailAddress( ApplicantName $name, string $emailAddress ): Applicant {
		return new Applicant(
			$name,
			$this->newAddress(),
			new EmailAddress( $emailAddress ),
			new PhoneNumber( self::APPLICANT_PHONE_NUMBER ),
			new DateTime( self::APPLICANT_DATE_OF_BIRTH )
		);
	}

	private function newPersonApplicantName(): ApplicantName {
		return ApplicantName::newPrivatePersonName(
			self::APPLICANT_SALUTATION,
			self::APPLICANT_TITLE,
			self::APPLICANT_FIRST_NAME,
			self::APPLICANT_LAST_NAME
		);
	}

	private function newCompanyApplicantName(): ApplicantName {
		return ApplicantName::newCompanyName( self::APPLICANT_COMPANY_NAME );
	}

	private function newAddress(): ApplicantAddress {
		return new ApplicantAddress(
			streetAddress: self::APPLICANT_STREET_ADDRESS,
			postalCode: self::APPLICANT_POSTAL_CODE,
			city: self::APPLICANT_CITY,
			countryCode: self::APPLICANT_COUNTRY_CODE
		);
	}

	public static function newDoctrineEntity( int $id = self::DEFAULT_ID ): DoctrineMembershipApplication {
		$application = self::createDoctrineApplicationWithoutApplicantName( $id );

		$application->setApplicantFirstName( self::APPLICANT_FIRST_NAME );
		$application->setApplicantLastName( self::APPLICANT_LAST_NAME );
		$application->setApplicantSalutation( self::APPLICANT_SALUTATION );
		$application->setApplicantTitle( self::APPLICANT_TITLE );
		$application->setDonationReceipt( self::OPTS_INTO_DONATION_RECEIPT );
		$application->setPaymentId( self::PAYMENT_ID );

		return $application;
	}

	private static function createDoctrineApplicationWithoutApplicantName( int $id = self::DEFAULT_ID ): DoctrineMembershipApplication {
		$application = new DoctrineMembershipApplication();

		$application->setId( $id );
		$application->setStatus( DoctrineMembershipApplication::STATUS_NEUTRAL );

		$application->setCity( self::APPLICANT_CITY );
		$application->setCountry( self::APPLICANT_COUNTRY_CODE );
		$application->setPostcode( self::APPLICANT_POSTAL_CODE );
		$application->setAddress( self::APPLICANT_STREET_ADDRESS );

		$application->setApplicantEmailAddress( self::APPLICANT_EMAIL_ADDRESS );
		$application->setApplicantPhoneNumber( self::APPLICANT_PHONE_NUMBER );
		$application->setApplicantDateOfBirth( new DateTime( self::APPLICANT_DATE_OF_BIRTH ) );

		$application->setMembershipType( self::MEMBERSHIP_TYPE );
		$application->setPaymentType( self::PAYMENT_TYPE_DIRECT_DEBIT->value );
		$application->setPaymentIntervalInMonths( self::PAYMENT_PERIOD_IN_MONTHS->value );
		$application->setPaymentAmount( self::PAYMENT_AMOUNT_IN_EURO );

		$application->setPaymentBankAccount( self::PAYMENT_BANK_ACCOUNT );
		$application->setPaymentBankCode( self::PAYMENT_BANK_CODE );
		$application->setPaymentBankName( self::PAYMENT_BANK_NAME );
		$application->setPaymentBic( self::PAYMENT_BIC );
		$application->setPaymentIban( self::PAYMENT_IBAN );

		return $application;
	}

	public static function newDoctrineCompanyEntity( int $id = self::DEFAULT_ID ): DoctrineMembershipApplication {
		$application = self::createDoctrineApplicationWithoutApplicantName( $id );

		$application->setCompany( self::APPLICANT_COMPANY_NAME );
		$application->setApplicantTitle( '' );
		$application->setApplicantSalutation( self::APPLICANT_SALUTATION_COMPANY );
		$application->setPaymentAmount( self::COMPANY_PAYMENT_AMOUNT_IN_EURO );
		$application->setDonationReceipt( self::OPTS_INTO_DONATION_RECEIPT );
		$application->setPaymentId( self::PAYMENT_ID );

		return $application;
	}

	public static function newBackedUpButUnexportedDoctrineEntity( int $id = self::DEFAULT_ID, ?DateTime $backupTime = null ): DoctrineMembershipApplication {
		$application = self::newDoctrineEntity( $id );
		$application->setBackup( $backupTime ?? new DateTime() );
		return $application;
	}

	public static function newAnonymizedDoctrineEntity( int $id = self::DEFAULT_ID, ?DateTime $backupTime = null ): DoctrineMembershipApplication {
		$application = self::newDoctrineEntity( $id );

		$application->setBackup( $backupTime ?? new DateTime() );
		$application->setExport( $backupTime ?? new DateTime() );
		$application->scrub();

		return $application;
	}

	public static function newIncentive(): Incentive {
		return new Incentive( self::INCENTIVE_NAME );
	}

	public static function newPaymentParameters(): PaymentParameters {
		return new PaymentParameters(
			self::PAYMENT_AMOUNT_IN_EURO,
			self::PAYMENT_PERIOD_IN_MONTHS->value,
			self::PAYMENT_TYPE_DIRECT_DEBIT->value
		);
	}

}
