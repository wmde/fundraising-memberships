<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\PaymentMigration;

use Traversable;

class MembershipPaymentIdCollection implements \IteratorAggregate {
	private array $membershipToPaymentIds = [];

	public function addPaymentForMembership( int $paymentId, int $membershipId ): void {
		$this->membershipToPaymentIds[$membershipId] = $paymentId;
	}

	public function getIterator(): Traversable {
		return new \ArrayIterator( $this->membershipToPaymentIds );
	}

	public function clear(): void {
		$this->membershipToPaymentIds = [];
	}
}
