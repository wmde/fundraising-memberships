<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Model;

class ModerationReason {
	/**
	 * @param ModerationIdentifier $moderationIdentifier identifies the reason for the moderation
	 * @param string $source origin that caused the moderation (e.g. street value contained a bad word)
	 */
	public function __construct(
		private ModerationIdentifier $moderationIdentifier,
		private string $source = '' ) {
	}

	public function getModerationIdentifier(): ModerationIdentifier {
		return $this->moderationIdentifier;
	}

	public function getSource(): string {
		return $this->source;
	}

	public function setSource( string $source ): void {
		$this->source = $source;
	}

	public function __toString(): string {
		$source = $this->getSource();
		return $this->getModerationIdentifier()->name .	( $this->getSource() ? ":$source" : '' );
	}

}
