<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\PaymentIdRepository;

class DummyPaymentIdRepository implements PaymentIdRepository {

	public function getNewID(): int {
		throw new \LogicException( 'ID generation should not be called in this code path' );
	}

}
