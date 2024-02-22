<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Model;

use ArrayObject;
use LogicException;
use Traversable;

class MembershipApplication {

	public const ACTIVE_MEMBERSHIP = 'active';
	public const SUSTAINING_MEMBERSHIP = 'sustaining';

	/**
	 * @var ModerationReason[]
	 */
	private array $moderationReasons;
	private bool $cancelled = false;
	private bool $confirmed = false;
	private bool $exported = false;

	/** @var Incentive[] */
	private array $incentives = [];

	public function __construct(
		private readonly int $id,
		private readonly string $type,
		private Applicant $applicant,
		private readonly int $paymentId,
		private readonly ?bool $donationReceipt
	) {
		$this->moderationReasons = [];
	}

	public function __clone(): void {
		$this->applicant = clone $this->applicant;
		for ( $i = 0; $i < count( $this->moderationReasons ); $i++ ) {
			$this->moderationReasons[$i] = clone $this->moderationReasons[$i];
		}
	}

	public function getId(): int {
		return $this->id;
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
	 * @param ModerationReason ...$moderationReasons provide at least 1 ModerationReason to mark for moderation
	 */
	public function markForModeration( ModerationReason ...$moderationReasons ): void {
		if ( empty( $moderationReasons ) ) {
			throw new LogicException( "you must provide at least one ModerationReason to mark a donation for moderation" );
		}
		$this->moderationReasons = array_merge( $this->moderationReasons, $moderationReasons );
	}

	public function approve(): void {
		$this->moderationReasons = [];
	}

	/**
	 * @return ModerationReason[]
	 */
	public function getModerationReasons(): array {
		return $this->moderationReasons;
	}

	public function isMarkedForModeration(): bool {
		return count( $this->moderationReasons ) > 0;
	}

	public function cancel(): void {
		if ( !$this->isCancellable() ) {
			throw new LogicException( 'Can only cancel new donations' );
		}
		$this->cancelled = true;
	}

	public function restore(): void {
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
	 * @return Traversable<int, Incentive>
	 */
	public function getIncentives(): Traversable {
		return new ArrayObject( $this->incentives );
	}

	public function shouldSendConfirmationMail(): bool {
		if ( !$this->confirmed ) {
			return false;
		}
		foreach ( $this->moderationReasons as $moderationReason ) {
			if ( $moderationReason->getModerationIdentifier() === ModerationIdentifier::EMAIL_BLOCKED ) {
				return false;
			}
		}
		return true;
	}

}
