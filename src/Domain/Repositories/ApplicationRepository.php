<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Repositories;

/**
 * This is a backwards-compatibility interface.
 *
 * We decided to shorten the concept of "membership applications" to "membership" instead of "application" (or "request"),
 * because "application" and "request" have technical meaning and make the code more confusing to read.
 *
 * You can delete this interface when the Fundraising Application and the Fundraising Operation Center no longer use it.
 *
 * @deprecated Use {@see MembershipRepository} instead
 */
interface ApplicationRepository extends MembershipRepository {

}
