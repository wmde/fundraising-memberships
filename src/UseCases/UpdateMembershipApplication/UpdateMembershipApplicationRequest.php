<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\UpdateMembershipApplication;

use WMDE\EmailAddress\EmailAddress;

class UpdateMembershipApplicationRequest {

	public function __construct(
		private readonly int $membershipId,
		private readonly bool $isCompanyApplication,
		private readonly string $salutation,
		private readonly string $title,
		private readonly string $firstName,
		private readonly string $lastName,
		private readonly string $companyName,
		private readonly string $streetAddress,
		private readonly string $postalCode,
		private readonly string $city,
		private readonly string $countryCode,
		private readonly EmailAddress $emailAddress,
	) {
	}

	public function getMembershipId(): int {
		return $this->membershipId;
	}

	public function getStreetAddress(): string {
		return $this->streetAddress;
	}

	public function getPostalCode(): string {
		return $this->postalCode;
	}

	public function getCity(): string {
		return $this->city;
	}

	public function getCountryCode(): string {
		return $this->countryCode;
	}

	public function getEmailAddress(): EmailAddress {
		return $this->emailAddress;
	}

	public function isCompanyApplication(): bool {
		return $this->isCompanyApplication;
	}

	public function getSalutation(): string {
		return $this->salutation;
	}

	public function getTitle(): string {
		return $this->title;
	}

	public function getFirstName(): string {
		return $this->firstName;
	}

	public function getLastName(): string {
		return $this->lastName;
	}

	public function getCompanyName(): string {
		return $this->companyName;
	}
}
