<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\ApplyForMembership;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantAddress;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantName;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tracking\MembershipApplicationTrackingInfo;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipRequest;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\MembershipApplicationBuilder;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\MembershipApplicationBuilder
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MembershipApplicationBuilderTest extends TestCase {

	private const COMPANY_NAME = 'Malenfant asteroid mining';
	private const OMIT_OPTIONAL_FIELDS = true;

	public function testCompanyMembershipRequestGetsBuildCorrectly(): void {
		$request = $this->newCompanyMembershipRequest();

		$application = ( new MembershipApplicationBuilder() )->newApplicationFromRequest( $request );

		$this->assertIsExpectedCompanyPersonName( $application->getApplicant()->getName() );
		$this->assertIsExpectedAddress( $application->getApplicant()->getPhysicalAddress() );

		$this->assertEquals(
			Euro::newFromInt( ValidMembershipApplication::PAYMENT_AMOUNT_IN_EURO ),
			$application->getPayment()->getAmount()
		);

		$this->assertTrue( $application->getDonationReceipt() );
	}

	/**
	 * @param bool $omitOptionalFields
	 * @param string[] $incentives
	 * @return ApplyForMembershipRequest
	 */
	private function newCompanyMembershipRequest( bool $omitOptionalFields = false, array $incentives = [] ): ApplyForMembershipRequest {
		$request = new ApplyForMembershipRequest();

		$request->setMembershipType( ValidMembershipApplication::MEMBERSHIP_TYPE );
		$request->markApplicantAsCompany();
		$request->setApplicantCompanyName( self::COMPANY_NAME );
		$request->setMembershipType( ValidMembershipApplication::MEMBERSHIP_TYPE );
		$request->setApplicantSalutation( '' );
		$request->setApplicantTitle( '' );
		$request->setApplicantFirstName( '' );
		$request->setApplicantLastName( '' );
		$request->setApplicantStreetAddress( ValidMembershipApplication::APPLICANT_STREET_ADDRESS );
		$request->setApplicantPostalCode( ValidMembershipApplication::APPLICANT_POSTAL_CODE );
		$request->setApplicantCity( ValidMembershipApplication::APPLICANT_CITY );
		$request->setApplicantCountryCode( ValidMembershipApplication::APPLICANT_COUNTRY_CODE );
		$request->setApplicantEmailAddress( ValidMembershipApplication::APPLICANT_EMAIL_ADDRESS );
		$request->setPaymentType( ValidMembershipApplication::PAYMENT_TYPE_DIRECT_DEBIT );
		$request->setPaymentIntervalInMonths( ValidMembershipApplication::PAYMENT_PERIOD_IN_MONTHS );
		$request->setPaymentAmountInEuros( Euro::newFromInt( ValidMembershipApplication::PAYMENT_AMOUNT_IN_EURO ) );
		$request->setBankData( $this->newValidBankData() );
		$request->setApplicantPhoneNumber(
			$omitOptionalFields ? '' : ValidMembershipApplication::APPLICANT_PHONE_NUMBER
		);
		$request->setApplicantDateOfBirth(
			$omitOptionalFields ? '' : ValidMembershipApplication::APPLICANT_DATE_OF_BIRTH
		);
		$request->setTrackingInfo( $this->newTrackingInfo() );
		$request->setPiwikTrackingString( 'foo/bar' );
		$request->setOptsIntoDonationReceipt( true );
		$request->setIncentives( $incentives );

		return $request->assertNoNullFields()->freeze();
	}

	private function newValidBankData(): BankData {
		$bankData = new BankData();

		$bankData->setIban( new Iban( ValidMembershipApplication::PAYMENT_IBAN ) );
		$bankData->setBic( ValidMembershipApplication::PAYMENT_BIC );
		$bankData->setAccount( ValidMembershipApplication::PAYMENT_BANK_ACCOUNT );
		$bankData->setBankCode( ValidMembershipApplication::PAYMENT_BANK_CODE );
		$bankData->setBankName( ValidMembershipApplication::PAYMENT_BANK_NAME );

		return $bankData->assertNoNullFields()->freeze();
	}

	private function newTrackingInfo(): MembershipApplicationTrackingInfo {
		return new MembershipApplicationTrackingInfo(
			ValidMembershipApplication::TEMPLATE_CAMPAIGN,
			ValidMembershipApplication::TEMPLATE_NAME
		);
	}

	private function assertIsExpectedCompanyPersonName( ApplicantName $name ): void {
		$this->assertEquals(
			$this->getCompanyPersonName(),
			$name
		);
	}

	private function getCompanyPersonName(): ApplicantName {
		$name = ApplicantName::newCompanyName();

		$name->setCompanyName( self::COMPANY_NAME );
		$name->setSalutation( ApplicantName::COMPANY_SALUTATION );
		$name->setTitle( '' );
		$name->setFirstName( '' );
		$name->setLastName( '' );

		return $name->assertNoNullFields()->freeze();
	}

	private function assertIsExpectedAddress( ApplicantAddress $address ): void {
		$this->assertEquals(
			$this->getPhysicalAddress(),
			$address
		);
	}

	private function getPhysicalAddress(): ApplicantAddress {
		$address = new ApplicantAddress();

		$address->setStreetAddress( ValidMembershipApplication::APPLICANT_STREET_ADDRESS );
		$address->setPostalCode( ValidMembershipApplication::APPLICANT_POSTAL_CODE );
		$address->setCity( ValidMembershipApplication::APPLICANT_CITY );
		$address->setCountryCode( ValidMembershipApplication::APPLICANT_COUNTRY_CODE );

		return $address->assertNoNullFields()->freeze();
	}

	public function testWhenNoBirthDateAndPhoneNumberIsGiven_membershipApplicationIsStillBuiltCorrectly(): void {
		$request = $this->newCompanyMembershipRequest( self::OMIT_OPTIONAL_FIELDS );

		$application = ( new MembershipApplicationBuilder() )->newApplicationFromRequest( $request );

		$this->assertIsExpectedCompanyPersonName( $application->getApplicant()->getName() );
		$this->assertIsExpectedAddress( $application->getApplicant()->getPhysicalAddress() );

		$this->assertEquals(
			Euro::newFromInt( ValidMembershipApplication::PAYMENT_AMOUNT_IN_EURO ),
			$application->getPayment()->getAmount()
		);
	}

	public function testWhenBuildingCompanyApplication_salutationFieldIsSet(): void {
		$request = $this->newCompanyMembershipRequest( self::OMIT_OPTIONAL_FIELDS );

		$application = ( new MembershipApplicationBuilder() )->newApplicationFromRequest( $request );

		$this->assertSame( ApplicantName::COMPANY_SALUTATION, $application->getApplicant()->getName()->getSalutation() );
	}

	public function testWhenBuildingApplicationIncentivesAreSet(): void {
		$request = $this->newCompanyMembershipRequest( self::OMIT_OPTIONAL_FIELDS, [ 'inner_peace', 'a_better_world' ] );

		$application = ( new MembershipApplicationBuilder() )->newApplicationFromRequest( $request );
		$incentives = iterator_to_array( $application->getIncentives() );

		$this->assertCount( 2, $incentives );
	}

}
