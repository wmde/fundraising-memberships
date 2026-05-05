<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Model;

class ApplicantName {

	public const string COMPANY_SALUTATION = 'Firma';
	public const string PERSON_PRIVATE = 'person';
	public const string PERSON_COMPANY = 'firma';
	public const string PERSON_SCRUBBED = 'scrubbed';

	private function __construct(
		public readonly string $personType = '',
		public readonly string $salutation = '',
		public readonly string $title = '',
		public readonly string $firstName = '',
		public readonly string $lastName = '',
		public readonly string $companyName = '',
	) {
	}

	public static function newPrivatePersonName(
		string $salutation = '',
		string $title = '',
		string $firstName = '',
		string $lastName = '',
	): self {
		return new self(
			personType: self::PERSON_PRIVATE,
			salutation: $salutation,
			title: $title,
			firstName: $firstName,
			lastName: $lastName,
		);
	}

	public static function newCompanyName(
		string $companyName = '',
	): self {
		return new self(
			personType: self::PERSON_COMPANY,
			salutation: self::COMPANY_SALUTATION,
			companyName: $companyName,
		);
	}

	public static function newScrubbedName(): self {
		return new self( personType: self::PERSON_SCRUBBED );
	}

	public function isPrivatePerson(): bool {
		return $this->personType === self::PERSON_PRIVATE;
	}

	public function isCompany(): bool {
		return $this->personType === self::PERSON_COMPANY;
	}

	public function getFullName(): string {
		return implode( ', ', array_filter( [
			$this->getFullPrivatePersonName(),
			$this->companyName
		] ) );
	}

	private function getFullPrivatePersonName(): string {
		return implode( ' ', array_filter( [
			$this->title,
			$this->firstName,
			$this->lastName
		] ) );
	}

}
