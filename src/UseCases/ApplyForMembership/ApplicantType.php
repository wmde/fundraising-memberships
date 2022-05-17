<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership;

enum ApplicantType {
	case PERSON_APPLICANT;
	case COMPANY_APPLICANT;
}
