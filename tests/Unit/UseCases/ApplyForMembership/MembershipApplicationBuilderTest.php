<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\ApplyForMembership;

use PHPUnit\Framework\TestCase;
use ReflectionObject;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantAddress;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantName;
use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\TestIncentiveFinder;
use WMDE\Fundraising\MembershipContext\Tracking\MembershipApplicationTrackingInfo;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipRequest;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\MembershipApplicationBuilder;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\MembershipApplicationBuilder
 */
class MembershipApplicationBuilderTest extends TestCase {

	private const COMPANY_NAME = 'Malenfant asteroid mining';
	private const OMIT_OPTIONAL_FIELDS = true;

	private const PAYMENT_ID = 1;

	public function testCompanyMembershipRequestGetsBuildCorrectly(): void {
		$request = $this->newCompanyMembershipRequest();

		$testIncentiveFinder = new TestIncentiveFinder( [ new Incentive( 'I AM INCENTIVE' ) ] );
		$application = ( new MembershipApplicationBuilder( $testIncentiveFinder ) )->newApplicationFromRequest( $request, self::PAYMENT_ID );

		$this->assertIsExpectedCompanyPersonName( $application->getApplicant()->getName() );
		$this->assertIsExpectedAddress( $application->getApplicant()->getPhysicalAddress() );

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

		$testIncentiveFinder = new TestIncentiveFinder( [ new Incentive( 'I AM INCENTIVE' ) ] );
		$application = ( new MembershipApplicationBuilder( $testIncentiveFinder ) )->newApplicationFromRequest( $request, self::PAYMENT_ID );

		$this->assertIsExpectedCompanyPersonName( $application->getApplicant()->getName() );
		$this->assertIsExpectedAddress( $application->getApplicant()->getPhysicalAddress() );
	}

	public function testWhenBuildingCompanyApplication_salutationFieldIsSet(): void {
		$request = $this->newCompanyMembershipRequest( self::OMIT_OPTIONAL_FIELDS );

		$testIncentiveFinder = new TestIncentiveFinder( [ new Incentive( 'I AM INCENTIVE' ) ] );
		$application = ( new MembershipApplicationBuilder( $testIncentiveFinder ) )->newApplicationFromRequest( $request, self::PAYMENT_ID );

		$this->assertSame( ApplicantName::COMPANY_SALUTATION, $application->getApplicant()->getName()->getSalutation() );
	}

	public function testWhenBuildingApplicationIncentivesAreSet(): void {
		$incentives = [
			$this->newIncentiveWithNameAndId( 'inner_peace', 1 ),
			$this->newIncentiveWithNameAndId( 'a_better_world', 2 )
		];

		$incentiveFinder = new TestIncentiveFinder( $incentives );

		$request = $this->newCompanyMembershipRequest( self::OMIT_OPTIONAL_FIELDS, array_map(
			fn( $incentive ) => $incentive->getName(),
			$incentives
		) );

		$application = ( new MembershipApplicationBuilder( $incentiveFinder ) )->newApplicationFromRequest( $request, self::PAYMENT_ID );
		$applicationIncentives = iterator_to_array( $application->getIncentives() );

		$this->assertCount( 2, $applicationIncentives );
		$this->assertSame( $incentives[0], $applicationIncentives[0] );
		$this->assertSame( $incentives[1], $applicationIncentives[1] );
	}

	private function newIncentiveWithNameAndId( string $name, int $id ): Incentive {
		$incentive = new Incentive( $name );

		$reflectionObject = new ReflectionObject( $incentive );
		$reflectionProperty = $reflectionObject->getProperty( 'id' );
		$reflectionProperty->setAccessible( true );
		$reflectionProperty->setValue( $incentive, $id );

		return $incentive;
	}

}
