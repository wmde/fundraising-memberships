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

	private const MEMBERSHIP_ID = 9;

	private const PAYMENT_ID = 1;

	public function testCompanyMembershipRequestGetsBuildCorrectly(): void {
		$request = $this->newCompanyMembershipRequest();
		$testIncentiveFinder = new TestIncentiveFinder( [ new Incentive( 'I AM INCENTIVE' ) ] );
		$builder = new MembershipApplicationBuilder( $testIncentiveFinder );

		$application = $builder->newApplicationFromRequest( $request, self::MEMBERSHIP_ID, self::PAYMENT_ID );

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
		return ApplyForMembershipRequest::newCompanyApplyForMembershipRequest(
			membershipType: ValidMembershipApplication::MEMBERSHIP_TYPE,
			applicantCompanyName: self::COMPANY_NAME,
			applicantStreetAddress: ValidMembershipApplication::APPLICANT_STREET_ADDRESS,
			applicantPostalCode: ValidMembershipApplication::APPLICANT_POSTAL_CODE,
			applicantCity: ValidMembershipApplication::APPLICANT_CITY,
			applicantCountryCode: ValidMembershipApplication::APPLICANT_COUNTRY_CODE,
			applicantEmailAddress: ValidMembershipApplication::APPLICANT_EMAIL_ADDRESS,
			optsIntoDonationReceipt: true,
			incentives: $incentives,
			paymentParameters: ValidMembershipApplication::newPaymentParameters(),
			trackingInfo: $this->newTrackingInfo(),
			applicantPhoneNumber: $omitOptionalFields ? '' : ValidMembershipApplication::APPLICANT_PHONE_NUMBER,
		);
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
		return ApplicantName::newCompanyName( self::COMPANY_NAME );
	}

	private function assertIsExpectedAddress( ApplicantAddress $address ): void {
		$this->assertEquals(
			$this->getPhysicalAddress(),
			$address
		);
	}

	private function getPhysicalAddress(): ApplicantAddress {
		return new ApplicantAddress(
			streetAddress: ValidMembershipApplication::APPLICANT_STREET_ADDRESS,
			postalCode: ValidMembershipApplication::APPLICANT_POSTAL_CODE,
			city: ValidMembershipApplication::APPLICANT_CITY,
			countryCode: ValidMembershipApplication::APPLICANT_COUNTRY_CODE
		);
	}

	public function testWhenNoPhoneNumberIsGiven_membershipApplicationIsStillBuiltCorrectly(): void {
		$request = $this->newCompanyMembershipRequest();
		$testIncentiveFinder = new TestIncentiveFinder( [ new Incentive( 'I AM INCENTIVE' ) ] );
		$builder = new MembershipApplicationBuilder( $testIncentiveFinder );

		$application = $builder->newApplicationFromRequest( $request, self::MEMBERSHIP_ID, self::PAYMENT_ID );

		$this->assertIsExpectedCompanyPersonName( $application->getApplicant()->getName() );
		$this->assertIsExpectedAddress( $application->getApplicant()->getPhysicalAddress() );
	}

	public function testWhenBuildingCompanyApplication_salutationFieldIsSet(): void {
		$request = $this->newCompanyMembershipRequest();
		$testIncentiveFinder = new TestIncentiveFinder( [ new Incentive( 'I AM INCENTIVE' ) ] );
		$builder = new MembershipApplicationBuilder( $testIncentiveFinder );

		$application = $builder->newApplicationFromRequest( $request, self::MEMBERSHIP_ID, self::PAYMENT_ID );

		$this->assertSame( ApplicantName::COMPANY_SALUTATION, $application->getApplicant()->getName()->salutation );
	}

	public function testWhenBuildingApplicationIncentivesAreSet(): void {
		$incentives = [
			$this->newIncentiveWithNameAndId( 'inner_peace', 1 ),
			$this->newIncentiveWithNameAndId( 'a_better_world', 2 )
		];
		$incentiveFinder = new TestIncentiveFinder( $incentives );
		$request = $this->newCompanyMembershipRequest( false, array_map(
			fn ( $incentive ) => $incentive->getName(),
			$incentives
		) );
		$builder = new MembershipApplicationBuilder( $incentiveFinder );

		$application = $builder->newApplicationFromRequest( $request, self::MEMBERSHIP_ID, self::PAYMENT_ID );
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
