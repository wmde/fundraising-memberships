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
	private Connection $connection;
	private MembershipContextFactory $factory;
	private ?EntityManager $entityManager;

	public function __construct( array $config ) {
		$this->config = $config;

		$this->connection = DriverManager::getConnection( $this->config['db'] );
		$this->factory = new MembershipContextFactory( $config );
		$this->entityManager = null;
	}

	public function getEntityManager(): EntityManager {
		if ( $this->entityManager === null ) {
			$doctrineConfig = ORMSetup::createXMLMetadataConfiguration( [
				MembershipContextFactory::DOCTRINE_CLASS_MAPPING_DIRECTORY,
				MembershipContextFactory::DOMAIN_CLASS_MAPPING_DIRECTORY,
				PaymentContextFactory::DOCTRINE_CLASS_MAPPING_DIRECTORY
			] );

			$paymentContext = new PaymentContextFactory();
			$paymentContext->registerCustomTypes( $this->connection );

			$eventManager = $this->setupEventSubscribers( $this->factory->newEventSubscribers() );

			$this->entityManager = EntityManager::create(
				$this->connection,
				$doctrineConfig,
				$eventManager
			);
		}

		return $this->entityManager;
	}

	private function setupEventSubscribers( array $eventSubscribers ): EventManager {
		$eventManager = $this->connection->getEventManager();
		foreach ( $eventSubscribers as $eventSubscriber ) {
			$eventManager->addEventSubscriber( $eventSubscriber );
		}
		return $eventManager;
	}

	public function setTokenGenerator( MembershipTokenGenerator $tokenGenerator ) {
		$this->factory->setTokenGenerator( $tokenGenerator );
	}
}
