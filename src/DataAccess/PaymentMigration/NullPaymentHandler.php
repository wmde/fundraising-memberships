<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\PaymentMigration;

use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;

class NullPaymentHandler implements NewPaymentHandler {
	public function handlePayment( Payment $payment, int $membershipId ): void {
		// doing nothing
	}

}
