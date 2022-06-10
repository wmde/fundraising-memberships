<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\PaymentMigration;

class BoundedValue {
	private mixed $lowerBound;
	private mixed $upperBound;

	public function __construct( mixed $value ) {
		$this->lowerBound = $value;
		$this->upperBound = $value;
	}

	public function set( mixed $value ): void {
		if ( $value < $this->lowerBound ) {
			$this->lowerBound = $value;
		}
		if ( $value > $this->upperBound ) {
			$this->upperBound = $value;
		}
	}

	public function getLowerBound(): mixed {
		return $this->lowerBound;
	}

	public function getUpperBound(): mixed {
		return $this->upperBound;
	}
}
