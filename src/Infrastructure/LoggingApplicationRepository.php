<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Infrastructure;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;

/**
 * @deprecated Doctrine errors are rare, use application-level logging for Doctrine errors
 */
class LoggingApplicationRepository implements ApplicationRepository {

	private const CONTEXT_EXCEPTION_KEY = 'exception';

	private string $logLevel;

	public function __construct(
		private readonly ApplicationRepository $repository,
		private readonly LoggerInterface $logger
	) {
		$this->logLevel = LogLevel::CRITICAL;
	}

	/**
	 * @param MembershipApplication $application
	 *
	 * @throws StoreMembershipApplicationException
	 * @see MembershipApplicationRepository::storeApplication
	 *
	 */
	public function storeApplication( MembershipApplication $application ): void {
		try {
			$this->repository->storeApplication( $application );
		} catch ( StoreMembershipApplicationException $ex ) {
			$this->logger->log( $this->logLevel, $ex->getMessage(), [ self::CONTEXT_EXCEPTION_KEY => $ex ] );
			throw $ex;
		}
	}

	/**
	 * @param int $id
	 *
	 * @return MembershipApplication|null
	 *
	 * @throws GetMembershipApplicationException
	 */
	public function getApplicationById( int $id ): ?MembershipApplication {
		try {
			return $this->repository->getApplicationById( $id );
		} catch ( GetMembershipApplicationException $ex ) {
			$this->logger->log( $this->logLevel, $ex->getMessage(), [ self::CONTEXT_EXCEPTION_KEY => $ex ] );
			throw $ex;
		}
	}
}
