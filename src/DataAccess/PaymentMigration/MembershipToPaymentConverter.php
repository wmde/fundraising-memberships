<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\PaymentMigration;

use Doctrine\DBAL\Connection;
use WMDE\Euro\Euro;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;

class MembershipToPaymentConverter {

	private const CHUNK_SIZE = 2000;
	public const CONVERT_ALL = -1;

	private ConversionResult $result;

	public function __construct(
		private Connection $db,
		private ?NewPaymentHandler $paymentHandler = null,
		private ?ProgressPrinter $progressPrinter = null
	) {
		if ( $paymentHandler === null ) {
			$this->paymentHandler = new NullPaymentHandler();
		}
		if ( $this->progressPrinter === null ) {
			$this->progressPrinter = new ProgressPrinter();
		}
	}

	/**
	 * Convert memberships to payments
	 *
	 * Leave out parameters to convert all memberships
	 *
	 * @param int $idOffset Starting membership ID (exclusive)
	 * @param int $maxMembershipId End membership ID
	 * @return ConversionResult
	 */
	public function convertMemberships( int $idOffset = 0, int $maxMembershipId = self::CONVERT_ALL ): ConversionResult {
		$this->result = new ConversionResult();
		if ( $maxMembershipId === self::CONVERT_ALL ) {
			$maxMembershipId = $this->getMaxId();
		}
		$this->progressPrinter->initialize( $maxMembershipId - $idOffset );
		foreach ( $this->getRows( $idOffset, $maxMembershipId ) as $row ) {
			$this->result->addRow();
			if ( $row['data'] ) {
				if ( $row['data'] === 'Array' ) {
					$this->result->addWarning( 'Unserializable data field', $row );
					$row['data'] = [];
				} else {
					$row['data'] = unserialize( base64_decode( $row['data'] ) );
				}
			}

			$membershipId = intval( $row['id'] );
			try {
				$payment = $this->newPayment( $row );
				$this->paymentHandler->handlePayment( $payment, $membershipId );
			} catch ( \Throwable $e ) {
				$msg = $e->getMessage();
				$this->result->addError( $msg, $row );
			}
			$this->progressPrinter->printProgress( $membershipId );
		}
		return $this->result;
	}

	private function getRows( int $minMembershipId, int $maxMembershipId ): iterable {
		$qb = $this->db->createQueryBuilder();
		$qb->select( 'id', 'membership_fee AS amount', 'membership_fee_interval AS intervalInMonths', 'payment_type as paymentType',
			'data', 'iban', 'bic', 'status', 'timestamp AS membershipDate',
		)
			->from( 'request', 'm' );

		return new ChunkedQueryResultIterator( $qb, 'id', self::CHUNK_SIZE, $maxMembershipId, $minMembershipId );
	}

	private function getMaxId(): int {
		$maxId = $this->db->executeQuery( "SELECT MAX(id) FROM request" )->fetchOne();
		if ( $maxId === false ) {
			throw new \RuntimeException( 'Could not get maximum ID' );
		}
		if ( $maxId === null ) {
			return 0;
		}
		return $maxId;
	}

	private function newPayment( array $row ): Payment {
		switch ( $row['paymentType'] ) {
			case 'PPL':
				return $this->newPayPalPayment( $row );
			case 'BEZ':
				return $this->newDirectDebitPayment( $row );
			default:
				throw new \Exception( sprintf( "Unknown payment type '%s'", $row['paymentType'] ) );
		}
	}

	private function newPayPalPayment( array $row ): PayPalPayment {
		// We don't book PayPal payments, as they were never really active
		return new PayPalPayment(
			intval( $row['id'] ),
			$this->getAmount( $row ),
			PaymentInterval::from( $row['intervalInMonths'] )
		);
	}

	private function newDirectDebitPayment( array $row ): DirectDebitPayment {
		if ( empty( $row['data']['iban'] ) ) {
			// DummyData
			$iban = new Iban( 'DE88100900001234567892' );
			$bic = 'BEVODEBB';
			$anonymous = true;
		} else {
			$iban = new Iban( $row['iban'] ?? '' );
			$bic = $row['bic'] ?? '';
			$anonymous = false;
		}
		$payment = DirectDebitPayment::create(
			intval( $row['id'] ),
			$this->getAmount( $row ),
			PaymentInterval::from( intval( $row['intervalInMonths'] ) ),
			$iban,
			$bic
		);
		if ( $anonymous ) {
			$payment->anonymise();
		}
		if ( $row['status'] == MembershipApplication::STATUS_CANCELED ) {
			$payment->cancel();
		}
		return $payment;
	}

	private function getAmount( array $row ): Euro {
		$amount = $row['amount'];
		if ( $amount === '' || $amount === null ) {
			$this->result->addWarning( 'Converted empty amount to 0', $row );
			$amount = '0';
		}
		return Euro::newFromInt( $amount );
	}
}
