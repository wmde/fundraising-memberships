<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Fixtures;

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
		return ApplyForMembershipRequest::newPrivateApplyForMembershipRequest(
			membershipType: ValidMembershipApplication::MEMBERSHIP_TYPE,
			applicantSalutation: ValidMembershipApplication::APPLICANT_SALUTATION,
			applicantTitle: ValidMembershipApplication::APPLICANT_TITLE,
			applicantFirstName: ValidMembershipApplication::APPLICANT_FIRST_NAME,
			applicantLastName: ValidMembershipApplication::APPLICANT_LAST_NAME,
			applicantStreetAddress: ValidMembershipApplication::APPLICANT_STREET_ADDRESS,
			applicantPostalCode: ValidMembershipApplication::APPLICANT_POSTAL_CODE,
			applicantCity: ValidMembershipApplication::APPLICANT_CITY,
			applicantCountryCode: ValidMembershipApplication::APPLICANT_COUNTRY_CODE,
			applicantEmailAddress: ValidMembershipApplication::APPLICANT_EMAIL_ADDRESS,
			optsIntoDonationReceipt: false,
			incentives: [],
			paymentParameters: ValidMembershipApplication::newPaymentParameters(),
			trackingInfo: $this->newTrackingInfo(),
			applicantDateOfBirth: ValidMembershipApplication::APPLICANT_DATE_OF_BIRTH,
			applicantPhoneNumber: ValidMembershipApplication::APPLICANT_PHONE_NUMBER,
		);
	}

	private function newTrackingInfo(): MembershipApplicationTrackingInfo {
		return new MembershipApplicationTrackingInfo(
			ValidMembershipApplication::TEMPLATE_CAMPAIGN,
			ValidMembershipApplication::TEMPLATE_NAME
		);
	}

}
