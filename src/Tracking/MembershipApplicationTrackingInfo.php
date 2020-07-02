<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tracking;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class MembershipApplicationTrackingInfo {

	private $campaignCode;
	private $keyword;

	public function __construct( string $campaignCode, string $keyword ) {
		$this->campaignCode = $campaignCode;
		$this->keyword = $keyword;
	}

	public function getCampaignCode(): string {
		return $this->campaignCode;
	}

	public function getKeyword(): string {
		return $this->keyword;
	}

}
