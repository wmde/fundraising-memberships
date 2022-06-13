<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Data;

use WMDE\Fundraising\MembershipContext\Tracking\MembershipApplicationTrackingInfo;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipRequest;

class ValidMembershipApplicationRequest {

	/**
	 * Returns a request with the same data as the constants in @see ValidMembershipApplication
	 *
	 * The object is purposely left mutable so tests can change a single value to something invalid.
	 *
	 * @return ApplyForMembershipRequest
	 */
	public static function newValidRequest(): ApplyForMembershipRequest {
		return ( new self() )->createValidRequest();
	}

	private function createValidRequest(): ApplyForMembershipRequest {
		$request = new ApplyForMembershipRequest();

		$request->setMembershipType( ValidMembershipApplication::MEMBERSHIP_TYPE );

		$request->setApplicantFirstName( ValidMembershipApplication::APPLICANT_FIRST_NAME );
		$request->setApplicantLastName( ValidMembershipApplication::APPLICANT_LAST_NAME );
		$request->setApplicantSalutation( ValidMembershipApplication::APPLICANT_SALUTATION );
		$request->setApplicantTitle( ValidMembershipApplication::APPLICANT_TITLE );
		$request->setApplicantCompanyName( '' );

		$request->setApplicantDateOfBirth( ValidMembershipApplication::APPLICANT_DATE_OF_BIRTH );

		$request->setApplicantCity( ValidMembershipApplication::APPLICANT_CITY );
		$request->setApplicantCountryCode( ValidMembershipApplication::APPLICANT_COUNTRY_CODE );
		$request->setApplicantPostalCode( ValidMembershipApplication::APPLICANT_POSTAL_CODE );
		$request->setApplicantStreetAddress( ValidMembershipApplication::APPLICANT_STREET_ADDRESS );

		$request->setApplicantEmailAddress( ValidMembershipApplication::APPLICANT_EMAIL_ADDRESS );
		$request->setApplicantPhoneNumber( ValidMembershipApplication::APPLICANT_PHONE_NUMBER );

		$request->setMembershipType( ValidMembershipApplication::MEMBERSHIP_TYPE );

		$request->setTrackingInfo( $this->newTrackingInfo() );
		$request->setPiwikTrackingString( 'foo/bar' );

		$request->setPaymentCreationRequest( ValidMembershipApplication::newPaymentCreationRequest() );

		return $request->assertNoNullFields();
	}

	private function newTrackingInfo(): MembershipApplicationTrackingInfo {
		return new MembershipApplicationTrackingInfo(
			ValidMembershipApplication::TEMPLATE_CAMPAIGN,
			ValidMembershipApplication::TEMPLATE_NAME
		);
	}

}
