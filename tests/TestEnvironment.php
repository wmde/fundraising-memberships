<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests;

use Doctrine\ORM\EntityManager;
use Exception;

/**
 * @phpstan-import-type Params from \Doctrine\DBAL\DriverManager
 */
class TestEnvironment {

	private TestMembershipContextFactory $factory;

	/**
	 * @param Params $config
	 */
	private function __construct( array $config ) {
		$this->factory = new TestMembershipContextFactory( $config );
	}

	public static function newInstance(): self {
		$environment = new self( [ 'driver' => 'pdo_sqlite', 'memory' => true, ] );

		$environment->install();

		return $environment;
	}

	private function install(): void {
		$schemaCreator = $this->getFactory()->newSchemaCreator();

		try {
			$schemaCreator->dropSchema();
		} catch ( Exception $ex ) {
		}

		$schemaCreator->createSchema();
	}

	public function getFactory(): TestMembershipContextFactory {
		return $this->factory;
	}

	public function getEntityManager(): EntityManager {
		return $this->factory->getEntityManager();
	}
}
