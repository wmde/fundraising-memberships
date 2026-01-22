<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication as DoctrineApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\Internal\DoctrineApplicationTable;
use WMDE\Fundraising\MembershipContext\DataAccess\LegacyConverters\DomainToLegacyConverter;
use WMDE\Fundraising\MembershipContext\DataAccess\LegacyConverters\LegacyToDomainConverter;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationReason;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationAnonymizedException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\MembershipRepository;
use WMDE\Fundraising\PaymentContext\UseCases\GetPayment\GetPaymentUseCase;

class DoctrineMembershipRepository implements MembershipRepository {

	private DoctrineApplicationTable $table;

	public function __construct(
		EntityManager $entityManager,
		private readonly GetPaymentUseCase $getPaymentUseCase,
		private readonly ModerationReasonRepository $moderationReasonRepository
	) {
		$this->table = new DoctrineApplicationTable( $entityManager );
	}

	public function storeApplication( MembershipApplication $application ): void {
		// Doctrine will persist the moderation reasons that are not yet found in the database
		// and create relation entries to the membership application automatically
		$existingModerationReasons = $this->moderationReasonRepository->getModerationReasonsThatAreAlreadyPersisted(
			...$application->getModerationReasons()
		);

		$doctrineApplication = $this->table->getApplicationOrNullById( $application->getId() ) ?? new DoctrineApplication();
		$this->updateDoctrineApplication( $doctrineApplication, $application, $existingModerationReasons );
		$this->table->persistApplication( $doctrineApplication );
	}

	/**
	 * @param DoctrineApplication $doctrineApplication
	 * @param MembershipApplication $application
	 * @param ModerationReason[] $existingModerationReasons
	 */
	private function updateDoctrineApplication( DoctrineApplication $doctrineApplication, MembershipApplication $application, array $existingModerationReasons ): void {
		$converter = new DomainToLegacyConverter();
		$converter->convert(
			$doctrineApplication,
			$application,
			$this->getPaymentUseCase->getLegacyPaymentDataObject( $application->getPaymentId() ),
			$existingModerationReasons
		);
	}

	public function getMembershipApplicationById( int $id ): ?MembershipApplication {
		$application = $this->table->getApplicationOrNullById( $id );
		return $application === null ? null : $this->convertMembershipApplication( $application );
	}

	/**
	 * @param int $id
	 *
	 * @return MembershipApplication|null which was not exported (and thus not anonymized) yet, so it can still be
	 * altered, e.g. setting moderation status
	 */
	public function getUnexportedAndUnscrubbedMembershipApplicationById( int $id ): ?MembershipApplication {
		$application = $this->table->getApplicationOrNullById( $id );

		if ( $application === null ) {
			return null;
		}

		if ( $application->getExport() !== null || $application->isAnonymized() ) {
			throw new ApplicationAnonymizedException();
		}

		return $this->convertMembershipApplication( $application );
	}

	private function convertMembershipApplication( DoctrineApplication $application ): MembershipApplication {
		$converter = new LegacyToDomainConverter();
		return $converter->createFromLegacyObject( $application );
	}
}
