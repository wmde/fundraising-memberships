<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Model;

class FeeChange {
	// This is set by Doctrine
	// @phpstan-ignore-next-line
	private ?int $id;

	public function __construct(
		private readonly string $uuid,
		private int $paymentId,
		private int $externalMemberId,
		private string $memberName,
		private int $currentAmountInCents,
		private int $suggestedAmountInCents,
		private int $currentInterval,
		private FeeChangeState $state,
		private ?\DateTime $exportDate,
		/** @var \DateTime|null timestamp to mark when a user has filled out and successfully submitted a membership fee change */
		private ?\DateTime $filledOn
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

	public function getMemberName(): string {
		return $this->memberName;
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

	public function getFilledOnDate(): ?\DateTime {
		return $this->filledOn;
	}

	/**
	 * @param int $paymentId
	 * @param string $memberName
	 * @param \DateTimeImmutable $filledOn timestamp that indicates a user successfully filled out and submitted their membership fee/payment info change
	 */
	public function updateMembershipFee( int $paymentId, string $memberName, \DateTimeImmutable $filledOn ): void {
		$this->memberName = $memberName;
		$this->paymentId = $paymentId;
		$this->state = FeeChangeState::FILLED;
		$this->filledOn = \DateTime::createFromImmutable( $filledOn );
	}

	public function export( \DateTime $exportDate ): void {
		$this->exportDate = $exportDate;
		$this->state = FeeChangeState::EXPORTED;
	}
}
