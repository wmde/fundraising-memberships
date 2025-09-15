<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Model;

class FeeChange {
	/**
	 * This is set by Doctrine
	 */
	// @phpstan-ignore-next-line
	private ?int $id;

	public function __construct(
		private readonly string $uuid,
		private int $paymentId,
		private int $externalMemberId,
		private int $currentAmountInCents,
		private int $suggestedAmountInCents,
		private int $currentInterval,
		private FeeChangeState $state,
		private ?\DateTime $exportDate
	) {
	}

	public function getId(): ?int {
		return $this->id;
	}

	public function getUuid(): string {
		return $this->uuid;
	}

	public function getPaymentId(): int {
		return $this->paymentId;
	}

	public function getExternalMemberId(): int {
		return $this->externalMemberId;
	}

	public function getCurrentAmountInCents(): int {
		return $this->currentAmountInCents;
	}

	public function getSuggestedAmountInCents(): int {
		return $this->suggestedAmountInCents;
	}

	public function getCurrentInterval(): int {
		return $this->currentInterval;
	}

	public function getState(): FeeChangeState {
		return $this->state;
	}

	public function getExportDate(): ?\DateTime {
		return $this->exportDate;
	}

	public function updateMembershipFee( int $paymentId ): void {
		$this->paymentId = $paymentId;
		$this->state = FeeChangeState::FILLED;
	}

	public function export( \DateTime $exportDate ): void {
		$this->exportDate = $exportDate;
		$this->state = FeeChangeState::EXPORTED;
	}
}
