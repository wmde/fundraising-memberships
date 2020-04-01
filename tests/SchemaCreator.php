<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;

class SchemaCreator {
	private EntityManager $entityManager;
	private SchemaTool $schemaTool;

	public function __construct( EntityManager $entityManager ) {
		$this->entityManager = $entityManager;
		$this->schemaTool = new SchemaTool( $this->entityManager );
	}

	public function createSchema() {
		$this->getSchemaTool()->createSchema( $this->getClassMetaData() );
	}

	public function dropSchema() {
		$this->getSchemaTool()->dropSchema( $this->getClassMetaData() );
	}

	private function getSchemaTool(): SchemaTool {
		return $this->schemaTool;
	}

	private function getClassMetaData(): array {
		return $this->entityManager->getMetadataFactory()->getAllMetadata();
	}

}