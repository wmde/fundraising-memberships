<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Data;

use DateTime;
use WMDE\EmailAddress\EmailAddress;
use WMDE\Euro\Euro;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication as DoctrineMembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Model\Applicant;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantAddress;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantName;
use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Model\Payment;
use WMDE\Fundraising\MembershipContext\Domain\Model\PhoneNumber;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalData;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;

/**
 * newDomainEntity and newDoctrineEntity return equivalent objects.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ValidMembershipApplication {

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
	public const APPLICANT_PHONE_NUMBER = '1337-1337-1337';

	public const MEMBERSHIP_TYPE = MembershipApplication::SUSTAINING_MEMBERSHIP;
	public const PAYMENT_TYPE_PAYPAL = 'PPL';
	public const PAYMENT_TYPE_DIRECT_DEBIT = 'BEZ';
	public const PAYMENT_PERIOD_IN_MONTHS = 3;
	public const PAYMENT_AMOUNT_IN_EURO = 10;
	public const COMPANY_PAYMENT_AMOUNT_IN_EURO = 25;
	public const TOO_HIGH_QUARTERLY_PAYMENT_AMOUNT_IN_EURO = 250.1;
	public const TOO_HIGH_YEARLY_PAYMENT_AMOUNT_IN_EURO = 1000.1;

	public const PAYMENT_BANK_ACCOUNT = '0648489890';
	public const PAYMENT_BANK_CODE = '50010517';
	public const PAYMENT_BANK_NAME = 'ING-DiBa';
	public const PAYMENT_BIC = 'INGDDEFFXXX';
	public const PAYMENT_IBAN = 'DE12500105170648489890';

	public const TEMPLATE_CAMPAIGN = 'test161012';
	public const TEMPLATE_NAME = 'Some_Membership_Form_Template.twig';
	public const FIRST_PAYMENT_DATE = '2021-02-01';

	public const OPTS_INTO_DONATION_RECEIPT = true;
	public const INCENTIVE_NAME = 'eternal_thankfulness';

	public static function newDomainEntity(): MembershipApplication {
		$self = ( new self() );
		return new MembershipApplication(
			null,
			self::MEMBERSHIP_TYPE,
			$self->newApplicant( $self->newPersonApplicantName() ),
			$self->newPayment(),
			self::OPTS_INTO_DONATION_RECEIPT
		);
	}

	public static function newAutoConfirmedDomainEntity(): MembershipApplication {
		$application = self::newDomainEntity();
		$application->confirm();
		return $application;
	}

	public static function newCompanyApplication(): MembershipApplication {
		$self = ( new self() );
		return new MembershipApplication(
			null,
			self::MEMBERSHIP_TYPE,
			$self->newApplicant( $self->newCompanyApplicantName() ),
			$self->newPaymentWithHighAmount( self::PAYMENT_PERIOD_IN_MONTHS, self::COMPANY_PAYMENT_AMOUNT_IN_EURO ),
			self::OPTS_INTO_DONATION_RECEIPT
		);
	}

	public static function newApplicationWithTooHighQuarterlyAmount(): MembershipApplication {
		$self = ( new self() );
		return new MembershipApplication(
			null,
			self::MEMBERSHIP_TYPE,
			$self->newApplicant( $self->newPersonApplicantName() ),
			$self->newPaymentWithHighAmount( self::PAYMENT_PERIOD_IN_MONTHS, self::TOO_HIGH_QUARTERLY_PAYMENT_AMOUNT_IN_EURO ),
			self::OPTS_INTO_DONATION_RECEIPT
		);
	}

	public static function newApplicationWithTooHighYearlyAmount(): MembershipApplication {
		$self = ( new self() );
		return new MembershipApplication(
			null,
			self::MEMBERSHIP_TYPE,
			$self->newApplicant( $self->newPersonApplicantName() ),
			$self->newPaymentWithHighAmount( 12, self::TOO_HIGH_YEARLY_PAYMENT_AMOUNT_IN_EURO ),
			self::OPTS_INTO_DONATION_RECEIPT
		);
	}

	public static function newDomainEntityUsingPayPal( PayPalData $payPalData = null ): MembershipApplication {
		return ( new self() )->createApplicationUsingPayPal( $payPalData );
	}

	public static function newConfirmedSubscriptionDomainEntity(): MembershipApplication {
		$self = ( new self() );

		$payPalData = ( new PayPalData() )
			->setSubscriberId( 'subscription_id' );

		$application = new MembershipApplication(
			null,
			self::MEMBERSHIP_TYPE,
			$self->newApplicant( $self->newPersonApplicantName() ),
			$self->newPayPalPayment( $payPalData ),
			self::OPTS_INTO_DONATION_RECEIPT
		);
		$application->confirm();

		return $application;
	}

	private function newApplicant( ApplicantName $name ): Applicant {
		return new Applicant(
			$name,
			$this->newAddress(),
			new EmailAddress( self::APPLICANT_EMAIL_ADDRESS ),
			new PhoneNumber( self::APPLICANT_PHONE_NUMBER ),
			new \DateTime( self::APPLICANT_DATE_OF_BIRTH )
		);
	}

	public static function newDomainEntityWithEmailAddress( string $emailAddress ): MembershipApplication {
		$self = ( new self() );
		return new MembershipApplication(
			null,
			self::MEMBERSHIP_TYPE,
			$self->newApplicantWithEmailAddress( $self->newPersonApplicantName(), $emailAddress ),
			$self->newPayment(),
			self::OPTS_INTO_DONATION_RECEIPT
		);
	}

	public static function newApplicationWithIncentives(): MembershipApplication {
		$self = ( new self() );
		$application = new MembershipApplication(
			null,
			self::MEMBERSHIP_TYPE,
			$self->newApplicant( $self->newPersonApplicantName() ),
			$self->newPayment(),
			self::OPTS_INTO_DONATION_RECEIPT
		);
		$application->addIncentive( self::newIncentive() );
		return $application;
	}

	private function newApplicantWithEmailAddress( ApplicantName $name, string $emailAddress ): Applicant {
		return new Applicant(
			$name,
			$this->newAddress(),
			new EmailAddress( $emailAddress ),
			new PhoneNumber( self::APPLICANT_PHONE_NUMBER ),
			new \DateTime( self::APPLICANT_DATE_OF_BIRTH )
		);
	}

	private function createApplicationUsingPayPal( PayPalData $payPalData = null ): MembershipApplication {
		$self = ( new self() );
		return new MembershipApplication(
			null,
			self::MEMBERSHIP_TYPE,
			$self->newApplicant( $self->newPersonApplicantName() ),
			$this->newPayPalPayment( $payPalData ),
			self::OPTS_INTO_DONATION_RECEIPT
		);
	}

	private function newPersonApplicantName(): ApplicantName {
		$personName = ApplicantName::newPrivatePersonName();

		$personName->setFirstName( self::APPLICANT_FIRST_NAME );
		$personName->setLastName( self::APPLICANT_LAST_NAME );
		$personName->setSalutation( self::APPLICANT_SALUTATION );
		$personName->setTitle( self::APPLICANT_TITLE );

		return $personName->freeze()->assertNoNullFields();
	}

	private function newCompanyApplicantName(): ApplicantName {
		$companyName = ApplicantName::newCompanyName();
		$companyName->setCompanyName( self::APPLICANT_COMPANY_NAME );

		return $companyName->freeze()->assertNoNullFields();
	}

	private function newAddress(): ApplicantAddress {
		$address = new ApplicantAddress();

		$address->setCity( self::APPLICANT_CITY );
		$address->setCountryCode( self::APPLICANT_COUNTRY_CODE );
		$address->setPostalCode( self::APPLICANT_POSTAL_CODE );
		$address->setStreetAddress( self::APPLICANT_STREET_ADDRESS );

		return $address->freeze()->assertNoNullFields();
	}

	private function newPayment(): Payment {
		return new Payment(
			self::PAYMENT_PERIOD_IN_MONTHS,
			Euro::newFromFloat( self::PAYMENT_AMOUNT_IN_EURO ),
			$this->newDirectDebitPayment( $this->newBankData() )
		);
	}

	private function newPaymentWithHighAmount( int $periodInMonths, float $amount ): Payment {
		return new Payment(
			$periodInMonths,
			Euro::newFromFloat( $amount ),
			$this->newDirectDebitPayment( $this->newBankData() )
		);
	}

	private function newDirectDebitPayment( BankData $bankData ): DirectDebitPayment {
		return new DirectDebitPayment( $bankData );
	}

	private function newPayPalPayment( PayPalData $payPalData = null ): Payment {
		return new Payment(
			self::PAYMENT_PERIOD_IN_MONTHS,
			Euro::newFromFloat( self::PAYMENT_AMOUNT_IN_EURO ),
			new PayPalPayment( $payPalData ?: $this->newPayPalData() )
		);
	}

	private function newPayPalData(): PayPalData {
		$payPalData = new PayPalData();
		$payPalData->setFirstPaymentDate( self::FIRST_PAYMENT_DATE );
		return $payPalData;
	}

	private function newBankData(): BankData {
		$bankData = new BankData();

		$bankData->setAccount( self::PAYMENT_BANK_ACCOUNT );
		$bankData->setBankCode( self::PAYMENT_BANK_CODE );
		$bankData->setBankName( self::PAYMENT_BANK_NAME );
		$bankData->setBic( self::PAYMENT_BIC );
		$bankData->setIban( new Iban( self::PAYMENT_IBAN ) );

		return $bankData->freeze()->assertNoNullFields();
	}

	public static function newDoctrineEntity(): DoctrineMembershipApplication {
		$application = self::createDoctrineApplicationWithoutApplicantName();

		$application->setApplicantFirstName( self::APPLICANT_FIRST_NAME );
		$application->setApplicantLastName( self::APPLICANT_LAST_NAME );
		$application->setApplicantSalutation( self::APPLICANT_SALUTATION );
		$application->setApplicantTitle( self::APPLICANT_TITLE );
		$application->setDonationReceipt( self::OPTS_INTO_DONATION_RECEIPT );

		return $application;
	}

	private static function createDoctrineApplicationWithoutApplicantName(): DoctrineMembershipApplication {
		$application = new DoctrineMembershipApplication();

		$application->setStatus( DoctrineMembershipApplication::STATUS_CONFIRMED );

		$application->setCity( self::APPLICANT_CITY );
		$application->setCountry( self::APPLICANT_COUNTRY_CODE );
		$application->setPostcode( self::APPLICANT_POSTAL_CODE );
		$application->setAddress( self::APPLICANT_STREET_ADDRESS );

		$application->setApplicantEmailAddress( self::APPLICANT_EMAIL_ADDRESS );
		$application->setApplicantPhoneNumber( self::APPLICANT_PHONE_NUMBER );
		$application->setApplicantDateOfBirth( new \DateTime( self::APPLICANT_DATE_OF_BIRTH ) );

		$application->setMembershipType( self::MEMBERSHIP_TYPE );
		$application->setPaymentType( self::PAYMENT_TYPE_DIRECT_DEBIT );
		$application->setPaymentIntervalInMonths( self::PAYMENT_PERIOD_IN_MONTHS );
		$application->setPaymentAmount( self::PAYMENT_AMOUNT_IN_EURO );

		$application->setPaymentBankAccount( self::PAYMENT_BANK_ACCOUNT );
		$application->setPaymentBankCode( self::PAYMENT_BANK_CODE );
		$application->setPaymentBankName( self::PAYMENT_BANK_NAME );
		$application->setPaymentBic( self::PAYMENT_BIC );
		$application->setPaymentIban( self::PAYMENT_IBAN );

		return $application;
	}

	public static function newDoctrineCompanyEntity(): DoctrineMembershipApplication {
		$application = self::createDoctrineApplicationWithoutApplicantName();

		$application->setCompany( self::APPLICANT_COMPANY_NAME );
		$application->setApplicantTitle( '' );
		$application->setApplicantSalutation( self::APPLICANT_SALUTATION_COMPANY );
		$application->setPaymentAmount( self::COMPANY_PAYMENT_AMOUNT_IN_EURO );
		$application->setDonationReceipt( self::OPTS_INTO_DONATION_RECEIPT );

		return $application;
	}

	public static function newAnonymizedDoctrineEntity(): DoctrineMembershipApplication {
		$application = self::newDoctrineEntity();
		$application->setBackup( new DateTime() );
		return $application;
	}

	public static function newIncentive(): Incentive {
		return new Incentive( self::INCENTIVE_NAME );
	}

}
