<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tracking;

class MembershipApplicationTrackingInfo {
	public function __construct(
		private readonly string $campaignCode,
		private readonly string $keyword
	) {
	}

	public function getCampaignCode(): string {
		return $this->campaignCode;
	}

	public function getKeyword(): string {
		return $this->keyword;
	}

}
