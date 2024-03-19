<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\ApplyForMembership;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tracking\MembershipApplicationTrackingInfo;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipRequest;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipRequest
 */
class ApplyForMembershipRequestTest extends TestCase {

	public function testRequestIsMarkedAsPrivate(): void {
		$request = ApplyForMembershipRequest::newPrivateApplyForMembershipRequest(
			membershipType: '',
			applicantSalutation: 'Herr',
			applicantTitle: 'Dr.',
			applicantFirstName: 'Bruce',
			applicantLastName: 'Wayne',
			applicantStreetAddress: 'Fledergasse 9',
			applicantPostalCode: '66484',
			applicantCity: 'Battweiler',
			applicantCountryCode: 'ZZ',
			applicantEmailAddress: 'bw@waynecorp.biz',
			optsIntoDonationReceipt: true,
			incentives: [],
			paymentParameters: ValidMembershipApplication::newPaymentParameters(),
			trackingInfo: new MembershipApplicationTrackingInfo( 'test_campaign', 'test_keyword' ),
			applicantDateOfBirth: ' 1978-04-17',
			applicantPhoneNumber: ValidMembershipApplication::APPLICANT_PHONE_NUMBER,
		);

		$this->assertFalse( $request->isCompanyApplication() );
	}

	public function testRequestIsMarkedAsCompany(): void {
		$request = ApplyForMembershipRequest::newCompanyApplyForMembershipRequest(
			membershipType: '',
			applicantCompanyName: 'ACME',
			applicantStreetAddress: 'Fledergasse 9',
			applicantPostalCode: '66484',
			applicantCity: 'Battweiler',
			applicantCountryCode: 'ZZ',
			applicantEmailAddress: 'bw@waynecorp.biz',
			optsIntoDonationReceipt: true,
			incentives: [],
			paymentParameters: ValidMembershipApplication::newPaymentParameters(),
			trackingInfo: new MembershipApplicationTrackingInfo( 'test_campaign', 'test_keyword' ),
			applicantPhoneNumber: ValidMembershipApplication::APPLICANT_PHONE_NUMBER,
		);

		$this->assertTrue( $request->isCompanyApplication() );
	}

	public function testPrivateStringValuesAreTrimmed(): void {
		$request = ApplyForMembershipRequest::newPrivateApplyForMembershipRequest(
			membershipType: '',
			applicantSalutation: ' Herr  ',
			applicantTitle: ' Dr. ',
			applicantFirstName: ' Bruce ',
			applicantLastName: 'Wayne        ',
			applicantStreetAddress: ' Fledergasse 9 ',
			applicantPostalCode: ' 66484   ',
			applicantCity: '  Battweiler      ',
			applicantCountryCode: ' ZZ ',
			applicantEmailAddress: ' bw@waynecorp.biz ',
			optsIntoDonationReceipt: true,
			incentives: [],
			paymentParameters: ValidMembershipApplication::newPaymentParameters(),
			trackingInfo: new MembershipApplicationTrackingInfo( 'test_campaign', 'test_keyword' ),
			applicantDateOfBirth: ' 1978-04-17',
			applicantPhoneNumber: ValidMembershipApplication::APPLICANT_PHONE_NUMBER,
		);

		$this->assertSame( 'Herr', $request->applicantSalutation );
		$this->assertSame( 'Dr.', $request->applicantTitle );
		$this->assertSame( 'Bruce', $request->applicantFirstName );
		$this->assertSame( 'Wayne', $request->applicantLastName );
		$this->assertSame( 'Fledergasse 9', $request->applicantStreetAddress );
		$this->assertSame( '66484', $request->applicantPostalCode );
		$this->assertSame( 'Battweiler', $request->applicantCity );
		$this->assertSame( 'ZZ', $request->applicantCountryCode );
		$this->assertSame( 'bw@waynecorp.biz', $request->applicantEmailAddress );
		$this->assertSame( '1978-04-17', $request->applicantDateOfBirth );
	}

	public function testCompanyStringValuesAreTrimmed(): void {
		$request = ApplyForMembershipRequest::newCompanyApplyForMembershipRequest(
			membershipType: '',
			applicantCompanyName: ' ACME  ',
			applicantStreetAddress: ' Fledergasse 9 ',
			applicantPostalCode: ' 66484   ',
			applicantCity: '  Battweiler      ',
			applicantCountryCode: ' ZZ ',
			applicantEmailAddress: ' bw@waynecorp.biz ',
			optsIntoDonationReceipt: true,
			incentives: [],
			paymentParameters: ValidMembershipApplication::newPaymentParameters(),
			trackingInfo: new MembershipApplicationTrackingInfo( 'test_campaign', 'test_keyword' ),
			applicantPhoneNumber: ValidMembershipApplication::APPLICANT_PHONE_NUMBER,
		);

		$this->assertSame( 'ACME', $request->applicantCompanyName );
		$this->assertSame( 'Fledergasse 9', $request->applicantStreetAddress );
		$this->assertSame( '66484', $request->applicantPostalCode );
		$this->assertSame( 'Battweiler', $request->applicantCity );
		$this->assertSame( 'ZZ', $request->applicantCountryCode );
		$this->assertSame( 'bw@waynecorp.biz', $request->applicantEmailAddress );
	}

}
