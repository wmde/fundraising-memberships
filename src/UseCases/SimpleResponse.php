<?php

namespace WMDE\Fundraising\MembershipContext\UseCases;

/**
 * This interface represents the response to a use case that can either succeed or fail, containing the ID of the
 * membership application as additional information.
 *
 * Responses can implement this interface where the exact nature of the failure does not need to be communicated to
 * the calling code and where the calling code does not need a whole data transfer object with membership information,
 * e.g. for changing the deletion or moderation status of mombership applications.
 */
interface SimpleResponse {
	public function getMembershipApplicationId(): int;

	public function isSuccess(): bool;
}
