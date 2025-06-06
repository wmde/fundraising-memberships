<?php

namespace WMDE\Fundraising\MembershipContext\Domain\Model;

use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;

class MembershipFeeUpgrade {
	private ?int $amount;
	private ?PaymentInterval $interval;

	private MembershipFeeUpgradeState $state;

	public function __construct(
		private int $id,
		private string $email,
		private string $UUID
	) {
		$this->amount = null;
		$this->interval = null;
		$this->state = MembershipFeeUpgradeState::NEW;
	}

	public function updateMembershipFee( PaymentInterval $interval, int $amount ): void {
		$this->amount = $amount;
		$this->interval = $interval;
	}

	public function getAmount(): ?int {
		return $this->amount;
	}

	public function getInterval(): ?PaymentInterval {
		return $this->interval;
	}

}
