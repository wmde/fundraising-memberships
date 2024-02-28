<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\PaymentMigration;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;

class InsertingPaymentHandler implements NewPaymentHandler {

	private const INSERT_BATCH_SIZE = 2000;

	private int $paymentIndex = 0;

	private bool $transactionStarted = false;

	public function __construct(
		private readonly EntityManager $entityManager,
		private readonly MembershipPaymentIdCollection $paymentIdCollection ) {
	}

	public function handlePayment( Payment $payment, int $membershipId ): void {
		$this->entityManager->persist( $payment );
		$this->paymentIdCollection->addPaymentForMembership( $payment->getId(), $membershipId );
		$this->paymentIndex++;
		if ( ( $this->paymentIndex % self::INSERT_BATCH_SIZE ) === 0 ) {
			$this->flush();
		}
	}

	public function flushRemaining(): void {
		$this->flush();
	}

	private function flush(): void {
		try {
			$this->entityManager->flush();
		} catch ( \Exception $e ) {
			// We prevent the exception from being caught upstream, because we don't want to count these errors:
			// The exception will be some kind of unrecoverable database error.
			die( $e->getMessage() );
		}
		$this->entityManager->clear();

		$this->startTransactionIfNeeded();
		$statement = $this->entityManager->getConnection()->prepare( "UPDATE request SET payment_id=? WHERE id=?" );
		foreach ( $this->paymentIdCollection as $membershipId => $paymentId ) {
			$statement->bindValue( 1, $paymentId );
			$statement->bindValue( 2, $membershipId );
			$statement->executeQuery();
		}
		$this->commit();
		$this->paymentIdCollection->clear();
	}

	private function startTransactionIfNeeded(): void {
		if ( $this->transactionStarted ) {
			return;
		}
		$this->entityManager->beginTransaction();
		$this->transactionStarted = true;
	}

	private function commit() {
		if ( !$this->transactionStarted ) {
			return;
		}
		$this->entityManager->commit();
		$this->transactionStarted = false;
	}

}
