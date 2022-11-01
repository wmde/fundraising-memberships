<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\Tests\TestDoubles;

use WMDE\Fundraising\PaymentContext\DataAccess\PaymentNotFoundException;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;

class InMemoryPaymentRepository implements PaymentRepository {
	/**
	 * @param Payment[] $payments
	 */
	public function __construct( private array $payments = [] ) {
	}

	public function storePayment( Payment $payment ): void {
		$this->payments[$payment->getId()] = $payment;
	}

	public function getPaymentById( int $id ): Payment {
		if ( empty( $this->payments[$id] ) ) {
			throw new PaymentNotFoundException( "Payment with id $id not found" );
		}
		return $this->payments[$id];
	}

}
