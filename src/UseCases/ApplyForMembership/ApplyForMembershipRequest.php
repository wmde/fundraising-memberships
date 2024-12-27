<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership;

use WMDE\Fundraising\MembershipContext\Tracking\MembershipTracking;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentParameters;

class ApplyForMembershipRequest {

	/**
	 * @param string $membershipType
	 * @param bool $applicantIsCompany
	 * @param string $applicantStreetAddress
	 * @param string $applicantPostalCode
	 * @param string $applicantCity
	 * @param string $applicantCountryCode
	 * @param string $applicantEmailAddress
	 * @param PaymentParameters $paymentParameters
	 * @param MembershipTracking $trackingInfo
	 * @param string $applicantCompanyName
	 * @param string $applicantSalutation
	 * @param string $applicantTitle
	 * @param string $applicantFirstName
	 * @param string $applicantLastName
	 * @param string $applicantDateOfBirth
	 * @param string $applicantPhoneNumber
	 * @param bool $optsIntoDonationReceipt
	 * @param array<string> $incentives
	 */
	private function __construct(
		public readonly string $membershipType,
		public readonly bool $applicantIsCompany,
		public readonly string $applicantStreetAddress,
		public readonly string $applicantPostalCode,
		public readonly string $applicantCity,
		public readonly string $applicantCountryCode,
		public readonly string $applicantEmailAddress,
		public readonly PaymentParameters $paymentParameters,
		public readonly MembershipTracking $trackingInfo,
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

	/**
	 * @param string $membershipType
	 * @param string $applicantSalutation
	 * @param string $applicantTitle
	 * @param string $applicantFirstName
	 * @param string $applicantLastName
	 * @param string $applicantStreetAddress
	 * @param string $applicantPostalCode
	 * @param string $applicantCity
	 * @param string $applicantCountryCode
	 * @param string $applicantEmailAddress
	 * @param bool $optsIntoDonationReceipt
	 * @param array<string> $incentives
	 * @param PaymentParameters $paymentParameters
	 * @param MembershipTracking $trackingInfo
	 * @param string $applicantDateOfBirth
	 * @param string $applicantPhoneNumber
	 *
	 * @return ApplyForMembershipRequest
	 */
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
		MembershipTracking $trackingInfo,
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

	/**
	 * @param string $membershipType
	 * @param string $applicantCompanyName
	 * @param string $applicantStreetAddress
	 * @param string $applicantPostalCode
	 * @param string $applicantCity
	 * @param string $applicantCountryCode
	 * @param string $applicantEmailAddress
	 * @param bool $optsIntoDonationReceipt
	 * @param array<string> $incentives
	 * @param PaymentParameters $paymentParameters
	 * @param MembershipTracking $trackingInfo
	 * @param string $applicantPhoneNumber
	 *
	 * @return ApplyForMembershipRequest
	 */
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
		MembershipTracking $trackingInfo,
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

	public function getTracking(): MembershipTracking {
		return $this->trackingInfo;
	}
}
