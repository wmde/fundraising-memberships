<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\ORM\EntityManager;
use Psr\Log\NullLogger;
use WMDE\Fundraising\MembershipContext\Authorization\ApplicationTokenFetcher;
use WMDE\Fundraising\MembershipContext\Authorization\ApplicationTokenFetchingException;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipApplicationTokens;
use WMDE\Fundraising\MembershipContext\DataAccess\Internal\DoctrineApplicationTable;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DoctrineApplicationTokenFetcher implements ApplicationTokenFetcher {

	private $table;

	public function __construct( EntityManager $entityManager ) {
		$this->table = new DoctrineApplicationTable( $entityManager, new NullLogger() ); // TODO logger
	}

	/**
	 * @param int $applicationId
	 *
	 * @return MembershipApplicationTokens
	 * @throws ApplicationTokenFetchingException
	 */
	public function getTokens( int $applicationId ): MembershipApplicationTokens {
		try {
			$application = $this->table->getApplicationById( $applicationId );
		}
		catch ( GetMembershipApplicationException $ex ) {
			throw new ApplicationTokenFetchingException(
				sprintf( 'Could not get membership application with ID %d', $applicationId ),
				$ex
			);
		}

		return new MembershipApplicationTokens(
			$application->getDataObject()->getAccessToken(),
			$application->getDataObject()->getUpdateToken()
		);
	}

}
