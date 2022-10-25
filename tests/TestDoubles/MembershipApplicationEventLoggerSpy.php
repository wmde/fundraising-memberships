<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\TestDoubles;

use WMDE\Fundraising\MembershipContext\Infrastructure\MembershipApplicationEventLogger;

class MembershipApplicationEventLoggerSpy implements MembershipApplicationEventLogger {

	private array $logs = [];

	public function log( int $membershipApplicationId, string $message ): void {
		$this->logs[$membershipApplicationId] = $message;
	}

	public function getLogs(): array {
		return $this->logs;
	}
}
