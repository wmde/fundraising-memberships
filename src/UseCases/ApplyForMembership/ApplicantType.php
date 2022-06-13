<?php
// phpcs:ignoreFile -- Until phpcs has 8.1 enum support, see https://github.com/squizlabs/PHP_CodeSniffer/issues/3479

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership;

enum ApplicantType: string {
	case PERSON_APPLICANT = 'person';
	case COMPANY_APPLICANT = 'firma';
}
