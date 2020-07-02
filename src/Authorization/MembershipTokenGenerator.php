<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Authorization;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
interface MembershipTokenGenerator {

	public function generateToken(): string;

	public function generateTokenExpiry(): \DateTime;

}
