<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\Repositories\PaymentIDRepository;

class DummyPaymentIdRepository implements PaymentIDRepository {

	public function getNewID(): int {
		throw new \LogicException( 'ID generation should not be called in this code path' );
	}

}
