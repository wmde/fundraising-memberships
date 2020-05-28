<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext;

use WMDE\Fundraising\MembershipContext\Domain\Event;

interface EventEmitter {

	public function emit( Event $event ): void;
}
