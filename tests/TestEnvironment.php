<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipTokenGenerator;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TestEnvironment {

	private TestMembershipContextFactory $factory;

	private function __construct( array $config, Configuration $doctrineConfig ) {
		$this->factory = new TestMembershipContextFactory( $config, $doctrineConfig );
	}

	public static function newInstance(): self {
		$environment = new self(
			[
				'db' => [
					'driver' => 'pdo_sqlite',
					'memory' => true,
				],
				'token-length' => 16,
				'token-validity-timestamp' => 'PT4H',
			],
			Setup::createConfiguration( true )
		);

		$environment->install();

		return $environment;
	}

	private function install(): void {
		$schemaCreator = new SchemaCreator( $this->getEntityManager() );

		try {
			$schemaCreator->dropSchema();
		}
		catch ( \Exception $ex ) {
		}

		$schemaCreator->createSchema();
	}

	public function getEntityManager(): EntityManager {
		return $this->factory->getEntityManager();
	}

	public function setTokenGenerator( MembershipTokenGenerator $tokenGenerator ): void {
		$this->factory->setTokenGenerator( $tokenGenerator );
	}
}
