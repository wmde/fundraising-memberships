<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\PaymentAnonymizer;

class FakePaymentAnonymizer implements PaymentAnonymizer {

	/**
	 * @var int[]
	 */
	public array $paymentIds = [];

	public function anonymizeWithIds( int ...$paymentIds ): void {
		foreach ( $paymentIds as $id ) {
			$this->paymentIds[] = $id;
		}
	}
}
