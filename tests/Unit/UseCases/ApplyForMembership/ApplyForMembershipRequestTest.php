<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\ApplyForMembership;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Tracking\MembershipApplicationTrackingInfo;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipRequest;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipRequest
 */
class ApplyForMembershipRequestTest extends TestCase {
	public function testApplicantAccessors(): void {
		$request = new ApplyForMembershipRequest();
		$request->setApplicantSalutation( 'Herr' );
		$request->setApplicantTitle( 'Dr.' );
		$request->setApplicantFirstName( 'Bruce' );
		$request->setApplicantLastName( 'Wayne' );
		$request->setApplicantCompanyName( 'Wayne Enterprises' );
		$request->setApplicantPostalCode( '66484' );
		$request->setApplicantStreetAddress( 'Fledergasse 9' );
		$request->setApplicantCity( 'Battweiler' );
		$request->setApplicantCountryCode( 'ZZ' );
		$request->setApplicantEmailAddress( 'bw@waynecorp.biz' );
		$request->setApplicantPhoneNumber( '+16060842' );
		$request->setApplicantDateOfBirth( '1978-04-17' );
		$request->setOptsIntoDonationReceipt( true );
		$request->setIncentives( [ 'bat-infos' ] );
		$companyRequest = new ApplyForMembershipRequest();
		$companyRequest->markApplicantAsCompany();

		$this->assertSame( 'Herr', $request->getApplicantSalutation() );
		$this->assertSame( 'Dr.', $request->getApplicantTitle() );
		$this->assertSame( 'Bruce', $request->getApplicantFirstName() );
		$this->assertSame( 'Wayne', $request->getApplicantLastName() );
		$this->assertSame( 'Wayne Enterprises', $request->getApplicantCompanyName() );
		$this->assertSame( 'Fledergasse 9', $request->getApplicantStreetAddress() );
		$this->assertSame( '66484', $request->getApplicantPostalCode() );
		$this->assertSame( 'Battweiler', $request->getApplicantCity() );
		$this->assertSame( 'ZZ', $request->getApplicantCountryCode() );
		$this->assertSame( 'bw@waynecorp.biz', $request->getApplicantEmailAddress() );
		$this->assertSame( '+16060842', $request->getApplicantPhoneNumber() );
		$this->assertSame( '1978-04-17', $request->getApplicantDateOfBirth() );
		$this->assertTrue( $request->getOptsIntoDonationReceipt() );
		$this->assertEquals( [ 'bat-infos' ], $request->getIncentives() );
		$this->assertFalse( $request->isCompanyApplication() );
		$this->assertTrue( $companyRequest->isCompanyApplication() );
	}

	public function testTrackingAccessors(): void {
		$request = new ApplyForMembershipRequest();
		$trackingInfo = new MembershipApplicationTrackingInfo( 'test_campaign', 'test_keyword' );
		$request->setTrackingInfo( $trackingInfo );
		$request->setPiwikTrackingString( 'test_campaign/test_keyword' );

		$this->assertEquals( $trackingInfo, $request->getTrackingInfo() );
		$this->assertSame( 'test_campaign/test_keyword', $request->getPiwikTrackingString() );
	}

	public function testStringValuesAreTrimmed(): void {
		$request = new ApplyForMembershipRequest();
		$request->setApplicantSalutation( ' Herr  ' );
		$request->setApplicantTitle( ' Dr. ' );
		$request->setApplicantFirstName( ' Bruce ' );
		$request->setApplicantLastName( 'Wayne        ' );
		$request->setApplicantCompanyName( '   Wayne Enterprises ' );
		$request->setApplicantPostalCode( ' 66484   ' );
		$request->setApplicantStreetAddress( ' Fledergasse 9 ' );
		$request->setApplicantCity( '  Battweiler      ' );
		$request->setApplicantCountryCode( ' ZZ ' );
		$request->setApplicantEmailAddress( ' bw@waynecorp.biz ' );
		$request->setApplicantPhoneNumber( '    +16060842 ' );
		$request->setApplicantDateOfBirth( ' 1978-04-17' );
		$request->setPiwikTrackingString( '   test_campaign/test_keyword ' );

		$this->assertSame( 'Herr', $request->getApplicantSalutation() );
		$this->assertSame( 'Dr.', $request->getApplicantTitle() );
		$this->assertSame( 'Bruce', $request->getApplicantFirstName() );
		$this->assertSame( 'Wayne', $request->getApplicantLastName() );
		$this->assertSame( 'Wayne Enterprises', $request->getApplicantCompanyName() );
		$this->assertSame( 'Fledergasse 9', $request->getApplicantStreetAddress() );
		$this->assertSame( '66484', $request->getApplicantPostalCode() );
		$this->assertSame( 'Battweiler', $request->getApplicantCity() );
		$this->assertSame( 'ZZ', $request->getApplicantCountryCode() );
		$this->assertSame( 'bw@waynecorp.biz', $request->getApplicantEmailAddress() );
		$this->assertSame( '+16060842', $request->getApplicantPhoneNumber() );
		$this->assertSame( '1978-04-17', $request->getApplicantDateOfBirth() );
		$this->assertSame( 'test_campaign/test_keyword', $request->getPiwikTrackingString() );
	}

}
