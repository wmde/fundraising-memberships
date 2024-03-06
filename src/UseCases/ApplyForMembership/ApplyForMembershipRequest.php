<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership;

use WMDE\Fundraising\MembershipContext\Tracking\MembershipApplicationTrackingInfo;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentParameters;

class ApplyForMembershipRequest {

	private function __construct(
		public readonly string $membershipType,
		public readonly bool $applicantIsCompany,
		public readonly string $applicantStreetAddress,
		public readonly string $applicantPostalCode,
		public readonly string $applicantCity,
		public readonly string $applicantCountryCode,
		public readonly string $applicantEmailAddress,
		public readonly PaymentParameters $paymentParameters,
		public readonly MembershipApplicationTrackingInfo $trackingInfo,
		public readonly string $applicantCompanyName = '',
		public readonly string $applicantSalutation = '',
		public readonly string $applicantTitle = '',
		public readonly string $applicantFirstName = '',
		public readonly string $applicantLastName = '',
		public readonly string $applicantDateOfBirth = '',
		public readonly string $applicantPhoneNumber = '',
		public readonly bool $optsIntoDonationReceipt = true,
		public readonly array $incentives = [],
	) {
	}

	public static function newPrivateApplyForMembershipRequest(
		string $membershipType,
		string $applicantSalutation,
		string $applicantTitle,
		string $applicantFirstName,
		string $applicantLastName,
		string $applicantStreetAddress,
		string $applicantPostalCode,
		string $applicantCity,
		string $applicantCountryCode,
		string $applicantEmailAddress,
		bool $optsIntoDonationReceipt,
		array $incentives,
		PaymentParameters $paymentParameters,
		MembershipApplicationTrackingInfo $trackingInfo,
		string $applicantDateOfBirth = '',
		string $applicantPhoneNumber = '',
	): ApplyForMembershipRequest {
		return new self(
			membershipType: $membershipType,
			applicantIsCompany: false,
			applicantStreetAddress: trim( $applicantStreetAddress ),
			applicantPostalCode: trim( $applicantPostalCode ),
			applicantCity: trim( $applicantCity ),
			applicantCountryCode: trim( $applicantCountryCode ),
			applicantEmailAddress: trim( $applicantEmailAddress ),
			paymentParameters: $paymentParameters,
			trackingInfo: $trackingInfo,
			applicantSalutation: trim( $applicantSalutation ),
			applicantTitle: trim( $applicantTitle ),
			applicantFirstName: trim( $applicantFirstName ),
			applicantLastName: trim( $applicantLastName ),
			applicantDateOfBirth: trim( $applicantDateOfBirth ),
			applicantPhoneNumber: trim( $applicantPhoneNumber ),
			optsIntoDonationReceipt: $optsIntoDonationReceipt,
			incentives: $incentives
		);
	}

	public static function newCompanyApplyForMembershipRequest(
		string $membershipType,
		string $applicantCompanyName,
		string $applicantStreetAddress,
		string $applicantPostalCode,
		string $applicantCity,
		string $applicantCountryCode,
		string $applicantEmailAddress,
		bool $optsIntoDonationReceipt,
		array $incentives,
		PaymentParameters $paymentParameters,
		MembershipApplicationTrackingInfo $trackingInfo,
		string $applicantPhoneNumber = '',
	): ApplyForMembershipRequest {
		return new self(
			membershipType: $membershipType,
			applicantIsCompany: true,
			applicantStreetAddress: trim( $applicantStreetAddress ),
			applicantPostalCode: trim( $applicantPostalCode ),
			applicantCity: trim( $applicantCity ),
			applicantCountryCode: trim( $applicantCountryCode ),
			applicantEmailAddress: trim( $applicantEmailAddress ),
			paymentParameters: $paymentParameters,
			trackingInfo: $trackingInfo,
			applicantCompanyName: trim( $applicantCompanyName ),
			applicantPhoneNumber: trim( $applicantPhoneNumber ),
			optsIntoDonationReceipt: $optsIntoDonationReceipt,
			incentives: $incentives
		);
	}

	public function isCompanyApplication(): bool {
		return $this->applicantIsCompany;
	}

	public function getMatomoTrackingString(): string {
		return $this->trackingInfo->getMatomoString();
	}
}
