<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use WMDE\Fundraising\MembershipContext\MembershipContextFactory;
use WMDE\Fundraising\PaymentContext\PaymentContextFactory;

/**
 * @phpstan-import-type Params from DriverManager
 */
class TestMembershipContextFactory {

	private ?Connection $connection;
	private MembershipContextFactory $factory;
	private ?EntityManager $entityManager;

	/**
	 * @param Params $config
	 */
	public function __construct( private readonly array $config ) {
		$this->factory = new MembershipContextFactory();
		$this->entityManager = null;
		$this->connection = null;
	}

	public function getConnection(): Connection {
		if ( $this->connection === null ) {
			$this->connection = DriverManager::getConnection( $this->config );
			$this->factory->registerCustomTypes( $this->connection );
		}
		return $this->connection;
	}

	public function getEntityManager(): EntityManager {
		if ( $this->entityManager === null ) {
			$this->entityManager = $this->newEntityManager();
		}
		return $this->entityManager;
	}

	private function newEntityManager(): EntityManager {
		$paymentContext = new PaymentContextFactory();
		$doctrineConfig = ORMSetup::createXMLMetadataConfiguration( array_merge(
			$this->factory->getDoctrineMappingPaths(),
			$paymentContext->getDoctrineMappingPaths()
		) );
		$doctrineConfig->enableNativeLazyObjects( true );

		$entityManager = new EntityManager( $this->getConnection(), $doctrineConfig );

		$paymentContext->registerCustomTypes( $entityManager->getConnection() );

		return $entityManager;
	}

	public function newSchemaCreator(): SchemaCreator {
		return new SchemaCreator( $this->newEntityManager() );
	}
}
