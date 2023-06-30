<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipTokenGenerator;
use WMDE\Fundraising\MembershipContext\MembershipContextFactory;
use WMDE\Fundraising\PaymentContext\PaymentContextFactory;

class TestMembershipContextFactory {

	private array $config;
	private ?Connection $connection;
	private MembershipContextFactory $factory;
	private ?EntityManager $entityManager;

	public function __construct( array $config ) {
		$this->config = $config;
		$this->factory = new MembershipContextFactory( $config );
		$this->entityManager = null;
		$this->connection = null;
	}

	public function getConnection(): Connection {
		if ( $this->connection === null ) {
			$this->connection = DriverManager::getConnection( $this->config['db'] );
			$this->factory->registerCustomTypes( $this->connection );
		}
		return $this->connection;
	}

	public function getEntityManager(): EntityManager {
		if ( $this->entityManager === null ) {
			$this->entityManager = $this->newEntityManager( $this->factory->newEventSubscribers() );
		}
		return $this->entityManager;
	}

	private function newEntityManager( array $eventSubscribers = [] ): EntityManager {
		$paymentContext = new PaymentContextFactory();
		$doctrineConfig = ORMSetup::createXMLMetadataConfiguration( array_merge(
			$this->factory->getDoctrineMappingPaths(),
			$paymentContext->getDoctrineMappingPaths()
		) );

		$entityManager = EntityManager::create( $this->getConnection(), $doctrineConfig );

		$paymentContext->registerCustomTypes( $entityManager->getConnection() );

		$this->setupEventSubscribers( $entityManager->getEventManager(), $eventSubscribers );

		return $entityManager;
	}

	private function setupEventSubscribers( EventManager $eventManager, array $eventSubscribers ): void {
		foreach ( $eventSubscribers as $eventSubscriber ) {
			$eventManager->addEventSubscriber( $eventSubscriber );
		}
	}

	public function setTokenGenerator( MembershipTokenGenerator $tokenGenerator ): void {
		$this->factory->setTokenGenerator( $tokenGenerator );
	}

	public function newSchemaCreator(): SchemaCreator {
		return new SchemaCreator( $this->newEntityManager() );
	}
}
