<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\MembershipContext\Authorization\ApplicationTokenFetcher;
use WMDE\Fundraising\MembershipContext\Authorization\ApplicationTokenFetchingException;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipApplicationTokens;
use WMDE\Fundraising\MembershipContext\DataAccess\Internal\DoctrineApplicationTable;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;

/**
 * @deprecated The ApplicationTokenFetcher is no longer used
 */
class DoctrineApplicationTokenFetcher implements ApplicationTokenFetcher {

	private DoctrineApplicationTable $table;

	public function __construct( EntityManager $entityManager ) {
		$this->table = new DoctrineApplicationTable( $entityManager );
	}

	/**
	 * @param int $membershipApplicationId
	 *
	 * @return MembershipApplicationTokens
	 * @throws ApplicationTokenFetchingException
	 */
	public function getTokens( int $membershipApplicationId ): MembershipApplicationTokens {
		try {
			$application = $this->table->getApplicationById( $membershipApplicationId );
		} catch ( GetMembershipApplicationException $ex ) {
			throw new ApplicationTokenFetchingException(
				sprintf( 'Could not get membership application with ID %d', $membershipApplicationId ),
				$ex
			);
		}

		return new MembershipApplicationTokens(
			$application->getDataObject()->getAccessToken(),
			$application->getDataObject()->getUpdateToken()
		);
	}

}
