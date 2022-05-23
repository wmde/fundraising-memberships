<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Model;

use Traversable;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MembershipApplication {

	public const ACTIVE_MEMBERSHIP = 'active';
	public const SUSTAINING_MEMBERSHIP = 'sustaining';

	private ?int $id;

	private string $type;
	private Applicant $applicant;
	private int $paymentId;
	private bool $moderationNeeded = false;
	private bool $cancelled = false;
	private bool $confirmed = false;
	private bool $exported = false;
	private ?bool $donationReceipt;
	/** @var Incentive[] */
	private array $incentives = [];

	public function __construct( ?int $id, string $type, Applicant $applicant, int $paymentId, ?bool $donationReceipt ) {
		$this->id = $id;
		$this->type = $type;
		$this->applicant = $applicant;
		$this->paymentId = $paymentId;
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

	public function getPaymentId(): int {
		return $this->paymentId;
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

	public function restore() {
		$this->cancelled = false;
	}

	public function isCancelled(): bool {
		return $this->cancelled;
	}

	public function confirm(): void {
		$this->confirmed = true;
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

		return true;
	}

	public function setExported(): void {
		$this->exported = true;
	}

	public function isExported(): bool {
		return $this->exported;
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
