<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Model;

use WMDE\FreezableValueObject\FreezableValueObject;

class ApplicantAddress {
	use FreezableValueObject;

	private string $streetAddress = '';
	private string $postalCode = '';
	private string $city = '';
	private string $countryCode = '';

	public function getStreetAddress(): string {
		return $this->streetAddress;
	}

	public function setStreetAddress( string $streetAddress ): void {
		$this->assertIsWritable();
		$this->streetAddress = $streetAddress;
	}

	public function getPostalCode(): string {
		return $this->postalCode;
	}

	public function setPostalCode( string $postalCode ): void {
		$this->assertIsWritable();
		$this->postalCode = $postalCode;
	}

	public function getCity(): string {
		return $this->city;
	}

	public function setCity( string $city ): void {
		$this->assertIsWritable();
		$this->city = $city;
	}

	public function getCountryCode(): string {
		return $this->countryCode;
	}

	public function setCountryCode( string $countryCode ): void {
		$this->assertIsWritable();
		$this->countryCode = $countryCode;
	}

}
