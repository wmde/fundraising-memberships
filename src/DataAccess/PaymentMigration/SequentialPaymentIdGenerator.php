<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\PaymentMigration;

use WMDE\Fundraising\PaymentContext\Domain\PaymentIdRepository;

/**
 * A payment ID generator that doesn't query the DB for every new ID, but keeps it as internal state.
 *
 * After the whole batch has run, the last ID needs to be synced to the DB for future payments
 */
class SequentialPaymentIdGenerator implements PaymentIdRepository {
	private int $currentId;

	public function __construct( int $startId ) {
		$this->currentId = $startId;
	}

	public function getNewID(): int {
		return $this->currentId++;
	}

}
