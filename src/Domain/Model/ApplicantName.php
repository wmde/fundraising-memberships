<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Model;

use WMDE\FreezableValueObject\FreezableValueObject;

class ApplicantName {
	use FreezableValueObject;

	public const COMPANY_SALUTATION = 'Firma';

	public const PERSON_PRIVATE = 'person';
	public const PERSON_COMPANY = 'firma';

	private string $personType = '';

	private string $salutation = '';
	private string $title = '';
	private string $firstName = '';
	private string $lastName = '';

	private string $companyName = '';

	private function __construct( string $personType ) {
		$this->personType = $personType;
	}

	public static function newPrivatePersonName(): self {
		return new self( self::PERSON_PRIVATE );
	}

	public static function newCompanyName(): self {
		$companyName = new self( self::PERSON_COMPANY );
		$companyName->setSalutation( self::COMPANY_SALUTATION );
		return $companyName;
	}

	public function setCompanyName( string $companyName ): void {
		$this->assertIsWritable();
		$this->companyName = $companyName;
	}

	public function getCompanyName(): string {
		return $this->companyName;
	}

	public function getSalutation(): string {
		return $this->salutation;
	}

	public function setSalutation( string $salutation ): void {
		$this->assertIsWritable();
		$this->salutation = $salutation;
	}

	public function getTitle(): string {
		return $this->title;
	}

	public function setTitle( string $title ): void {
		$this->assertIsWritable();
		$this->title = $title;
	}

	public function getFirstName(): string {
		return $this->firstName;
	}

	public function setFirstName( string $firstName ): void {
		$this->assertIsWritable();
		$this->firstName = $firstName;
	}

	public function getLastName(): string {
		return $this->lastName;
	}

	public function setLastName( string $lastName ): void {
		$this->assertIsWritable();
		$this->lastName = $lastName;
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
			$this->getTitle(),
			$this->getFirstName(),
			$this->getLastName()
		] ) );
	}

}
