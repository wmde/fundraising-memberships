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

	private ?int $id;

	private string $type;
	private Applicant $applicant;
	private Payment $payment;
	private bool $moderationNeeded = false;
	private bool $cancelled = false;
	private bool $confirmed = false;
	private bool $exported = false;
	private ?bool $donationReceipt;
	/** @var Incentive[] */
	private array $incentives = [];

	public function __construct( ?int $id, string $type, Applicant $applicant, Payment $payment, ?bool $donationReceipt ) {
		$this->id = $id;
		$this->type = $type;
		$this->applicant = $applicant;
		$this->payment = $payment;
		$this->donationReceipt = $donationReceipt;
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

	public function markForModeration(): void {
		$this->moderationNeeded = true;
	}

	public function approve(): void {
		$this->moderationNeeded = false;
	}

	public function needsModeration(): bool {
		return $this->moderationNeeded;
	}

	public function cancel(): void {
		if ( !$this->isCancellable() ) {
			throw new \LogicException( 'Can only cancel new donations' );
		}
		$this->cancelled = true;
	}

	public function isCancelled(): bool {
		return $this->cancelled;
	}

	public function isConfirmed(): bool {
		return $this->confirmed;
	}

	private function isCancellable(): bool {
		if ( $this->isCancelled() ) {
			return false;
		}

		if ( $this->exported ) {
			return false;
		}

		if ( $this->payment->getPaymentMethod()->hasExternalProvider() ) {
			return false;
		}

		return true;
	}

	public function confirm(): void {
		$this->confirmed = true;
	}

	public function setExported(): void {
		$this->exported = true;
	}

	public function isExported(): bool {
		return $this->exported;
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
