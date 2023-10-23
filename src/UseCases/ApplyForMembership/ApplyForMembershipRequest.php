<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership;

use WMDE\FreezableValueObject\FreezableValueObject;
use WMDE\Fundraising\MembershipContext\Tracking\MembershipApplicationTrackingInfo;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentParameters;

class ApplyForMembershipRequest {
	use FreezableValueObject;

	private string $membershipType;

	private bool $applicantIsCompany = false;
	private string $applicantCompanyName;
	private string $applicantSalutation;
	private string $applicantTitle;
	private string $applicantFirstName;
	private string $applicantLastName;

	private string $applicantStreetAddress;
	private string $applicantPostalCode;
	private string $applicantCity;
	private string $applicantCountryCode;

	private string $applicantEmailAddress;
	private string $applicantPhoneNumber;
	private string $applicantDateOfBirth;

	private PaymentParameters $paymentParameters;

	private MembershipApplicationTrackingInfo $trackingInfo;
	private string $piwikTrackingString;

	private bool $optsIntoDonationReceipt = true;

	/** @var string[] */
	private array $incentives = [];

	public function getMembershipType(): string {
		return $this->membershipType;
	}

	public function setMembershipType( string $membershipType ): void {
		$this->assertIsWritable();
		$this->membershipType = $membershipType;
	}

	/**
	 * @return bool True when the applicant is a company, false when the applicant is a private person
	 */
	public function isCompanyApplication(): bool {
		return $this->applicantIsCompany;
	}

	public function markApplicantAsCompany(): void {
		$this->assertIsWritable();
		$this->applicantIsCompany = true;
	}

	public function getApplicantCompanyName(): string {
		return $this->applicantCompanyName;
	}

	public function setApplicantCompanyName( string $applicantCompanyName ): void {
		$this->applicantCompanyName = trim( $applicantCompanyName );
	}

	public function getApplicantSalutation(): string {
		return $this->applicantSalutation;
	}

	public function setApplicantSalutation( string $applicantSalutation ): void {
		$this->assertIsWritable();
		$this->applicantSalutation = trim( $applicantSalutation );
	}

	public function getApplicantTitle(): string {
		return $this->applicantTitle;
	}

	public function setApplicantTitle( string $applicantTitle ): void {
		$this->assertIsWritable();
		$this->applicantTitle = trim( $applicantTitle );
	}

	public function getApplicantFirstName(): string {
		return $this->applicantFirstName;
	}

	public function setApplicantFirstName( string $applicantFirstName ): void {
		$this->assertIsWritable();
		$this->applicantFirstName = trim( $applicantFirstName );
	}

	public function getApplicantLastName(): string {
		return $this->applicantLastName;
	}

	public function setApplicantLastName( string $applicantLastName ): void {
		$this->assertIsWritable();
		$this->applicantLastName = trim( $applicantLastName );
	}

	public function getApplicantStreetAddress(): string {
		return $this->applicantStreetAddress;
	}

	public function setApplicantStreetAddress( string $applicantStreetAddress ): void {
		$this->assertIsWritable();
		$this->applicantStreetAddress = trim( $applicantStreetAddress );
	}

	public function getApplicantPostalCode(): string {
		return $this->applicantPostalCode;
	}

	public function setApplicantPostalCode( string $applicantPostalCode ): void {
		$this->assertIsWritable();
		$this->applicantPostalCode = trim( $applicantPostalCode );
	}

	public function getApplicantCity(): string {
		return $this->applicantCity;
	}

	public function setApplicantCity( string $applicantCity ): void {
		$this->assertIsWritable();
		$this->applicantCity = trim( $applicantCity );
	}

	public function getApplicantCountryCode(): string {
		return $this->applicantCountryCode;
	}

	public function setApplicantCountryCode( string $applicantCountryCode ): void {
		$this->assertIsWritable();
		$this->applicantCountryCode = trim( $applicantCountryCode );
	}

	public function getApplicantEmailAddress(): string {
		return $this->applicantEmailAddress;
	}

	public function setApplicantEmailAddress( string $applicantEmailAddress ): void {
		$this->assertIsWritable();
		$this->applicantEmailAddress = trim( $applicantEmailAddress );
	}

	public function getApplicantPhoneNumber(): string {
		return $this->applicantPhoneNumber;
	}

	public function setApplicantPhoneNumber( string $applicantPhoneNumber ): void {
		$this->assertIsWritable();
		$this->applicantPhoneNumber = trim( $applicantPhoneNumber );
	}

	public function getApplicantDateOfBirth(): string {
		return $this->applicantDateOfBirth;
	}

	public function setApplicantDateOfBirth( string $applicantDateOfBirth ): void {
		$this->assertIsWritable();
		$this->applicantDateOfBirth = trim( $applicantDateOfBirth );
	}

	public function getPaymentParameters(): PaymentParameters {
		return $this->paymentParameters;
	}

	public function setPaymentParameters( PaymentParameters $paymentParameters ): void {
		$this->paymentParameters = $paymentParameters;
	}

	public function getTrackingInfo(): MembershipApplicationTrackingInfo {
		return $this->trackingInfo;
	}

	public function setTrackingInfo( MembershipApplicationTrackingInfo $trackingInfo ): void {
		$this->assertIsWritable();
		$this->trackingInfo = $trackingInfo;
	}

	public function getPiwikTrackingString(): string {
		return $this->piwikTrackingString;
	}

	public function setPiwikTrackingString( string $piwikTrackingString ): void {
		$this->assertIsWritable();
		$this->piwikTrackingString = trim( $piwikTrackingString );
	}

	public function getOptsIntoDonationReceipt(): bool {
		return $this->optsIntoDonationReceipt;
	}

	public function setOptsIntoDonationReceipt( bool $optIn ): void {
		$this->assertIsWritable();
		$this->optsIntoDonationReceipt = $optIn;
	}

	/**
	 * @return string[]
	 */
	public function getIncentives(): array {
		return $this->incentives;
	}

	/**
	 * @param string[] $incentives
	 */
	public function setIncentives( array $incentives ): void {
		$this->assertIsWritable();
		$this->incentives = $incentives;
	}

}
