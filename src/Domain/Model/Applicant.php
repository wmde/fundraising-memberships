<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Model;

use WMDE\EmailAddress\EmailAddress;

class Applicant {

	public function __construct(
		private readonly ApplicantName $name,
		private readonly ApplicantAddress $physicalAddress,
		private EmailAddress $email,
		private readonly PhoneNumber $phone,
		private readonly ?\DateTime $dateOfBirth = null
	) {
	}

	public function getName(): ApplicantName {
		return $this->name;
	}

	public function getPhysicalAddress(): ApplicantAddress {
		return $this->physicalAddress;
	}

	public function getEmailAddress(): EmailAddress {
		return $this->email;
	}

	public function getDateOfBirth(): ?\DateTime {
		return $this->dateOfBirth;
	}

	public function changeEmailAddress( EmailAddress $email ): void {
		$this->email = $email;
	}

	public function getPhoneNumber(): PhoneNumber {
		return $this->phone;
	}

	public function isPrivatePerson(): bool {
		return $this->getName()->isPrivatePerson();
	}

	public function isCompany(): bool {
		return $this->getName()->isCompany();
	}
}
