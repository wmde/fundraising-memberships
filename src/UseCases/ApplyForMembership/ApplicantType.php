<?php
declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership;

enum ApplicantType: string {
	case PERSON_APPLICANT = 'person';
	case COMPANY_APPLICANT = 'firma';
}
