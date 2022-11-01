<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\TestDoubles;

use WMDE\Fundraising\MembershipContext\Domain\Event;
use WMDE\Fundraising\MembershipContext\EventEmitter;

class EventEmitterSpy implements EventEmitter {

	private array $events = [];

	public function emit( Event $event ): void {
		$this->events[] = $event;
	}

	/**
	 * @return Event[]
	 */
	public function getEvents(): array {
		return $this->events;
	}
}
