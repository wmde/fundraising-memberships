<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Model;

use RuntimeException;
use Traversable;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentMethod;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Application {

	public const ACTIVE_MEMBERSHIP = 'active';
	public const SUSTAINING_MEMBERSHIP = 'sustaining';

	private const NO_MODERATION_NEEDED = false;
	private const NEEDS_MODERATION = true;

	private const IS_CURRENT = false;
	private const IS_CANCELLED = true;

	private const IS_CONFIRMED = true;
	private const IS_PENDING = false;

	private const IS_NOT_DELETED = false;
	private const IS_DELETED = true;

	/**
	 * @var int|null
	 */
	private $id;

	private string $type;
	private Applicant $applicant;
	private Payment $payment;
	private bool $needsModeration;
	private bool $isCancelled;
	private bool $isConfirmed;
	private bool $isDeleted;
	private ?bool $donationReceipt;
	/** @var Incentive[] */
	private array $incentives;

	public static function newApplication( string $type, Applicant $applicant, Payment $payment, ?bool $donationReceipt ): self {
		return new self(
			null,
			$type,
			$applicant,
			$payment,
			self::NO_MODERATION_NEEDED,
			self::IS_CURRENT,
			self::IS_PENDING,
			self::IS_NOT_DELETED,
			$donationReceipt
		);
	}

	public function __construct( ?int $id, string $type, Applicant $applicant, Payment $payment,
		bool $needsModeration, bool $isCancelled, bool $isConfirmed, bool $isDeleted, ?bool $donationReceipt ) {
		$this->id = $id;
		$this->type = $type;
		$this->applicant = $applicant;
		$this->payment = $payment;
		$this->needsModeration = $needsModeration;
		$this->isCancelled = $isCancelled;
		$this->isConfirmed = $isConfirmed;
		$this->isDeleted = $isDeleted;
		$this->donationReceipt = $donationReceipt;
		$this->incentives = [];
	}

	public function getId(): ?int {
		return $this->id;
	}

	public function hasId(): bool {
		return $this->id !== null;
	}

	public function getApplicant(): Applicant {
		return $this->applicant;
	}

	public function getPayment(): Payment {
		return $this->payment;
	}

	public function getType(): string {
		return $this->type;
	}

	public function getDonationReceipt(): ?bool {
		return $this->donationReceipt;
	}

	/**
	 * @param int $id
	 * @throws \RuntimeException
	 */
	public function assignId( int $id ): void {
		if ( $this->id !== null && $this->id !== $id ) {
			throw new \RuntimeException( 'Id cannot be changed after initial assignment' );
		}

		$this->id = $id;
	}

	public function cancel(): void {
		$this->isCancelled = self::IS_CANCELLED;
	}

	public function markForModeration(): void {
		$this->needsModeration = self::NEEDS_MODERATION;
	}

	public function isCancelled(): bool {
		return $this->isCancelled === self::IS_CANCELLED;
	}

	public function needsModeration(): bool {
		return $this->needsModeration === self::NEEDS_MODERATION;
	}

	public function isConfirmed(): bool {
		return $this->isConfirmed === self::IS_CONFIRMED;
	}

	public function confirm(): void {
		$this->isConfirmed = self::IS_CONFIRMED;
	}

	public function confirmSubscriptionCreated(): void {
		if ( !$this->hasExternalPayment() ) {
			throw new RuntimeException( 'Only external payments can be confirmed as booked' );
		}

		if ( !$this->statusAllowsForBooking() ) {
			throw new RuntimeException( 'Only unconfirmed membership applications can be confirmed as booked' );
		}

		$this->confirm();
	}

	public function hasExternalPayment(): bool {
		return $this->getPayment()->getPaymentMethod()->getId() === PaymentMethod::PAYPAL;
	}

	private function statusAllowsForBooking(): bool {
		return !$this->isConfirmed() || $this->needsModeration() || $this->isCancelled();
	}

	public function markAsDeleted(): void {
		$this->isDeleted = self::IS_DELETED;
	}

	public function isDeleted(): bool {
		return $this->isDeleted;
	}

	public function notifyOfFirstPaymentDate( string $firstPaymentDate ): void {
		$paymentMethod = $this->getPayment()->getPaymentMethod();

		if ( $paymentMethod instanceof PayPalPayment ) {
			$paymentMethod->getPayPalData()->setFirstPaymentDate( $firstPaymentDate );
		}
	}

	public function addIncentive( Incentive $incentive ): void {
		$this->incentives[] = $incentive;
	}

	/**
	 * @return Traversable<Incentive>
	 */
	public function getIncentives(): Traversable {
		return new \ArrayObject( $this->incentives );
	}

}
